<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WhatsAppTemplateKey;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Teks pesan WhatsApp yang bisa disunting Super Admin (docs/08 §8.4).
 *
 * @property int $id
 * @property WhatsAppTemplateKey $key
 * @property string $name
 * @property string $body
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WhatsAppTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\WhatsAppTemplateFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Isi pesan yang dibaca pelanggan adalah hal yang paling perlu punya
     * jejak: satu kalimat keliru menyentuh setiap orang yang dikabari sesudah
     * itu, dan tanpa log tidak ada cara mengetahui kapan ia berubah.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['name', 'body', 'is_active'];
    }

    /**
     * Eksplisit: Laravel menurunkan `WhatsAppTemplate` menjadi
     * `whats_app_templates` — dua kata, karena "App" dibaca sebagai kata
     * tersendiri. Tabelnya bernama `whatsapp_templates` sesuai docs/04 §4.2.
     */
    protected $table = 'whatsapp_templates';

    /**
     * `key` sengaja TIDAK fillable. Himpunan kuncinya ditentukan
     * App\Enums\WhatsAppTemplateKey dan hanya diisi seeder — layar admin
     * menyunting isinya, tidak pernah menambah atau mengganti kunci
     * (keputusan grill Q5).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'body',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'key' => WhatsAppTemplateKey::class,
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Seluruh template, urut mengikuti perjalanan sebuah booking — bukan abjad
     * dan bukan urutan id.
     *
     * Diurutkan di PHP, bukan lewat `FIELD()`/`CASE` di SQL: uji berjalan di
     * atas SQLite sedangkan produksi memakai MySQL, dan pengurutan khas satu
     * mesin adalah persis jenis cacat yang lulus di uji lalu diam-diam gagal di
     * produksi (lihat catatan SlotTime di roadmap F1.4). Barisnya hanya tujuh.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function ordered(): \Illuminate\Support\Collection
    {
        $urutan = array_flip(WhatsAppTemplateKey::values());

        return self::query()
            ->get()
            ->sortBy(fn (self $template): int => $urutan[$template->key->value] ?? PHP_INT_MAX)
            ->values();
    }
}
