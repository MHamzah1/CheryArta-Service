<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pembatalan invoice — Super Admin saja (docs/07 §A8, docs/09 §9.3).
 *
 * Alasan WAJIB, dan itu bukan formalitas: void adalah satu-satunya cara
 * mengoreksi invoice yang sudah terbit (docs/05 §5.6), sehingga baris void yang
 * tanpa keterangan meninggalkan tagihan hilang yang tak seorang pun bisa
 * jelaskan enam bulan kemudian. Alasannya ikut tercatat di activity log
 * (docs/09 §9.7).
 */
class VoidInvoiceRequest extends FormRequest
{
    /**
     * Otorisasi diperiksa DI SINI, bukan hanya di controller.
     *
     * Laravel menjalankan `authorize()` sebelum `rules()`, dan urutan itu yang
     * menentukan jawaban: advisor yang menekan "Batalkan" tanpa mengisi alasan
     * harus menerima **403**, bukan galat validasi "alasan wajib diisi".
     * Pesan kedua itu mengundangnya mencoba lagi dengan alasan terisi — untuk
     * sesuatu yang memang bukan haknya.
     *
     * `Gate::authorize` di controller tetap dipertahankan sebagai lapis kedua
     * (.claude/rules/50 #1).
     */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice && $this->user()?->can('void', $invoice) === true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min' => 'Tuliskan alasan yang bisa dimengerti orang lain — minimal 5 karakter.',
            'reason.max' => 'Alasan paling panjang 255 karakter.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reason' => 'alasan pembatalan'];
    }
}
