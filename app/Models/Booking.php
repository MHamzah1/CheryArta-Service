<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Support\SlotTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu pesanan servis.
 *
 * Model ini TIDAK menghitung kuota, tidak menerbitkan kode booking, dan tidak
 * mengubah status sendiri — semuanya milik SlotService dan BookingService
 * (.claude/rules/10-backend-laravel.md).
 *
 * @property int $id
 * @property string $booking_code
 * @property int $user_id
 * @property int $vehicle_id
 * @property int $service_package_id
 * @property CarbonImmutable $booking_date
 * @property string $booking_time
 * @property BookingStatus $status
 * @property BookingSource $source
 * @property int|null $odometer
 * @property string|null $complaint
 * @property string|null $admin_note
 * @property int|null $handled_by
 * @property string|null $cancel_reason
 * @property int|null $rescheduled_from_id
 * @property CarbonImmutable|null $estimated_finish_at
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property-read CarbonImmutable $booking_datetime
 * @property-read User $user
 * @property-read Vehicle $vehicle
 * @property-read ServicePackage $servicePackage
 * @property-read User|null $handledBy
 * @property-read Booking|null $rescheduledFrom
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingStatusHistory> $statusHistories
 */
class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory, SoftDeletes;

    /**
     * `booking_code`, `status`, dan `user_id` sengaja TIDAK fillable —
     * ketiganya ditentukan server. Menerima salah satunya dari request adalah
     * larangan mutlak di .claude/rules/50-keamanan.md.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'service_package_id',
        'booking_date',
        'booking_time',
        'source',
        'odometer',
        'complaint',
        'admin_note',
        'handled_by',
        'cancel_reason',
        'rescheduled_from_id',
        'estimated_finish_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            // Format ditulis eksplisit. Tanpa itu Laravel menyimpannya sebagai
            // `2026-08-04 00:00:00`, yang di MySQL dipangkas diam-diam oleh
            // kolom DATE tetapi di SQLite (dipakai uji) tersimpan apa adanya —
            // sehingga `where('booking_date', '2026-08-04')` tidak pernah cocok.
            // Alasan yang sama dengan App\Support\SlotTime untuk kolom jam.
            'booking_date' => 'immutable_date:Y-m-d',
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'odometer' => 'integer',
            'confirmed_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'estimated_finish_at' => 'immutable_datetime',
        ];
    }

    /** URL customer memakai kode booking, bukan id yang bisa ditebak berurutan. */
    public function getRouteKeyName(): string
    {
        return 'booking_code';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<ServicePackage, $this> */
    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    /** Service advisor penanggung jawab. @return BelongsTo<User, $this> */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** Booking yang digantikan booking ini. @return BelongsTo<Booking, $this> */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    /** @return HasMany<BookingStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    /**
     * Jam slot selalu tersimpan dalam bentuk kanonis `H:i:s`.
     *
     * Tanpa ini, `'09:00'` yang datang dari config/form akan tersimpan apa
     * adanya di SQLite tetapi menjadi `'09:00:00'` di MySQL — dan kueri kuota
     * yang membandingkan keduanya lulus di uji lalu diam-diam gagal di
     * produksi (lihat App\Support\SlotTime).
     */
    protected function bookingTime(): Attribute
    {
        return Attribute::set(fn (string $value): string => SlotTime::normalize($value));
    }

    /**
     * Tanggal dan jam disatukan kembali menjadi satu titik waktu di zona
     * Asia/Jakarta. Sistem lama memakai UTC di sini dan meleset satu hari saat
     * diakses dini hari (temuan B7).
     */
    protected function bookingDatetime(): Attribute
    {
        return Attribute::get(fn (): CarbonImmutable => CarbonImmutable::parse(
            $this->booking_date->format('Y-m-d').' '.substr($this->booking_time, 0, 5),
            config('booking.timezone'),
        ));
    }

    /** Booking yang masih menempati kuota slotnya. @param  Builder<$this>  $query */
    public function scopeOccupyingQuota(Builder $query): void
    {
        $query->whereNotIn('status', BookingStatus::quotaReleasingValues());
    }

    /** @param  Builder<$this>  $query */
    public function scopeOnSlot(Builder $query, string $date, string $time): void
    {
        $query->where('booking_date', $date)->where('booking_time', SlotTime::normalize($time));
    }

    /** Belum lewat dan belum berakhir. @param  Builder<$this>  $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::activeValues())
            ->whereDate('booking_date', '>=', now(config('booking.timezone'))->toDateString());
    }
}
