<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServicePackageCategory;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property ServicePackageCategory $category
 * @property array<int, string>|null $applicable_series
 * @property int $estimated_duration_minutes
 * @property bool $is_free
 * @property bool $is_active
 */
class ServicePackage extends Model
{
    /** @use HasFactory<\Database\Factories\ServicePackageFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Kolom paket layanan yang dicatat A12 (docs/07 §A12). Harga ikut dicatat: perubahannya berpengaruh langsung ke estimasi biaya pelanggan.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['code', 'name', 'category', 'applicable_series', 'estimated_duration_minutes', 'price', 'is_free', 'is_active', 'sort_order'];
    }

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'applicable_series',
        'estimated_duration_minutes',
        'price',
        'is_free',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => ServicePackageCategory::class,
            'applicable_series' => 'array',
            'estimated_duration_minutes' => 'integer',
            'price' => 'decimal:2',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Apakah paket ini sudah dipakai baris lain.
     *
     * Booking yang sudah dihapus lunak ikut dihitung: barisnya masih ada dan
     * masih menunjuk ke paket ini, sehingga menghapus paket akan membuat
     * riwayat servis lama kehilangan keterangan pekerjaannya.
     */
    public function isReferenced(): bool
    {
        return $this->bookings()->withTrashed()->exists();
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<$this>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Paket yang berlaku untuk satu seri kendaraan. `applicable_series` null
     * berarti berlaku umum, jadi ikut terambil.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForSeries(Builder $query, ?string $seriesCode): void
    {
        if ($seriesCode === null) {
            $query->whereNull('applicable_series');

            return;
        }

        $query->where(function (Builder $inner) use ($seriesCode): void {
            $inner->whereNull('applicable_series')
                ->orWhereJsonContains('applicable_series', $seriesCode);
        });
    }
}
