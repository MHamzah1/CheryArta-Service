<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SlotService;
use App\Support\PublicContent;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Beranda (docs/06 §6.5).
 *
 * Menampilkan versi ringkas dari konten yang punya halaman sendiri; bentuk
 * datanya datang dari App\Support\PublicContent supaya kartu di sini dan kartu
 * di halaman penuhnya tidak pernah berbeda.
 *
 * Batas jumlah ditulis sebagai konstanta di sini karena murni keputusan tata
 * letak, bukan aturan bisnis — angka aturan booking tetap hanya di
 * `config/booking.php`.
 */
class HomeController extends Controller
{
    private const PAKET_DI_BERANDA = 3;

    private const MOBIL_DI_BERANDA = 4;

    private const FASILITAS_DI_BERANDA = 8;

    private const TESTIMONI_DI_BERANDA = 6;

    private const FAQ_DI_BERANDA = 6;

    public function __invoke(SlotService $slots): Response
    {
        return Inertia::render('welcome', [
            'advantages' => Config::array('company.advantages'),
            'servicePackages' => PublicContent::servicePackages(self::PAKET_DI_BERANDA),
            'carModels' => PublicContent::carModelCards(self::MOBIL_DI_BERANDA),
            'facilities' => PublicContent::facilities(self::FASILITAS_DI_BERANDA),
            'testimonials' => PublicContent::testimonials(self::TESTIMONI_DI_BERANDA),
            'faqs' => PublicContent::faqs(self::FAQ_DI_BERANDA),
            'slotPreview' => $this->slotPreview($slots),
        ]);
    }

    /**
     * Ketersediaan hari buka terdekat, untuk kartu di hero.
     *
     * Hanya jam, sisa kuota, dan penanda penuh yang keluar — tidak ada satu
     * pun keterangan tentang siapa yang sudah memesan (docs/09 §9.4).
     *
     * @return array<string, mixed>
     */
    private function slotPreview(SlotService $slots): array
    {
        $date = $slots->nextBookableDate();

        return [
            'date' => $date->toDateString(),
            'slots' => $slots->availability($date),
        ];
    }
}
