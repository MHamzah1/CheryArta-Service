<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pencatatan A12 dengan aturan proyek ini (docs/07 §A12).
 *
 * Membungkus trait bawaan paket supaya tiga hal berlaku seragam di seluruh
 * model, tanpa disalin ke masing-masing:
 *
 * 1. **Hanya kolom yang berubah** yang dicatat, bukan seluruh baris — log yang
 *    memuat 20 kolom tak berubah membuat perubahan yang sesungguhnya
 *    tenggelam.
 * 2. **Atribut sensitif dikecualikan.** `password`, `remember_token`, dan
 *    token apa pun tidak boleh masuk log (.claude/rules/50 — "log jangan
 *    memuat password, token, atau isi pesan pribadi"). Activity log mencatat
 *    nilai sebelum DAN sesudah, jadi kolom yang lolos akan bocor dua kali.
 * 3. **Log kosong tidak disimpan** — menyentuh tombol simpan tanpa mengubah
 *    apa pun bukan peristiwa yang perlu dicatat.
 *
 * Model yang memakainya cukup mendeklarasikan `$activityLogAttributes`, dan
 * bila perlu menambah pengecualian lewat `$activityLogExcept`.
 */
trait RecordsActivity
{
    use LogsActivity;

    /**
     * Selalu dikecualikan, di model mana pun. Ditulis di sini supaya tidak ada
     * model yang lupa mengecualikannya sendiri — dan supaya menambahkannya
     * tanpa sengaja ke daftar model tetap tidak berakibat apa-apa.
     *
     * @var list<string>
     */
    private const SELALU_DIKECUALIKAN = [
        'password',
        'remember_token',
        'must_reset_password',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Kolom yang dicatat untuk model ini.
     *
     * Method abstrak, bukan properti: properti trait yang ditimpa properti
     * model adalah komposisi yang tidak sah di PHP, dan memakai
     * `property_exists` membuat kontraknya tidak terlihat sama sekali. Dengan
     * bentuk ini, model yang memakai trait TIDAK BISA lupa menyebutkan kolom
     * apa yang ia catat.
     *
     * @return list<string>
     */
    abstract protected function activityLogAttributes(): array;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_values(array_diff($this->activityLogAttributes(), self::SELALU_DIKECUALIKAN)))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename(static::class));
    }
}
