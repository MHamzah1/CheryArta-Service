<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris timeline status booking.
 *
 * Baris di sini ditulis sekali dan tidak pernah diubah atau dihapus
 * (.claude/rules/30-database.md) — karena itu tidak ada `updated_at`.
 *
 * @property int $id
 * @property int $booking_id
 * @property BookingStatus|null $from_status
 * @property BookingStatus $to_status
 * @property int|null $changed_by
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read Booking $booking
 * @property-read User|null $changedBy
 */
class BookingStatusHistory extends Model
{
    /** @use HasFactory<\Database\Factories\BookingStatusHistoryFactory> */
    use HasFactory;

    /** Baris audit tidak pernah disunting, jadi kolomnya pun tidak ada. */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => BookingStatus::class,
            'to_status' => BookingStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** Null bila perubahannya dilakukan sistem. @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
