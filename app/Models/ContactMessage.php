<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Support\PhoneNumber;
use App\Support\WhatsAppLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;

/**
 * Pesan dari form kontak publik.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $subject
 * @property string $message
 * @property bool $is_read
 * @property int|null $read_by
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property string|null $ip_address
 * @property-read string|null $whatsapp_reply_url
 * @property-read User|null $readBy
 */
class ContactMessage extends Model
{
    /** @use HasFactory<\Database\Factories\ContactMessageFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Hanya perubahan status baca yang dicatat. Isi pesannya TIDAK: ia data pribadi pengirim, dan hapus di sini permanen (keputusan grill #4) - yang perlu punya jejak adalah SIAPA yang menghapus, bukan salinan isinya di tabel kedua.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return ['is_read', 'read_by'];
    }

    /**
     * `is_read`, `read_by`, dan `ip_address` sengaja tidak fillable —
     * ketiganya ditentukan server, bukan pengirim form
     * (.claude/rules/50-keamanan.md).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    /** Dinormalisasi 62… seperti users.phone_wa, agar bisa dipakai wa.me. */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null || $value === ''
                ? null
                : PhoneNumber::normalize($value),
        );
    }

    /**
     * Tautan balasan WhatsApp, atau null bila pengirim tidak mencantumkan nomor.
     *
     * Bukan bagian dari alur draft booking: tidak ada template, tidak ada
     * placeholder, dan sengaja TIDAK dicatat di `whatsapp_messages` — kolom
     * `booking_id` di sana tidak nullable, dan pesan kontak memang tidak punya
     * booking (keputusan grill Q9). Yang tetap sama: nomornya dipakai dalam
     * bentuk ternormalisasi yang tersimpan, bukan apa pun yang diketik
     * pengirim (.claude/rules/50 #7).
     */
    protected function whatsappReplyUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->phone === null || $this->phone === '') {
                    return null;
                }

                return WhatsAppLink::to($this->phone, sprintf(
                    'Halo %s, terima kasih telah menghubungi %s. Kami menanggapi pesan Anda mengenai "%s".',
                    $this->name,
                    Config::string('company.name'),
                    $this->subject,
                ));
            },
        );
    }

    /** @return BelongsTo<User, $this> */
    public function readBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /** @param  Builder<$this>  $query */
    public function scopeUnread(Builder $query): void
    {
        $query->where('is_read', false);
    }
}
