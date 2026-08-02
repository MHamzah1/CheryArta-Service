<?php

declare(strict_types=1);

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Seluruh uji di direktori Feature memakai Tests\TestCase dan menyegarkan
| database (SQLite in-memory, lihat phpunit.xml).
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
|
| Pembuat pengguna per peran, supaya uji otorisasi ringkas dan konsisten.
| Lihat matriks hak akses di docs/09-keamanan-hak-akses.md §9.3.
|
*/

function superAdmin(array $attributes = []): User
{
    return User::factory()->superAdmin()->create($attributes);
}

function serviceAdvisor(array $attributes = []): User
{
    return User::factory()->serviceAdvisor()->create($attributes);
}

function customer(array $attributes = []): User
{
    return User::factory()->create($attributes);
}
