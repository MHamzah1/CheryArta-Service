<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\InvoiceItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Penyunting invoice (docs/07 §A8).
 *
 * Yang divalidasi hanya BENTUK masukannya. `subtotal` dan `total` sengaja tidak
 * ada di sini sama sekali — bukan divalidasi lalu diabaikan, melainkan tidak
 * pernah dibaca: keduanya dihitung InvoiceService dari `qty × unit_price`
 * (docs/05 §5.6). Menerima total dari browser adalah temuan invoice sistem lama
 * (.claude/rules/20).
 */
class UpdateInvoiceRequest extends FormRequest
{
    /** Otorisasinya `update` di controller — lihat Admin\InvoiceController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'items' => ['present', 'array', 'max:50'],
            'items.*.type' => ['required', Rule::in(InvoiceItemType::values())],
            'items.*.description' => ['required', 'string', 'max:200'],

            // decimal, bukan integer: 0,5 jam jasa harus bisa ditulis
            // (docs/04 §4.2). `gt:0` — baris berjumlah nol bukan baris.
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'max:9999', 'decimal:0,2'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],

            'discount' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Diskon tidak boleh melebihi subtotal — invoice bertotal negatif bukan
     * invoice (keputusan grill Q5).
     *
     * Dibandingkan dengan subtotal hasil hitungan SERVER dari item yang baru
     * saja dikirim, bukan dengan `subtotal` yang tersimpan (yang masih nilai
     * sebelum suntingan ini) dan bukan pula dengan angka dari browser.
     * InvoiceService tetap menjepitnya sekali lagi sebagai jaring terakhir —
     * pesan yang menjelaskan lahir di sini, keamanannya di sana.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $subtotal = $this->subtotalDariItem();
                $discount = (float) ($this->input('discount') ?? 0);

                if ($discount > $subtotal) {
                    $validator->errors()->add('discount', sprintf(
                        'Diskon tidak boleh melebihi subtotal (Rp %s).',
                        number_format($subtotal, 0, ',', '.'),
                    ));
                }
            },
        ];
    }

    /**
     * Bentuk yang diterima InvoiceService.
     *
     * @return array{items: list<array{type: string, description: string, qty: float, unit_price: float}>, discount: float, tax: float, notes: string|null}
     */
    public function payload(): array
    {
        /** @var list<array{type: string, description: string, qty: numeric-string|float|int, unit_price: numeric-string|float|int}> $items */
        $items = array_values($this->validated('items') ?? []);

        return [
            'items' => array_map(fn (array $item): array => [
                'type' => (string) $item['type'],
                'description' => (string) $item['description'],
                'qty' => (float) $item['qty'],
                'unit_price' => (float) $item['unit_price'],
            ], $items),
            'discount' => (float) ($this->validated('discount') ?? 0),
            'tax' => (float) ($this->validated('tax') ?? 0),
            'notes' => $this->validated('notes'),
        ];
    }

    private function subtotalDariItem(): float
    {
        $subtotal = 0.0;

        /** @var array<int, array{qty?: mixed, unit_price?: mixed}> $items */
        $items = $this->input('items', []);

        foreach ($items as $item) {
            $subtotal += round(((float) ($item['qty'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)), 2);
        }

        return round($subtotal, 2);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.max' => 'Satu invoice paling banyak memuat 50 baris.',
            'items.*.type.required' => 'Pilih jenis baris: jasa atau sparepart.',
            'items.*.type.in' => 'Jenis baris hanya bisa jasa atau sparepart.',
            'items.*.description.required' => 'Keterangan baris wajib diisi.',
            'items.*.description.max' => 'Keterangan paling panjang 200 karakter.',
            'items.*.qty.required' => 'Jumlah wajib diisi.',
            'items.*.qty.gt' => 'Jumlah harus lebih dari 0.',
            'items.*.qty.decimal' => 'Jumlah paling banyak 2 angka di belakang koma.',
            'items.*.unit_price.required' => 'Harga satuan wajib diisi.',
            'items.*.unit_price.min' => 'Harga satuan tidak boleh negatif.',
            'items.*.unit_price.decimal' => 'Harga satuan paling banyak 2 angka di belakang koma.',
            'discount.min' => 'Diskon tidak boleh negatif.',
            'tax.min' => 'Pajak tidak boleh negatif.',
            'notes.max' => 'Catatan paling panjang 2.000 karakter.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'items' => 'rincian',
            'discount' => 'diskon',
            'tax' => 'pajak',
            'notes' => 'catatan',
        ];
    }
}
