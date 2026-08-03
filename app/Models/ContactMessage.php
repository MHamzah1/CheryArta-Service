<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property string|null $ip_address
 */
class ContactMessage extends Model
{
    /** @use HasFactory<\Database\Factories\ContactMessageFactory> */
    use HasFactory;

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
