<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun awal — docs/04-skema-database.md §4.4.
 *
 * Idempoten (updateOrCreate), aman dijalankan berulang.
 *
 * PERINGATAN PRODUKSI: password di bawah hanya untuk pengembangan lokal.
 * Sebelum rilis, buat akun Super Admin dengan password kuat dan jangan
 * menjalankan seeder ini — lihat daftar periksa docs/09 §9.10.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cheryarta.test'],
            [
                'name' => 'Super Admin',
                'phone_wa' => '6289540454690',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'advisor@cheryarta.test'],
            [
                'name' => 'Andre Service Advisor',
                'phone_wa' => '6281234567890',
                'password' => Hash::make('password'),
                'role' => UserRole::ServiceAdvisor,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        // Customer contoh hanya untuk pengembangan lokal.
        if (app()->isLocal()) {
            User::updateOrCreate(
                ['email' => 'rani@example.test'],
                [
                    'name' => 'Rani Saputri',
                    'phone_wa' => '6281298765432',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Customer,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
