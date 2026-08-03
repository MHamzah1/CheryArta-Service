<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use Illuminate\Http\Response;

/**
 * `sitemap.xml` (docs/06 §6.9).
 *
 * Dibangkitkan dari halaman statis + `car_models` yang aktif, sehingga model
 * yang dinonaktifkan admin langsung hilang dari peta situs tanpa ada berkas
 * yang perlu disunting tangan.
 *
 * Rute panel (`/admin`, `/booking`, `/dashboard`) tidak pernah masuk ke sini —
 * `public/robots.txt` juga memblokirnya.
 */
class SitemapController extends Controller
{
    /** Halaman publik yang tidak bergantung pada isi database. */
    private const HALAMAN_STATIS = [
        ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['route' => 'public.catalog.index', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['route' => 'public.services', 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['route' => 'public.facilities', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['route' => 'public.about', 'priority' => '0.6', 'changefreq' => 'yearly'],
        ['route' => 'public.faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['route' => 'public.contact.show', 'priority' => '0.7', 'changefreq' => 'yearly'],
        ['route' => 'public.tracking', 'priority' => '0.5', 'changefreq' => 'yearly'],
    ];

    public function __invoke(): Response
    {
        $entri = [];

        foreach (self::HALAMAN_STATIS as $halaman) {
            $entri[] = [
                'loc' => route($halaman['route']),
                'lastmod' => null,
                'changefreq' => $halaman['changefreq'],
                'priority' => $halaman['priority'],
            ];
        }

        CarModel::query()
            ->active()
            ->ordered()
            ->each(function (CarModel $carModel) use (&$entri): void {
                $entri[] = [
                    'loc' => route('public.catalog.show', $carModel->slug),
                    'lastmod' => $carModel->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.8',
                ];
            });

        return response($this->xml($entri), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /** @param  list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>  $entri */
    private function xml(array $entri): string
    {
        $baris = array_map(static function (array $item): string {
            $lastmod = $item['lastmod'] === null
                ? ''
                : '        <lastmod>'.htmlspecialchars($item['lastmod'], ENT_XML1).'</lastmod>'.PHP_EOL;

            return '    <url>'.PHP_EOL
                .'        <loc>'.htmlspecialchars($item['loc'], ENT_XML1).'</loc>'.PHP_EOL
                .$lastmod
                .'        <changefreq>'.$item['changefreq'].'</changefreq>'.PHP_EOL
                .'        <priority>'.$item['priority'].'</priority>'.PHP_EOL
                .'    </url>';
        }, $entri);

        return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL
            .implode(PHP_EOL, $baris).PHP_EOL
            .'</urlset>'.PHP_EOL;
    }
}
