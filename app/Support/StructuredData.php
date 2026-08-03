<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Data terstruktur schema.org untuk beranda (docs/06 §6.9).
 *
 * Disusun di server dari `config/company.php` dan disisipkan lewat
 * `resources/views/app.blade.php`, bukan lewat React: menyuntikkan
 * `<script type="application/ld+json">` dari komponen menuntut
 * `dangerouslySetInnerHTML`, yang dilarang keras di proyek ini
 * (.claude/rules/20-frontend-react-inertia.md).
 */
final class StructuredData
{
    /** Nama hari schema.org, berkunci nama hari di config/company.php. */
    private const HARI = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    /**
     * Profil bengkel sebagai `schema.org/AutoRepair`.
     *
     * @return array<string, mixed>
     */
    public static function autoRepair(): array
    {
        /** @var array<string, string> $address */
        $address = Config::array('company.address');

        /** @var array<string, string> $phones */
        $phones = Config::array('company.phones');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'AutoRepair',
            'name' => Config::string('company.name'),
            'description' => Config::string('company.about'),
            'url' => url('/'),
            'image' => url('/logo.svg'),
            'telephone' => $phones['landline'],
            'email' => Config::string('company.email'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'],
                'addressLocality' => $address['city'],
                'addressRegion' => $address['province'],
                'addressCountry' => $address['country'],
            ],
            'openingHoursSpecification' => self::jamOperasional(),
            'sameAs' => self::sosial(),
            // Kunci yang isinya kosong dibuang: `sameAs: []` membuat validator
            // Google mengeluh, dan `telephone: ""` lebih buruk daripada tidak
            // ada nomor sama sekali.
        ], static fn (array|string $value): bool => $value !== [] && $value !== '');
    }

    /**
     * Hari tutup sengaja tidak dituliskan sama sekali: schema.org menganggap
     * hari yang tidak disebut sebagai tutup, dan menuliskannya dengan jam
     * kosong justru membuat Google memperlakukannya buka 24 jam.
     *
     * @return list<array<string, string>>
     */
    private static function jamOperasional(): array
    {
        /** @var array<string, array<string, mixed>> $jam */
        $jam = Config::array('company.operating_hours');
        $hasil = [];

        foreach (self::HARI as $kunci => $nama) {
            $hari = $jam[$kunci] ?? null;

            if (! is_array($hari) || ($hari['closed'] ?? false) === true) {
                continue;
            }

            $hasil[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => "https://schema.org/{$nama}",
                'opens' => (string) $hari['open'],
                'closes' => (string) $hari['close'],
            ];
        }

        return $hasil;
    }

    /** @return list<string> */
    private static function sosial(): array
    {
        /** @var array<string, string|null> $social */
        $social = Config::array('company.social');

        return array_values(array_filter([
            $social['instagram'] ?? null,
            $social['facebook'] ?? null,
        ], static fn (?string $url): bool => $url !== null && $url !== ''));
    }
}
