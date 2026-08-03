<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Kendaraan milik customer.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $car_model_id
 * @property string|null $model_name_manual
 * @property string $plate_prefix
 * @property string $plate_number
 * @property string $plate_suffix
 * @property string $plate_full
 * @property int|null $year
 * @property int|null $last_odometer
 * @property bool $is_primary
 * @property-read string $display_model
 */
class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * `user_id` sengaja TIDAK fillable — pemiliknya ditentukan server lewat
     * relasi (`$user->vehicles()->create(...)`), tidak pernah dari request.
     * `plate_full` juga tidak: ia nilai turunan yang disusun di booted().
     *
     * @var list<string>
     */
    protected $fillable = [
        'car_model_id',
        'model_name_manual',
        'plate_prefix',
        'plate_number',
        'plate_suffix',
        'year',
        'color',
        'vin',
        'is_primary',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_odometer' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Plat selalu disimpan huruf besar dan `plate_full` selalu ikut
        // segmennya. Menyusunnya di satu tempat mencegah kedua bentuk itu
        // berbeda isi — hal yang mustahil dijaga bila setiap controller
        // menyusunnya sendiri.
        static::saving(function (self $vehicle): void {
            $vehicle->plate_prefix = Str::upper(trim($vehicle->plate_prefix));
            $vehicle->plate_suffix = Str::upper(trim($vehicle->plate_suffix));
            $vehicle->plate_number = trim($vehicle->plate_number);

            $vehicle->plate_full = self::composePlate(
                $vehicle->plate_prefix,
                $vehicle->plate_number,
                $vehicle->plate_suffix,
            );
        });
    }

    /**
     * Bentuk gabungan `B-1234-ABC`.
     *
     * Dipakai booted() saat menyimpan DAN oleh Form Request saat memeriksa
     * keunikan plat — keduanya wajib menghasilkan bentuk yang sama persis,
     * kalau tidak pemeriksaan keunikannya menilai string yang berbeda dari
     * yang akhirnya tersimpan.
     */
    public static function composePlate(string $prefix, string $number, string $suffix): string
    {
        return sprintf(
            '%s-%s-%s',
            Str::upper(trim($prefix)),
            trim($number),
            Str::upper(trim($suffix)),
        );
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<CarModel, $this> */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    /**
     * Nama model yang layak ditampilkan: dari katalog bila kendaraannya Chery,
     * dari ketikan pemilik bila bukan (pertanyaan terbuka Q3).
     */
    protected function displayModel(): Attribute
    {
        return Attribute::get(function (): string {
            // Bercabang pada foreign key-nya, bukan pada objek relasi: bila
            // `car_model_id` terisi, barisnya dijamin ada oleh foreign key —
            // `nullOnDelete` mengosongkan kolom ini, bukan meninggalkannya
            // menunjuk model yang sudah hilang.
            if ($this->car_model_id === null) {
                return $this->model_name_manual ?? 'Model tidak diketahui';
            }

            return $this->carModel->name;
        });
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopePrimary(Builder $query): void
    {
        $query->where('is_primary', true);
    }
}
