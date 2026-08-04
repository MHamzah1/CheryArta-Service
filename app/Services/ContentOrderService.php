<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan urutan hasil seret untuk konten landing (docs/07 §A10).
 *
 * Dipakai bersama oleh fasilitas, FAQ, dan testimoni. Ketiganya punya kolom
 * `sort_order` dan perilaku yang sama, jadi aturannya hidup di satu tempat.
 *
 * Dibungkus transaksi bukan karena kehati-hatian umum, melainkan karena
 * urutan yang tersimpan separuh lebih buruk daripada urutan lama: dua baris
 * bisa berakhir dengan `sort_order` yang sama, dan urutan tampilnya menjadi
 * tidak bisa ditebak sampai ada yang menyeret ulang.
 */
final readonly class ContentOrderService
{
    /**
     * Menulis ulang `sort_order` mengikuti urutan `$ids`.
     *
     * Id yang tidak ada di tabel sudah ditolak Form Request lebih dulu, jadi
     * di sini urusannya tinggal menulis.
     *
     * @param  class-string<Model>  $model
     * @param  list<int>  $ids
     */
    public function apply(string $model, array $ids): void
    {
        DB::transaction(function () use ($model, $ids): void {
            foreach ($ids as $urutan => $id) {
                $model::query()->whereKey($id)->update(['sort_order' => $urutan + 1]);
            }
        });
    }
}
