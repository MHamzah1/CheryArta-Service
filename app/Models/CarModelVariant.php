<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $car_model_id
 * @property string $name
 * @property bool $is_active
 */
class CarModelVariant extends Model
{
    /** @use HasFactory<\Database\Factories\CarModelVariantFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'car_model_id',
        'name',
        'price',
        'specs',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'specs' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<CarModel, $this> */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
