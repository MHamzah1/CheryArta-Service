<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Urutan konten hasil seret (docs/07 §A10).
 *
 * Satu berkas untuk tiga sumber daya. Nama tabelnya datang dari rute, bukan
 * dari request — kalau tidak, mengirim `table=users` akan membuat aturan
 * `exists` menilai tabel yang salah.
 */
class ReorderContentRequest extends FormRequest
{
    /** @var array<string, class-string<\Illuminate\Database\Eloquent\Model>> */
    private const SUMBER = [
        'facilities' => \App\Models\Facility::class,
        'faqs' => \App\Models\Faq::class,
        'testimonials' => \App\Models\Testimonial::class,
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->modelClass()) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'integer',
                // `distinct` mencegah satu id dikirim dua kali, yang akan
                // membuat satu baris menimpa urutan baris lain.
                'distinct',
                Rule::exists($this->tabel(), 'id'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ids.required' => 'Urutan tidak terkirim. Muat ulang halaman lalu coba lagi.',
            'ids.*.exists' => 'Ada baris yang sudah tidak tersedia. Muat ulang halaman lalu susun ulang urutannya.',
            'ids.*.distinct' => 'Ada baris yang terkirim lebih dari sekali.',
        ];
    }

    /** @return list<int> */
    public function ids(): array
    {
        /** @var list<int> $ids */
        $ids = $this->validated('ids');

        return $ids;
    }

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    public function modelClass(): string
    {
        return self::SUMBER[$this->tabel()];
    }

    /**
     * Tabel ditentukan dari nama rute yang sedang dijalankan
     * (`admin.facilities.reorder` → `facilities`), bukan dari masukan.
     */
    private function tabel(): string
    {
        $nama = (string) $this->route()?->getName();

        foreach (array_keys(self::SUMBER) as $tabel) {
            if (str_contains($nama, ".{$tabel}.")) {
                return $tabel;
            }
        }

        abort(404);
    }
}
