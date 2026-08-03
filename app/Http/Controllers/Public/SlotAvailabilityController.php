<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `GET /booking/slots?date=YYYY-MM-DD` — satu-satunya endpoint JSON di alur
 * booking (docs/03 §3.4).
 *
 * Ada di `routes/web.php`, bukan `api.php`: tetap memakai session dan CSRF,
 * dan bukan API publik. Rate limit-nya dipasang di rute.
 *
 * Yang dikembalikan hanya jumlah sisa kuota per jam — tidak ada satu pun data
 * pribadi pemesan (temuan S8).
 */
class SlotAvailabilityController extends Controller
{
    public function __invoke(Request $request, SlotService $slots): JsonResponse
    {
        $validated = $request->validate(
            ['date' => ['required', 'date_format:Y-m-d']],
            ['date.required' => 'Tanggal wajib diisi.', 'date.date_format' => 'Format tanggal harus YYYY-MM-DD.'],
        );

        $date = $slots->parseDate($validated['date']);
        $alasan = $slots->dateRejectionReason($date);

        return response()->json([
            'date' => $date->toDateString(),
            'is_bookable' => $alasan === null,
            // Kalimat siap tampil, supaya React tidak perlu menyusun ulang
            // aturan yang sama untuk menjelaskannya.
            'reason' => $alasan,
            'slots' => $alasan === null ? $slots->availability($date) : [],
        ]);
    }
}
