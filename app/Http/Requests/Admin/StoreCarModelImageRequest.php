<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AssetType;
use App\Models\CarModel;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Unggahan galeri katalog (docs/07 §A4).
 *
 * Dua hal yang ditegakkan di sini dan tidak boleh dipercayakan ke browser:
 * batas ukuran/MIME (dibaca dari config lewat UploadRules — Cloudinary bukan
 * pengganti validasi server) dan **teks alt wajib** untuk setiap gambar
 * (.claude/rules/20-frontend-react-inertia.md §Aksesibilitas).
 */
class StoreCarModelImageRequest extends FormRequest
{
    /** Sekali unggah dibatasi supaya satu permintaan tidak menahan proses PHP terlalu lama. */
    private const MAKS_SEKALI_UNGGAH = 10;

    public function authorize(): bool
    {
        $carModel = $this->route('car_model');

        return $carModel instanceof CarModel && $this->user()->can('update', $carModel);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:'.self::MAKS_SEKALI_UNGGAH],
            'images.*' => UploadRules::for(AssetType::Image),

            // Sejajar dengan `images` berdasarkan indeks; ukurannya dipaksa
            // sama agar tidak ada gambar yang lolos tanpa teks alt.
            'alts' => ['required', 'array', 'size:'.count($this->allFiles()['images'] ?? [])],
            'alts.*' => ['required', 'string', 'max:160'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'images.required' => 'Pilih dulu gambar yang ingin diunggah.',
            'images.max' => 'Paling banyak '.self::MAKS_SEKALI_UNGGAH.' gambar sekali unggah.',
            'images.*.image' => 'Berkas :position bukan gambar yang sah.',
            'images.*.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'images.*.max' => 'Ukuran setiap gambar paling besar 2 MB.',
            'alts.size' => 'Setiap gambar wajib punya teks alt.',
            'alts.*.required' => 'Teks alt wajib diisi agar gambar terbaca pembaca layar.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'images' => 'gambar',
            'alts' => 'teks alt',
        ];
    }
}
