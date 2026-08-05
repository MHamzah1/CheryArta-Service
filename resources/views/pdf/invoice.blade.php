{{--
    PDF invoice (roadmap 2.4.3).

    Blade, BUKAN React: dompdf tidak menjalankan JavaScript sama sekali.
    Pengecualian sah terhadap .claude/rules/20, pola yang sama seperti JSON-LD
    di F1.6 yang juga disusun server.

    TANPA gambar raster — lihat App\Support\InvoiceDocument. Seluruh kop surat
    adalah teks dari config/company.php; menuliskan alamat langsung di sini
    berarti dua sumber untuk satu fakta.

    Gaya ditulis inline di berkas ini, bukan mengambil app.css: dompdf hanya
    memahami sebagian kecil CSS dan tidak mengenal Tailwind sama sekali. Token
    warnanya disalin dari docs/06 §6.2 seperlunya — brand-700 dan ink saja.
--}}
@php
    use App\Support\Rupiah;
    use App\Support\SlotTime;
    use App\Support\TanggalIndonesia;

    $perusahaan = config('company');
    $booking = $invoice->booking;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number ?? 'Invoice Draft' }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.5; }
        h1, h2, h3 { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .kop { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 16px; }
        .kop-nama { font-size: 18px; font-weight: bold; color: #1e3a8a; }
        .kop-alamat { font-size: 9px; color: #4b5563; }
        .judul { font-size: 15px; font-weight: bold; color: #1e3a8a; text-align: right; }
        .nomor { font-size: 11px; text-align: right; }
        .meta td { vertical-align: top; padding: 0 0 2px; font-size: 9.5px; }
        .meta .label { color: #6b7280; width: 92px; }
        .kotak { border: 1px solid #e5e7eb; padding: 8px 10px; }
        .rincian th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; color: #374151; border-bottom: 1px solid #e5e7eb; }
        .rincian td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .total-baris td { padding: 3px 8px; }
        .total-akhir td { border-top: 2px solid #1e3a8a; font-weight: bold; font-size: 12px; padding-top: 6px; }
        .cap { display: inline-block; border: 2px solid; padding: 3px 10px; font-weight: bold; font-size: 11px; letter-spacing: .06em; }
        .cap-lunas { color: #15803d; border-color: #15803d; }
        .cap-batal { color: #dc2626; border-color: #dc2626; }
        .cap-draft { color: #6b7280; border-color: #6b7280; }
        .catatan { margin-top: 14px; font-size: 9px; color: #4b5563; }
        .kaki { margin-top: 22px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8.5px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>

<table class="kop">
    <tr>
        <td>
            <div class="kop-nama">{{ $perusahaan['name'] }}</div>
            <div class="kop-alamat">
                {{ $perusahaan['address']['full'] }}<br>
                {{ $perusahaan['phones']['landline'] }} &middot; {{ $perusahaan['phones']['mobile'] }}<br>
                {{ $perusahaan['email'] }}
            </div>
        </td>
        <td style="width: 210px;">
            <div class="judul">INVOICE</div>
            <div class="nomor">
                {{ $invoice->invoice_number ?? 'Belum diterbitkan' }}<br>
                @if ($invoice->issued_at)
                    <span style="font-size: 9px; color: #6b7280;">
                        {{ TanggalIndonesia::tanpaHari($invoice->issued_at) }}
                    </span>
                @endif
            </div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td style="width: 50%; padding-right: 12px;">
            <div class="kotak">
                <table class="meta">
                    <tr><td class="label">Pelanggan</td><td>{{ $booking->user->name }}</td></tr>
                    <tr><td class="label">Kendaraan</td><td>{{ $booking->vehicle->display_model }}</td></tr>
                    <tr><td class="label">Nomor Polisi</td><td>{{ $booking->vehicle->plate_full }}</td></tr>
                    @if ($booking->odometer !== null)
                        <tr><td class="label">Odometer</td><td>{{ number_format((float) $booking->odometer, 0, ',', '.') }} km</td></tr>
                    @endif
                </table>
            </div>
        </td>
        <td style="width: 50%;">
            <div class="kotak">
                <table class="meta">
                    <tr><td class="label">Kode Booking</td><td>{{ $booking->booking_code }}</td></tr>
                    <tr><td class="label">Tanggal Servis</td><td>{{ TanggalIndonesia::lengkap($booking->booking_date) }}</td></tr>
                    <tr><td class="label">Jam</td><td>{{ SlotTime::short($booking->booking_time) }}</td></tr>
                    <tr><td class="label">Paket Layanan</td><td>{{ $booking->servicePackage->name }}</td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<table class="rincian" style="margin-top: 16px;">
    <thead>
        <tr>
            <th style="width: 26px;" class="tengah">#</th>
            <th>Keterangan</th>
            <th style="width: 58px;" class="tengah">Jenis</th>
            <th style="width: 46px;" class="kanan">Qty</th>
            <th style="width: 92px;" class="kanan">Harga</th>
            <th style="width: 100px;" class="kanan">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($invoice->items as $index => $item)
            <tr>
                <td class="tengah">{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="tengah">{{ $item->type->label() }}</td>
                <td class="kanan">{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                <td class="kanan">{{ Rupiah::format($item->unit_price) }}</td>
                <td class="kanan">{{ Rupiah::format($item->subtotal) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="tengah" style="padding: 14px; color: #6b7280;">Belum ada rincian.</td></tr>
        @endforelse
    </tbody>
</table>

<table style="margin-top: 12px;">
    <tr>
        <td style="vertical-align: bottom;">
            @if ($invoice->status === \App\Enums\InvoiceStatus::Paid)
                <span class="cap cap-lunas">LUNAS</span>
                <div style="margin-top: 5px; font-size: 9px; color: #4b5563;">
                    {{ $invoice->payment_method?->label() }}
                    @if ($invoice->paid_at) &middot; {{ TanggalIndonesia::tanpaHari($invoice->paid_at) }} @endif
                </div>
            @elseif ($invoice->status === \App\Enums\InvoiceStatus::Void)
                <span class="cap cap-batal">DIBATALKAN</span>
                @if ($invoice->void_reason)
                    <div style="margin-top: 5px; font-size: 9px; color: #4b5563;">{{ $invoice->void_reason }}</div>
                @endif
            @elseif ($invoice->status === \App\Enums\InvoiceStatus::Draft)
                <span class="cap cap-draft">DRAFT</span>
                <div style="margin-top: 5px; font-size: 9px; color: #4b5563;">Belum diterbitkan — angka masih dapat berubah.</div>
            @endif
        </td>
        <td style="width: 240px;">
            <table>
                <tr class="total-baris">
                    <td>Subtotal</td>
                    <td class="kanan">{{ Rupiah::format($invoice->subtotal) }}</td>
                </tr>
                @if ((float) $invoice->discount > 0)
                    <tr class="total-baris">
                        <td>Diskon</td>
                        <td class="kanan">&minus; {{ Rupiah::format($invoice->discount) }}</td>
                    </tr>
                @endif
                @if ((float) $invoice->tax > 0)
                    <tr class="total-baris">
                        <td>Pajak</td>
                        <td class="kanan">{{ Rupiah::format($invoice->tax) }}</td>
                    </tr>
                @endif
                <tr class="total-akhir">
                    <td>Total</td>
                    <td class="kanan">{{ Rupiah::format($invoice->total) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if ($invoice->notes)
    {{-- Catatan invoice memang ditujukan kepada pelanggan; catatan INTERNAL
         advisor hidup di bookings.admin_note dan tidak pernah ikut ke sini. --}}
    <div class="catatan"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
@endif

<div class="kaki">
    Terima kasih telah mempercayakan perawatan kendaraan Anda kepada {{ $perusahaan['name'] }}.<br>
    Dokumen ini dicetak dari sistem dan sah tanpa tanda tangan.
</div>

</body>
</html>
