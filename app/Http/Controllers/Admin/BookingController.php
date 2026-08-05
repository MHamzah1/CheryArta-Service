<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\WhatsAppTemplateKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingFilterRequest;
use App\Http\Requests\Admin\StoreWalkInBookingRequest;
use App\Http\Requests\Admin\WalkInLookupRequest;
use App\Models\Booking;
use App\Models\CarModel;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\SlotService;
use App\Services\WalkInBookingService;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\BookingPresenter;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A2 — Manajemen Booking (docs/07-modul-admin.md §A2).
 *
 * Middleware `role` menjaga pintu masuk /admin; `Gate::authorize` di tiap
 * method adalah lapis keduanya, dan yang membedakan Super Admin dari advisor
 * (docs/09 §9.3).
 */
class BookingController extends Controller
{
    /** Daftar booking dengan saringan yang dijalankan di server (roadmap 1.5.2). */
    public function index(BookingFilterRequest $request, SlotService $slots): Response
    {
        Gate::authorize('viewAny', Booking::class);

        $filters = $request->filters();

        $bookings = Booking::query()
            // Tanpa ini satu halaman 25 baris menembakkan seratus kueri —
            // cara N+1 masuk lewat pintu belakang (.claude/rules/10).
            ->with(['user:id,name', 'vehicle.carModel:id,name', 'servicePackage:id,name', 'handledBy:id,name'])
            ->tap($filters->apply(...))
            ->paginate(25)
            ->withQueryString()
            ->through(BookingPresenter::adminRow(...));

        return Inertia::render('admin/bookings/index', [
            'bookings' => $bookings,
            'filters' => $filters->toArray(),
            'statusOptions' => BookingStatus::options(),
            'packageOptions' => $this->pilihanPaket(),
            'advisorOptions' => $this->pilihanAdvisor(),
            // "Hari ini" menurut zona bengkel, bukan zona peramban advisor —
            // tombol pintasan saringan tidak boleh meleset satu hari saat
            // dibuka dini hari (temuan B7).
            'today' => $slots->today()->toDateString(),
        ]);
    }

    /**
     * Form booking walk-in (roadmap 1.5.4).
     *
     * Pencarian pelanggan memakai kunjungan Inertia parsial, bukan `fetch` ke
     * endpoint JSON baru: aturan 20 hanya mengecualikan ketersediaan slot, dan
     * daftar pelanggan justru data yang paling tidak boleh punya endpoint
     * terbuka sendiri (temuan S8).
     */
    public function create(WalkInLookupRequest $request, SlotService $slots): Response
    {
        Gate::authorize('createWalkIn', Booking::class);

        $kata = $request->keyword();

        return Inertia::render('admin/bookings/create', [
            'servicePackages' => $this->paketAktif(),
            'carModels' => CarModel::query()->active()->ordered()->get(['id', 'name'])->all(),
            // Walk-in melewati aturan H-1: batas kalendernya karena itu
            // dihitung dengan sumber WalkIn, bukan Web.
            'slotRules' => BookingPresenter::slotRules($slots, BookingSource::WalkIn),
            'filters' => ['cari' => $kata],
            'customerResults' => $kata === null ? [] : $this->cariPelanggan($kata),
            'selectedCustomer' => $this->pelangganTerpilih($request->customerId()),
        ]);
    }

    public function store(StoreWalkInBookingRequest $request, WalkInBookingService $walkIns): RedirectResponse
    {
        Gate::authorize('createWalkIn', Booking::class);

        $booking = $walkIns->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', "Booking walk-in {$booking->booking_code} dibuat dan langsung dikonfirmasi.");
    }

    /** Detail booking + panel ubah status + panel WhatsApp (roadmap 1.5.3 & 2.3.3). */
    public function show(Request $request, Booking $booking, WhatsAppNotifier $whatsapp): Response
    {
        Gate::authorize('view', $booking);

        $booking->load([
            'user',
            'vehicle.carModel',
            'servicePackage',
            'handledBy:id,name',
            'rescheduledFrom:id,booking_code,booking_date,booking_time',
            'statusHistories' => fn ($query) => $query->with('changedBy:id,name')->orderBy('created_at')->orderBy('id'),
            // Ikut dimuat di sini, bukan dikueri di dalam presenter: riwayat
            // pesan dibaca beberapa kali saat menyusun panel, dan kueri di
            // dalam perulangan adalah cara N+1 masuk lewat pintu belakang.
            'whatsappMessages' => fn ($query) => $query->with('generatedBy:id,name')->latest('id'),
        ]);

        return Inertia::render('admin/bookings/show', [
            'booking' => BookingPresenter::adminDetail($booking),
            'timeline' => $booking->statusHistories->map(BookingPresenter::adminTimelineEntry(...))->all(),

            // Status tujuan yang sah datang dari state machine di server.
            // React hanya merendernya — daftar yang disusun ulang di sana
            // adalah cara aturan yang sama mulai hidup di dua tempat.
            'statusOptions' => $booking->status->transitionOptions(),

            // Keputusan boleh/tidak dihitung server, dikirim sebagai boolean
            // (.claude/rules/20).
            'canUpdateStatus' => $request->user()->can('updateStatus', $booking),
            'canDelete' => $request->user()->can('delete', $booking),

            // Panel penuh A7 (docs/08 §8.2). Draftnya dihitung di sini supaya
            // BookingPresenter tetap murni pembentuk props — ia tidak ikut
            // memutuskan template mana yang berlaku.
            'whatsapp' => BookingPresenter::whatsappPanel(
                $booking,
                $whatsapp->draft($booking, WhatsAppTemplateKey::forStatus($booking->status)),
                $booking->isRemindable()
                    ? $whatsapp->draft($booking, WhatsAppTemplateKey::BookingReminder)
                    : null,
                $booking->whatsappMessages,
            ),
        ]);
    }

    /**
     * Soft delete — Super Admin saja (docs/07 §A14).
     *
     * Untuk membatalkan booking, advisor memakai transisi status `cancelled`
     * beserta alasannya. Penghapusan dipakai untuk salah input, dan bookingnya
     * pun tidak benar-benar hilang: baris riwayat statusnya tetap ada.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        Gate::authorize('delete', $booking);

        $kode = $booking->booking_code;
        $booking->delete();

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', "Booking {$kode} dihapus dari daftar.");
    }

    /**
     * Pelanggan yang sedang dipilih di form walk-in, beserta kendaraannya.
     *
     * @return array<string, mixed>|null
     */
    private function pelangganTerpilih(?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        $customer = User::query()
            ->where('role', UserRole::Customer)
            ->with(['vehicles' => fn ($query) => $query->with('carModel:id,name,series_code')
                ->orderByDesc('is_primary')
                ->orderBy('plate_full')])
            ->find($id);

        if ($customer === null) {
            return null;
        }

        return [
            ...$this->ringkasanPelanggan($customer),
            'vehicles' => $customer->vehicles->map(BookingPresenter::vehicleOption(...))->all(),
        ];
    }

    /**
     * Pencarian pelanggan lewat nama, email, atau nomor WhatsApp (docs/07 §A2).
     *
     * Dibatasi 10 baris: ini kotak pencarian di atas form, bukan daftar
     * pelanggan — daftar lengkapnya ada di /admin/customers dan dipaginasi.
     *
     * @return list<array<string, mixed>>
     */
    private function cariPelanggan(string $kata): array
    {
        // Nomor diketik dengan bentuk apa pun, tersimpan sebagai 62… — dicari
        // dengan bentuk tersimpannya. Kata tanpa angka menormalisasi menjadi
        // string kosong, dan `like '%%'` akan mencocokkan seluruh baris; karena
        // itu klausanya baru dipasang bila memang ada angkanya.
        $nomor = PhoneNumber::normalize($kata);

        return User::query()
            ->where('role', UserRole::Customer)
            ->where(function (Builder $query) use ($kata, $nomor): void {
                $query->where('name', 'like', '%'.$kata.'%')
                    ->orWhere('email', 'like', '%'.$kata.'%')
                    ->when($nomor !== '', fn (Builder $q) => $q->orWhere('phone_wa', 'like', '%'.$nomor.'%'));
            })
            ->withCount('vehicles')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (User $customer): array => [
                ...$this->ringkasanPelanggan($customer),
                'vehicles_count' => $customer->vehicles_count,
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function ringkasanPelanggan(User $customer): array
    {
        return [
            'id' => $customer->getKey(),
            'name' => $customer->name,
            'email' => $customer->email,
            'phone_wa' => $customer->phone_wa,
            'is_active' => $customer->is_active,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function paketAktif(): array
    {
        return ServicePackage::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'category', 'description', 'applicable_series', 'estimated_duration_minutes', 'price', 'is_free'])
            ->map(BookingPresenter::packageOption(...))
            ->all();
    }

    /**
     * Pilihan saringan paket — termasuk yang sudah nonaktif, karena booking
     * lamanya masih ada dan tetap perlu bisa dicari.
     *
     * @return list<array{value: string, label: string}>
     */
    private function pilihanPaket(): array
    {
        return ServicePackage::query()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn (ServicePackage $package): array => ['value' => (string) $package->id, 'label' => $package->name])
            ->all();
    }

    /**
     * Pilihan saringan advisor penanggung jawab.
     *
     * @return list<array{value: string, label: string}>
     */
    private function pilihanAdvisor(): array
    {
        return User::query()
            ->whereIn('role', UserRole::staffValues())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $staff): array => ['value' => (string) $staff->getKey(), 'label' => $staff->name])
            ->all();
    }
}
