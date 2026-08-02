<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Customer dan staf internal disimpan dalam satu tabel.
 *
 * CATATAN: MustVerifyEmail sengaja TIDAK dipakai — keputusan rancangan #4
 * meniadakan notifikasi email. Perlindungan akun sampah memakai rate limit
 * registrasi + kemampuan admin menonaktifkan akun (docs/03 §3.5).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone_wa
 * @property UserRole $role
 * @property string|null $address
 * @property bool $is_active
 * @property bool $must_reset_password
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * Ditulis eksplisit — `$guarded = []` dilarang
     * (.claude/rules/10-backend-laravel.md).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_wa',
        'password',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'must_reset_password' => 'boolean',
        ];
    }

    /**
     * Nomor WhatsApp selalu disimpan ternormalisasi (62xxxxxxxxxx), apa pun
     * bentuk yang diketik pengguna. Sistem lama menyimpannya apa adanya
     * termasuk tanda hubung, sehingga tidak bisa dipakai untuk tautan wa.me.
     */
    protected function phoneWa(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null || $value === ''
                ? null
                : PhoneNumber::normalize($value),
        );
    }

    /** Boleh mengakses panel internal /admin. */
    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }
}
