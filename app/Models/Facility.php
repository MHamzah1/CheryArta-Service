<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string|null $image_url
 * @property bool $is_active
 */
class Facility extends Model
{
    /** @use HasFactory<\Database\Factories\FacilityFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Kolom fasilitas yang dicatat A12 (docs/07 §A12).
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['title', 'description', 'image_url', 'is_active', 'sort_order'];
    }

    /** @var list<string> */
    protected $fillable = [
        'title',
        'description',
        'image_public_id',
        'image_url',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<$this>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
