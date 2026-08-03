<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SlotAvailabilityRequest;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;

/**
 * `GET /booking/slots?date=YYYY-MM-DD` — satu-satunya endpoint JSON di alur
 * booking (docs/03 §3.4).
 *
 * Ada di `routes/web.php`, bukan `api.php`: tetap memakai session dan CSRF,
 * dan bukan API publik. Rate limit-nya dipasang di rute.
 *
 * Yang dikembalikan hanya jumlah sisa kuota per jam — tidak ada satu pun data
 * pribadi pemesan (temuan S8).
 *
 * Dipakai dua pemanggil dengan aturan tanggal berbeda: form booking customer
 * (H-1 berlaku) dan form walk-in advisor (H-1 dilewati, docs/07 §A2). Bedanya
 * ditentukan `SlotAvailabilityRequest::source()`, yang memeriksa peran
 * pemanggil di server alih-alih mempercayai parameternya.
 */
class SlotAvailabilityController extends Controller
{
    public function __invoke(SlotAvailabilityRequest $request, SlotService $slots): JsonResponse
    {
        $date = $slots->parseDate((string) $request->validated('date'));
        $alasan = $slots->dateRejectionReason($date, $request->source());

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
