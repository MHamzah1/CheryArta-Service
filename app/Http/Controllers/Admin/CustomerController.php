<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CustomerAccountService;
use App\Support\BookingPresenter;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A6 — Customer (docs/07-modul-admin.md §A6, roadmap 1.5.6).
 *
 * Seluruh staf boleh melihat; hanya Super Admin yang boleh menonaktifkan akun
 * dan mereset password (matriks §A14). Pembatasan itu ditegakkan Policy, bukan
 * dengan menyembunyikan tombolnya.
 *
 * Tidak ada aksi hapus permanen di modul ini — riwayat servis dibutuhkan untuk
 * garansi kendaraan.
 */
class CustomerController extends Controller
{
    public function index(SearchRequest $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $kata = $request->keyword();

        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->when($kata !== null, fn (Builder $query) => $this->cari($query, (string) $kata))
            ->withCount(['vehicles', 'bookings'])
            // Servis terakhir = booking yang benar-benar selesai. Booking yang
            // dibatalkan bukan servis, dan menampilkannya di kolom ini akan
            // membuat advisor menyangka unitnya baru saja masuk.
            ->withMax(
                ['bookings as last_service_date' => fn (Builder $query) => $query->where('status', BookingStatus::Completed)],
                'booking_date',
            )
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $customer): array => [
                'id' => $customer->getKey(),
                'name' => $customer->name,
                'email' => $customer->email,
                'phone_wa' => $customer->phone_wa,
                'is_active' => $customer->is_active,
                'vehicles_count' => $customer->vehicles_count,
                'bookings_count' => $customer->bookings_count,
                // Kolom hasil `withMax` beralias: hanya ada bila kuerinya
                // memintanya, jadi dibaca lewat getAttribute() alih-alih
                // dijanjikan sebagai properti model yang selalu tersedia.
                'last_service_date' => $customer->getAttribute('last_service_date'),
            ]);

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'filters' => ['cari' => $kata],
        ]);
    }

    public function show(Request $request, User $customer): Response
    {
        Gate::authorize('view', $customer);

        $customer->load(['vehicles' => fn ($query) => $query->with('carModel:id,name')
            ->withCount('bookings')
            ->orderByDesc('is_primary')
            ->orderBy('plate_full')]);

        $bookings = $customer->bookings()
            ->with(['user:id,name', 'vehicle.carModel:id,name', 'servicePackage:id,name', 'handledBy:id,name'])
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_time')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(BookingPresenter::adminRow(...));

        return Inertia::render('admin/customers/show', [
            'customer' => [
                'id' => $customer->getKey(),
                'name' => $customer->name,
                'email' => $customer->email,
                'phone_wa' => $customer->phone_wa,
                'address' => $customer->address,
                'is_active' => $customer->is_active,
                'must_reset_password' => $customer->must_reset_password,
                'created_at' => $customer->created_at?->toIso8601String(),
                'last_login_at' => $customer->last_login_at?->toIso8601String(),
            ],
            'vehicles' => $customer->vehicles->map(fn (Vehicle $vehicle): array => [
                'id' => $vehicle->getKey(),
                'plate_full' => $vehicle->plate_full,
                'display_model' => $vehicle->display_model,
                'year' => $vehicle->year,
                'color' => $vehicle->color,
                'last_odometer' => $vehicle->last_odometer,
                'is_primary' => $vehicle->is_primary,
                'bookings_count' => $vehicle->bookings_count,
            ])->all(),
            'bookings' => $bookings,
            'stats' => $this->ringkasan($customer),

            // Dihitung server lewat UserPolicy dan dikirim sebagai boolean —
            // advisor tidak melihat tombolnya, DAN ditolak bila menembak
            // rutenya langsung (.claude/rules/20 & 50).
            'canToggleActive' => $request->user()->can('toggleActive', $customer),
            'canResetPassword' => $request->user()->can('resetPassword', $customer),
        ]);
    }

    /** Nonaktifkan / aktifkan kembali akun — Super Admin saja. */
    public function toggleActive(User $customer, CustomerAccountService $accounts): RedirectResponse
    {
        Gate::authorize('toggleActive', $customer);

        $aktif = $accounts->toggleActive($customer);

        return back()->with('success', $aktif
            ? "Akun {$customer->name} diaktifkan kembali dan bisa masuk lagi."
            : "Akun {$customer->name} dinonaktifkan. Data servisnya tetap tersimpan.");
    }

    /**
     * Reset password — Super Admin saja.
     *
     * Password sementaranya tampil SEKALI di pesan sukses, karena tanpa
     * notifikasi email (keputusan #4) tidak ada jalur lain untuk
     * menyampaikannya (docs/09 §9.2).
     */
    public function resetPassword(User $customer, CustomerAccountService $accounts): RedirectResponse
    {
        Gate::authorize('resetPassword', $customer);

        $sementara = $accounts->resetPassword($customer);

        return back()->with(
            'info',
            "Password sementara untuk {$customer->name}: {$sementara} — sampaikan lewat WhatsApp. "
            .'Pesan ini hanya muncul sekali dan tidak tersimpan di mana pun.',
        );
    }

    /**
     * Pencarian pelanggan lewat nama, email, atau nomor WhatsApp.
     *
     * @param  Builder<User>  $query
     */
    private function cari(Builder $query, string $kata): void
    {
        // Nomor tersimpan ternormalisasi (62…), sehingga yang dicari pun harus
        // bentuk itu — mengetik "0812…" tetap menemukannya. Kata yang sama
        // sekali tidak mengandung angka menormalisasi menjadi string kosong,
        // dan klausa `like '%%'` yang dihasilkannya akan mencocokkan SELURUH
        // baris — saringan yang diam-diam berhenti menyaring.
        $nomor = PhoneNumber::normalize($kata);

        $query->where(function (Builder $inner) use ($kata, $nomor): void {
            $inner->where('name', 'like', '%'.$kata.'%')
                ->orWhere('email', 'like', '%'.$kata.'%')
                ->when($nomor !== '', fn (Builder $q) => $q->orWhere('phone_wa', 'like', '%'.$nomor.'%'));
        });
    }

    /**
     * Ringkasan yang dibaca advisor sebelum menerima telepon.
     *
     * Total nilai invoice sengaja BELUM ada: tabel invoice baru lahir di
     * F2.3 (docs/10). Menampilkan "Rp 0" untuk sesuatu yang belum dihitung
     * lebih menyesatkan daripada tidak menampilkannya.
     *
     * @return array<string, int|string|null>
     */
    private function ringkasan(User $customer): array
    {
        return [
            'total_bookings' => $customer->bookings()->count(),
            'completed_bookings' => $customer->bookings()->where('status', BookingStatus::Completed)->count(),
            'upcoming_bookings' => $customer->bookings()->upcoming()->count(),
            'last_service_date' => $customer->bookings()
                ->where('status', BookingStatus::Completed)
                ->max('booking_date'),
        ];
    }
}
