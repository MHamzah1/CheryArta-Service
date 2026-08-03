<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreVehicleRequest;
use App\Http\Requests\Customer\UpdateVehicleRequest;
use App\Models\CarModel;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        // Diambil lewat relasi, bukan Vehicle::where('user_id', …) — pola ini
        // membuat kendaraan orang lain mustahil terambil sejak awal
        // (.claude/rules/50-keamanan.md).
        $vehicles = $request->user()
            ->vehicles()
            ->with('carModel:id,name')
            ->orderByDesc('is_primary')
            ->orderBy('plate_full')
            ->get();

        return Inertia::render('kendaraan/index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('kendaraan/create', [
            'carModels' => $this->pilihanModel(),
        ]);
    }

    public function store(StoreVehicleRequest $request, VehicleService $vehicles): RedirectResponse
    {
        $vehicle = $vehicles->create($request->user(), $request->validated());

        return redirect()
            ->route('customer.vehicles.index')
            ->with('success', "Kendaraan {$vehicle->plate_full} berhasil ditambahkan.");
    }

    public function edit(Vehicle $vehicle): Response
    {
        Gate::authorize('update', $vehicle);

        return Inertia::render('kendaraan/edit', [
            'vehicle' => $vehicle,
            'carModels' => $this->pilihanModel(),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, VehicleService $vehicles): RedirectResponse
    {
        $vehicles->update($vehicle, $request->validated());

        return redirect()
            ->route('customer.vehicles.index')
            ->with('success', "Kendaraan {$vehicle->plate_full} berhasil diperbarui.");
    }

    public function destroy(Vehicle $vehicle, VehicleService $vehicles): RedirectResponse
    {
        Gate::authorize('delete', $vehicle);

        $plate = $vehicle->plate_full;
        $vehicles->delete($vehicle);

        return redirect()
            ->route('customer.vehicles.index')
            ->with('success', "Kendaraan {$plate} berhasil dihapus.");
    }

    /** @return \Illuminate\Support\Collection<int, CarModel> */
    private function pilihanModel()
    {
        return CarModel::query()
            ->active()
            ->ordered()
            ->get(['id', 'name']);
    }
}
