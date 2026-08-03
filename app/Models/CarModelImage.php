<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\CloudinaryUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $car_model_id
 * @property string $public_id
 * @property string $url
 * @property string $alt
 * @property bool $is_primary
 * @property int $sort_order
 * @property-read string $thumbnail_url
 */
class CarModelImage extends Model
{
    /** @use HasFactory<\Database\Factories\CarModelImageFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'car_model_id',
        'public_id',
        'url',
        'alt',
        'is_primary',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<CarModel, $this> */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    /**
     * Versi kecil untuk kartu katalog. Pengubahan ukuran dikerjakan Cloudinary
     * lewat URL, sehingga tidak ada pemrosesan gambar di server (docs/12 §12.5).
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(
            fn (): string => CloudinaryUrl::withWidth(
                $this->url,
                (int) config('cloudinary.widths.thumbnail'),
            ),
        );
    }
}
