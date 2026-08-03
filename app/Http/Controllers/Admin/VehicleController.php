<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchRequest;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A6 — Kendaraan terdaftar (docs/07 §A6, roadmap 1.5.6).
 *
 * Menjawab pertanyaan yang paling sering muncul di meja servis: "unit ini
 * terakhir servis kapan?". Karena itu daftarnya dicari lewat plat, dan
 * kolom terpentingnya adalah tanggal servis terakhir — bukan tanggal
 * pendaftaran unit.
 *
 * Hanya membaca. Penyuntingan kendaraan tetap milik pemiliknya
 * (VehiclePolicy::update) — advisor yang mengoreksi plat orang lain diam-diam
 * adalah jalur perubahan data tanpa jejak.
 */
class VehicleController extends Controller
{
    public function index(SearchRequest $request): Response
    {
        Gate::authorize('viewAny', Vehicle::class);

        $kata = $request->keyword();

        $vehicles = Vehicle::query()
            ->with(['user:id,name,phone_wa', 'carModel:id,name'])
            ->when($kata !== null, fn (Builder $query) => $this->cari($query, (string) $kata))
            ->withCount(['bookings as services_count' => fn (Builder $query) => $query->where('status', BookingStatus::Completed)])
            ->withMax(
                ['bookings as last_service_date' => fn (Builder $query) => $query->where('status', BookingStatus::Completed)],
                'booking_date',
            )
            ->orderBy('plate_full')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle): array => [
                'id' => $vehicle->getKey(),
                'plate_full' => $vehicle->plate_full,
                'display_model' => $vehicle->display_model,
                'year' => $vehicle->year,
                'color' => $vehicle->color,
                'last_odometer' => $vehicle->last_odometer,
                // Kolom hasil `withCount`/`withMax` beralias: hanya ada bila
                // kuerinya memintanya, jadi dibaca lewat getAttribute().
                'services_count' => $vehicle->getAttribute('services_count'),
                'last_service_date' => $vehicle->getAttribute('last_service_date'),
                'owner' => [
                    'id' => $vehicle->user_id,
                    'name' => $vehicle->user->name,
                ],
            ]);

        return Inertia::render('admin/vehicles/index', [
            'vehicles' => $vehicles,
            'filters' => ['cari' => $kata],
        ]);
    }

    /**
     * Pencarian lewat plat, model, atau nama pemilik.
     *
     * Plat diketik orang dengan spasi ("B 1234 ABC") tetapi tersimpan dengan
     * tanda hubung — tanpa penyeragaman ini, cara pencarian yang paling wajar
     * justru tidak pernah menemukan apa pun.
     *
     * @param  Builder<Vehicle>  $query
     */
    private function cari(Builder $query, string $kata): void
    {
        $plat = Str::upper(preg_replace('/[\s-]+/', '-', trim($kata)) ?? $kata);

        $query->where(function (Builder $inner) use ($kata, $plat): void {
            $inner->where('plate_full', 'like', '%'.$plat.'%')
                ->orWhere('model_name_manual', 'like', '%'.$kata.'%')
                ->orWhereHas('carModel', fn (Builder $model) => $model->where('name', 'like', '%'.$kata.'%'))
                ->orWhereHas('user', fn (Builder $owner) => $owner->where('name', 'like', '%'.$kata.'%'));
        });
    }
}
