<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\PublicContent;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman konten publik: layanan, fasilitas, tentang, FAQ (docs/06 §6.4).
 *
 * Keempatnya hanya membaca — tidak ada aksi, tidak ada form — sehingga
 * disatukan di satu controller alih-alih empat berkas berisi satu method.
 * Isinya datang dari database (A5 untuk layanan, seeder untuk sisanya sesuai
 * keputusan R4), bukan ditulis di JSX.
 */
class ContentPageController extends Controller
{
    /** Halaman `/layanan` — sumber: A5 Paket Layanan. */
    public function services(): Response
    {
        return Inertia::render('layanan', [
            'groups' => PublicContent::servicePackageGroups(),
            'faqs' => PublicContent::faqs(4),
        ]);
    }

    /** Halaman `/fasilitas` — galeri dengan lightbox. */
    public function facilities(): Response
    {
        return Inertia::render('fasilitas', [
            'facilities' => PublicContent::facilities(),
        ]);
    }

    /** Halaman `/tentang` — profil & keunggulan dari config/company.php. */
    public function about(): Response
    {
        return Inertia::render('tentang', [
            'about' => Config::string('company.about'),
            'experienceYears' => Config::integer('company.experience_years'),
            'advantages' => Config::array('company.advantages'),
            'facilities' => PublicContent::facilities(4),
            'testimonials' => PublicContent::testimonials(3),
        ]);
    }

    /** Halaman `/faq`. */
    public function faq(): Response
    {
        return Inertia::render('faq', [
            'groups' => PublicContent::faqGroups(),
        ]);
    }
}
