<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Chery Arta') }}</title>

        {{-- Ikon tab: emblem Chery putih di atas kotak brand-700. SVG, bukan .ico,
             supaya tajam di layar retina dan tetap satu berkas 3 KB. --}}
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/favicon.svg">

        {{-- Font Inter di-self-host lewat @fontsource (lihat resources/css/app.css).
             Tidak ada permintaan ke host luar — sesuai kebijakan CSP docs/09 §9.5. --}}

        {{-- Data terstruktur schema.org/AutoRepair untuk beranda (docs/06 §6.9).
             Disusun di server, bukan di React: menyuntikkan <script> dari komponen
             menuntut dangerouslySetInnerHTML yang dilarang di proyek ini. --}}
        @if (($page['component'] ?? null) === 'welcome')
            <script type="application/ld+json">
                {!! json_encode(App\Support\StructuredData::autoRepair(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
            </script>
        @endif

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
