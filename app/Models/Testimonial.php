<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $customer_name
 * @property string|null $car_model
 * @property int $rating
 * @property string $content
 * @property bool $is_published
 */
class Testimonial extends Model
{
    /** @use HasFactory<\Database\Factories\TestimonialFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Kolom testimoni yang dicatat A12 (docs/07 §A12).
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['customer_name', 'car_model', 'rating', 'content', 'is_published', 'sort_order'];
    }

    /** @var list<string> */
    protected $fillable = [
        'customer_name',
        'car_model',
        'rating',
        'content',
        'is_published',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Halaman publik hanya boleh membaca lewat scope ini — testimoni yang
     * belum disetujui admin tidak pernah bocor ke pengunjung.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param  Builder<$this>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
