<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Booking;
use App\Models\CarModel;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * Saringan activity log (docs/07 §A12).
 *
 * Jenis objek datang dari daftar tertutup, bukan dari masukan bebas — tanpa
 * itu `subject_type` bisa diisi nama kelas apa pun dan dipakai memeriksa
 * keberadaan tabel lain.
 *
 * Rentang tanggal punya batas atas: tanpanya satu permintaan bisa memindai
 * seluruh tabel log yang justru paling cepat membesar di antara semua tabel.
 */
class ActivityLogFilterRequest extends FormRequest
{
    /** @var array<string, string> */
    public const LABEL_SUBJEK = [
        'User' => 'Akun',
        'CarModel' => 'Katalog Mobil',
        'ServicePackage' => 'Paket Layanan',
        'Booking' => 'Booking',
        'Facility' => 'Fasilitas',
        'Faq' => 'FAQ',
        'Testimonial' => 'Testimoni',
        'ContactMessage' => 'Pesan Masuk',
    ];

    /** Otorisasinya `viewActivityLog` di controller. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $batas = Config::integer('activitylog.max_filter_range_days');

        return [
            'causer' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'subject' => ['nullable', Rule::in(self::jenisObjek())],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari', "before_or_equal:dari +{$batas} days"],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh mendahului tanggal mulai.',
            'sampai.before_or_equal' => 'Rentang tanggal terlalu panjang. Persempit rentangnya.',
            'subject.in' => 'Jenis objek tidak dikenali.',
        ];
    }

    public function causer(): ?int
    {
        $causer = $this->validated('causer');

        return is_numeric($causer) ? (int) $causer : null;
    }

    public function subject(): ?string
    {
        $subject = $this->validated('subject');

        return is_string($subject) && $subject !== '' ? $subject : null;
    }

    public function dari(): ?string
    {
        return $this->tanggal('dari');
    }

    public function sampai(): ?string
    {
        return $this->tanggal('sampai');
    }

    /**
     * Pilihan jenis objek untuk layar — pasangan nilai kelas dan labelnya.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function subjectOptions(): array
    {
        return array_map(
            fn (string $kelas): array => [
                'value' => $kelas,
                'label' => self::LABEL_SUBJEK[class_basename($kelas)] ?? class_basename($kelas),
            ],
            self::jenisObjek(),
        );
    }

    /**
     * Model yang dicatat (docs/04 §4.2). Konten A10 ikut agar penghapusan
     * pesan kontak punya jejak — isinya hilang, pelakunya tidak.
     *
     * @return list<class-string>
     */
    private static function jenisObjek(): array
    {
        return [
            User::class,
            CarModel::class,
            ServicePackage::class,
            Booking::class,
            \App\Models\Facility::class,
            \App\Models\Faq::class,
            \App\Models\Testimonial::class,
            \App\Models\ContactMessage::class,
        ];
    }

    private function tanggal(string $kunci): ?string
    {
        $nilai = $this->validated($kunci);

        return is_string($nilai) && $nilai !== '' ? $nilai : null;
    }
}
