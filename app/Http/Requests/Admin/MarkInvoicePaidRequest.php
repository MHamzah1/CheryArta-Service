<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pencatatan pembayaran (docs/04 §4.2, docs/07 §A8).
 *
 * `payment_method` murni pencatatan — tidak ada payment gateway, tidak ada
 * verifikasi ke bank. `paid_at` tidak diterima dari request: yang berlaku
 * adalah kapan advisor menekan tombolnya, ditetapkan server.
 */
class MarkInvoicePaidRequest extends FormRequest
{
    /** Otorisasinya `markPaid` di controller — lihat Admin\InvoiceStatusController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(PaymentMethod::values())],
        ];
    }

    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::from((string) $this->validated('payment_method'));
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Pilih metode pembayaran lebih dahulu.',
            'payment_method.in' => 'Metode pembayaran tidak dikenali.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['payment_method' => 'metode pembayaran'];
    }
}
