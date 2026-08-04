<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AssetType;
use App\Models\Facility;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Fasilitas yang tampil di landing page (docs/07 §A10).
 *
 * Satu berkas untuk tambah dan ubah: bedanya hanya gambar wajib atau tidak,
 * dan itu bisa ditentukan dari ada tidaknya rute model.
 */
class FacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $facility = $this->route('facility');

        return $facility instanceof Facility
            ? ($this->user()?->can('update', $facility) ?? false)
            : ($this->user()?->can('create', Facility::class) ?? false);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:255'],
            // Gambar wajib saat menambah, opsional saat mengubah — mengubah
            // judul tidak boleh menuntut unggah ulang gambarnya.
            'image' => [$this->menambah() ? 'required' : 'nullable', ...UploadRules::for(AssetType::Image)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul fasilitas wajib diisi.',
            'title.max' => 'Judul fasilitas maksimal 120 karakter.',
            'description.required' => 'Deskripsi fasilitas wajib diisi.',
            'description.max' => 'Deskripsi fasilitas maksimal 255 karakter.',
            'image.required' => 'Gambar fasilitas wajib diunggah.',
            'image.image' => 'Berkas yang diunggah harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar melebihi batas yang diizinkan.',
        ];
    }

    private function menambah(): bool
    {
        return ! $this->route('facility') instanceof Facility;
    }
}
