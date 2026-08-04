<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLogFilterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * A12 — Activity Log (docs/07 §A12). Super Admin saja.
 *
 * Menjawab "siapa mengubah apa dan kapan" — kebutuhan yang sama sekali tidak
 * terpenuhi sistem lama.
 *
 * **Pencatatan dimulai sejak tahap ini; tidak ada pengisian mundur**
 * (keputusan grill #3). Perubahan sebelumnya tidak punya jejak, dan mengarang
 * datanya akan membuat log audit yang isinya tebakan — lebih berbahaya
 * daripada log yang jujur mulai dari satu tanggal. Karena itu layar
 * menampilkan tanggal mulai pencatatan, supaya kekosongan sebelumnya tidak
 * dibaca sebagai "tidak ada yang berubah".
 *
 * **Perubahan status booking TIDAK ada di sini.** Riwayatnya tetap milik
 * `booking_status_histories` yang sudah dipakai detail booking sejak Tahap 6.
 * Dua sumber kebenaran untuk satu fakta adalah cacat B2 sistem lama.
 */
class ActivityLogController extends Controller
{
    public function __invoke(ActivityLogFilterRequest $request): Response
    {
        Gate::authorize('viewActivityLog', User::class);

        $logs = Activity::query()
            ->with('causer:id,name')
            ->when($request->causer(), fn ($q, int $id) => $q->where('causer_id', $id))
            ->when($request->subject(), fn ($q, string $type) => $q->where('subject_type', $type))
            ->when($request->dari(), fn ($q, string $dari) => $q->whereDate('created_at', '>=', $dari))
            ->when($request->sampai(), fn ($q, string $sampai) => $q->whereDate('created_at', '<=', $sampai))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Activity $a): array => [
                'id' => $a->id,
                'description' => $a->description,
                'subject_label' => $this->labelSubjek($a),
                'subject_type' => $a->subject_type,
                'causer_name' => $a->causer instanceof User ? $a->causer->name : null,
                'changes' => $this->perubahan($a),
                'created_at' => $a->created_at?->toIso8601String() ?? '',
            ]);

        return Inertia::render('admin/activity-log/index', [
            'logs' => $logs,
            'filters' => [
                'causer' => $request->causer(),
                'subject' => $request->subject(),
                'dari' => $request->dari(),
                'sampai' => $request->sampai(),
            ],
            'causerOptions' => User::query()
                ->whereIn('role', ['super_admin', 'service_advisor'])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $u): array => ['value' => (string) $u->id, 'label' => $u->name]),
            'subjectOptions' => ActivityLogFilterRequest::subjectOptions(),
            // Kekosongan sebelum tanggal ini bukan berarti tidak ada perubahan
            // — hanya berarti belum dicatat.
            'mulaiMencatat' => Activity::query()->min('created_at'),
        ]);
    }

    /** Label yang terbaca manusia, bukan nama kelas mentah. */
    private function labelSubjek(Activity $activity): string
    {
        $nama = class_basename((string) $activity->subject_type);

        return ActivityLogFilterRequest::LABEL_SUBJEK[$nama] ?? $nama;
    }

    /**
     * Pasangan sebelum → sesudah untuk ditampilkan.
     *
     * Atribut sensitif sudah dikecualikan di masing-masing model lewat
     * `logExcept()`, jadi di sini tidak ada penyaringan ulang — satu tempat
     * saja, supaya tidak ada yang mengira penyaringannya cukup di sini.
     *
     * @return list<array{field: string, before: string, after: string}>
     */
    private function perubahan(Activity $activity): array
    {
        /** @var array<string, mixed> $sesudah */
        $sesudah = $activity->properties->get('attributes', []);
        /** @var array<string, mixed> $sebelum */
        $sebelum = $activity->properties->get('old', []);

        $hasil = [];

        foreach ($sesudah as $kolom => $nilaiBaru) {
            $nilaiLama = $sebelum[$kolom] ?? null;

            if ($nilaiLama === $nilaiBaru) {
                continue;
            }

            $hasil[] = [
                'field' => $kolom,
                'before' => $this->teks($nilaiLama),
                'after' => $this->teks($nilaiBaru),
            ];
        }

        return $hasil;
    }

    private function teks(mixed $nilai): string
    {
        return match (true) {
            $nilai === null => '—',
            is_bool($nilai) => $nilai ? 'ya' : 'tidak',
            is_scalar($nilai) => (string) $nilai,
            default => json_encode($nilai, JSON_UNESCAPED_UNICODE) ?: '—',
        };
    }
}
