<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\SlotService;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Tanggal yang sedang dilihat di jadwal harian (roadmap 1.5.5).
 *
 * Berbeda dari form booking, tanggal di sini TIDAK dibatasi aturan H-1 atau
 * batas 60 hari: advisor memang perlu menengok ke belakang untuk memeriksa
 * hari kemarin, dan ke depan sejauh apa pun. Hari tutup pun tetap boleh
 * dibuka — layarnya menjelaskan bahwa bengkel tutup, bukan menolak.
 */
class ScheduleRequest extends FormRequest
{
    /** Otorisasinya `viewAny` di controller — lihat Admin\ScheduleController. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Tanggal yang diminta, atau hari ini menurut zona bengkel.
     *
     * Sengaja tidak bernama `date()`: nama itu sudah dipakai
     * `Illuminate\Http\Request` dengan tanda tangan yang berbeda.
     */
    public function selectedDate(SlotService $slots): CarbonImmutable
    {
        $tanggal = $this->validated('tanggal');

        if (! is_string($tanggal) || $tanggal === '') {
            return $slots->today();
        }

        try {
            return $slots->parseDate($tanggal);
        } catch (Exception) {
            return $slots->today();
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tanggal.date_format' => 'Tanggal yang diminta tidak dikenali.',
        ];
    }
}
