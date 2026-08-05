<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppTemplateKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log klik-to-chat (docs/04 §4.2, docs/08 §8.8).
 *
 * Tabel audit: barisnya tidak pernah dihapus dan tidak pernah dihitung ulang.
 * Ia menjawab "pesan apa yang dikeluarkan sistem" — bukan "kalimat apa yang
 * sampai ke pelanggan", karena advisor tetap bisa menyuntingnya di kotak ketik
 * WhatsApp sebelum menekan kirim (keputusan grill Q2).
 *
 * @property int $id
 * @property int $booking_id
 * @property WhatsAppTemplateKey $template_key
 * @property string $recipient_phone
 * @property string $rendered_message
 * @property int|null $generated_by
 * @property WhatsAppMessageStatus $status
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read Booking $booking
 * @property-read User|null $generatedBy
 */
class WhatsAppMessage extends Model
{
    /** @use HasFactory<\Database\Factories\WhatsAppMessageFactory> */
    use HasFactory;

    /** Eksplisit — tanpa ini Laravel mencari `whats_app_messages` (docs/04 §4.2). */
    protected $table = 'whatsapp_messages';

    /**
     * Seluruh kolom ditentukan server lewat WhatsAppMessageService — tidak
     * satu pun datang dari request. `rendered_message` khususnya: menerimanya
     * dari klien akan menjadikan kolom audit ini isian pengguna
     * (.claude/rules/50).
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'template_key',
        'recipient_phone',
        'rendered_message',
        'generated_by',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'template_key' => WhatsAppTemplateKey::class,
            'status' => WhatsAppMessageStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Pesan yang sudah selesai diurus — terkirim atau sengaja dilewati.
     * Keduanya mengeluarkan booking dari kartu "Belum dikabari".
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSettled(Builder $query): void
    {
        $query->whereIn('status', WhatsAppMessageStatus::settledValues());
    }
}
