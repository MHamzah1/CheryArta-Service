<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CarCategory;
use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu model mobil di katalog publik.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property CarCategory $category
 * @property FuelType $fuel_type
 * @property string|null $series_code
 * @property string|null $brochure_url
 * @property bool $is_active
 */
class CarModel extends Model
{
    /** @use HasFactory<\Database\Factories\CarModelFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'category',
        'fuel_type',
        'series_code',
        'price_start',
        'short_description',
        'description',
        'specs',
        'brochure_public_id',
        'brochure_url',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => CarCategory::class,
            'fuel_type' => FuelType::class,
            'price_start' => 'decimal:2',
            'specs' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** URL katalog memakai slug, bukan id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<CarModelVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(CarModelVariant::class);
    }

    /** @return HasMany<CarModelImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(CarModelImage::class);
    }

    /**
     * Gambar utama untuk kartu katalog. Dipisah dari `images` supaya daftar
     * katalog cukup memuat satu gambar per model alih-alih seluruhnya.
     *
     * @return HasOne<CarModelImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(CarModelImage::class)->where('is_primary', true);
    }

    /** @return HasMany<Vehicle, $this> */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
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
}
