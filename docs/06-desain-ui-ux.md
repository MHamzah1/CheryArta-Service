# 06 — Desain UI/UX

## 6.1 Prinsip

1. **Mobile-first.** Dirancang dari 360px; layar besar mendapat tata letak lebih lapang, bukan sebaliknya.
2. **Satu ajakan dominan per layar.** Halaman depan hanya punya satu tombol utama: *Booking Servis*.
3. **Hierarki lewat ruang, bukan garis.** Sistem lama memakai bayangan tebal di mana-mana; versi baru memakai jarak dan satu tingkat elevasi.
4. **Tidak ada `alert()`.** Umpan balik memakai toast, inline error, dan dialog konfirmasi.
5. **Status selalu berupa lencana berwarna + teks** — tidak pernah warna saja (buta warna).
6. **Muat cepat.** Gambar `webp` responsif, font di-*self-host*, tanpa CDN pihak ketiga.

## 6.2 Design Token

Palet lama dipertahankan sebagai identitas (biru–merah–emas), tetapi dirapikan menjadi skala
yang bisa dipakai berulang.

```css
/* resources/css/app.css — @theme Tailwind v4 */
@theme {
  /* Merek */
  --color-brand-50:  #eef2ff;
  --color-brand-100: #e0e7ff;
  --color-brand-500: #2f4fbf;
  --color-brand-600: #24409f;
  --color-brand-700: #1e3a8a;   /* biru Chery Arta — warna utama */
  --color-brand-900: #16276b;

  /* merah — tindakan merusak & peringatan, bukan warna dominan.
     Dinamai "danger", BUKAN "accent", agar tidak tertukar dengan
     --color-accent milik shadcn/ui yang berwarna netral. */
  --color-danger-500: #dc2626;
  --color-danger-600: #b91c1c;

  --color-gold-400: #fbbf24;    /* emas — hanya untuk CTA & sorotan */
  --color-gold-500: #f59e0b;

  /* Netral */
  --color-ink:      #0f172a;
  --color-ink-soft: #475569;
  --color-line:     #e2e8f0;
  --color-surface:  #ffffff;
  --color-canvas:   #f8fafc;

  /* Status (kontras teks ≥ 4.5:1 di atas latarnya) */
  --color-status-pending-bg:  #fef3c7;  --color-status-pending-fg:  #92400e;
  --color-status-confirmed-bg:#dbeafe;  --color-status-confirmed-fg:#1e40af;
  --color-status-progress-bg: #e0e7ff;  --color-status-progress-fg: #3730a3;
  --color-status-done-bg:     #dcfce7;  --color-status-done-fg:     #166534;
  --color-status-cancel-bg:   #fee2e2;  --color-status-cancel-fg:   #991b1b;
  --color-status-noshow-bg:   #f1f5f9;  --color-status-noshow-fg:   #475569;

  /* Tipografi — Inter, di-self-host */
  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;

  /* Radius & elevasi — satu tingkat saja */
  --radius-card: 1rem;
  --radius-btn:  0.75rem;
  --shadow-card: 0 1px 3px rgb(15 23 42 / 0.08), 0 1px 2px rgb(15 23 42 / 0.04);
  --shadow-pop:  0 12px 32px rgb(15 23 42 / 0.12);
}
```

### Skala tipografi

| Peran | Ukuran (mobile → desktop) | Bobot |
|-------|---------------------------|-------|
| Display (hero) | 2rem → 3.5rem | 800 |
| H2 seksi | 1.5rem → 2.25rem | 700 |
| H3 kartu | 1.125rem → 1.25rem | 600 |
| Body | 1rem | 400 |
| Caption/label | 0.875rem | 500 |

Skala jarak: kelipatan 4px (`4 8 12 16 24 32 48 64 96`). Padding seksi: `py-16` mobile → `py-24` desktop.

## 6.3 Perubahan Visual dari Versi Lama

| Elemen | Lama | Baru |
|--------|------|------|
| Hero | Gradien biru→merah penuh layar + mobil melayang | Gradien lembut biru tua + foto bengkel, teks kiri, kartu "cek slot hari ini" di kanan |
| Kartu form | Kaca buram di atas gradien (kontras teks rendah) | Kartu putih di atas latar netral — keterbacaan jauh lebih baik |
| Dropdown layanan | Dropdown kustom bertumpuk, teks panjang terpotong | Kartu pilihan paket (radio card) dengan nama, durasi, harga |
| Pemilih jam | `<select>` 11 opsi tanpa info ketersediaan | Kisi tombol slot: tersedia / tersisa 1 / penuh |
| Status | Lencana `alert()`-driven | Lencana + timeline vertikal |
| Ikon | Font Awesome via CDN | `lucide-react` (bundled) |
| Notifikasi | `alert()` blocking | Toast + inline error |
| Tabel admin | Tabel lebar dengan scroll horizontal | Tabel padat + kolom aksi tetap, versi kartu di mobile (dipertahankan) |

## 6.4 Peta Situs

```
Publik
├── /                     Beranda
├── /katalog              Katalog mobil (filter: kategori, bahan bakar)
├── /katalog/{slug}       Detail model + varian + galeri + CTA booking
├── /layanan              Daftar paket layanan + durasi + harga
├── /fasilitas            Galeri 8 fasilitas (lightbox)
├── /tentang              Profil perusahaan & keunggulan
├── /faq                  Pertanyaan umum
├── /kontak               Alamat, peta, jam operasional, form pesan
├── /cek-service          Lacak status via kode booking
├── /login  /register  /lupa-password
│
Customer (perlu login)
├── /dashboard            Ringkasan: booking terdekat, kendaraan, tombol booking
├── /booking              Form booking 4 langkah
├── /booking/{kode}       Detail + timeline + tombol jadwal ulang/batal
├── /riwayat              Riwayat servis (filter per kendaraan)
├── /kendaraan            Daftar kendaraan + tambah/ubah
├── /invoice/{no}         Rincian biaya + unduh PDF
└── /profil               Ubah data diri, nomor WA, password
│
Admin (/admin — perlu role staf)
├── /                     Dashboard
├── /jadwal               Okupansi slot harian
├── /bookings             Daftar + filter + detail + ubah status
├── /customers            Daftar customer + riwayat
├── /vehicles             Daftar kendaraan terdaftar
├── /invoices             Daftar invoice
├── /laporan              Rekap + export
└── Super Admin: /katalog · /paket-layanan · /fasilitas · /faq · /testimoni
                 /users · /wa-template · /pesan-masuk · /activity-log
```

## 6.5 Wireframe Halaman Kunci

### Beranda (mobile 360px → desktop)

```
┌──────────────────────────────────────────────┐
│ [Chery Arta]        Katalog Layanan ... [Masuk]│  ← header lengket, transparan→solid saat scroll
├──────────────────────────────────────────────┤
│                                              │
│   Servis Chery Anda,                         │
│   tanpa antre.                               │   HERO
│   15+ tahun pengalaman · Teknisi bersertifikat│
│                                              │
│   [ Booking Servis ]  [ Lihat Katalog ]      │
│                                    ┌────────┐│
│                                    │Slot besok││ ← kartu ketersediaan
│                                    │08:00 ✓  ││
│                                    │08:30 ✓  ││
│                                    │09:00 penuh│
│                                    └────────┘│
├──────────────────────────────────────────────┤
│  ⚙ Teknisi   🔧 Peralatan   ⏱ Layanan  🛡 Garansi│  ← 4 keunggulan (dari sistem lama)
├──────────────────────────────────────────────┤
│  LAYANAN KAMI                                │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐         │
│  │First Mt.│ │Free Mt. │ │Perawatan│         │  ← kartu paket + durasi + harga
│  │1.000 km │ │Tiggo 8  │ │lainnya  │         │
│  │60 menit │ │Gratis   │ │         │         │
│  └─────────┘ └─────────┘ └─────────┘         │
├──────────────────────────────────────────────┤
│  KATALOG MOBIL           [lihat semua →]     │
│  ┌────────┐┌────────┐┌────────┐┌────────┐    │  ← geser horizontal di mobile
│  │Tiggo 8 ││Omoda 5 ││Tiggo 5X││Jaecoo  │    │
│  │Rp 4xx jt││…       ││…       ││…       │    │
│  └────────┘└────────┘└────────┘└────────┘    │
├──────────────────────────────────────────────┤
│  CARA BOOKING                                │
│  ①Daftar/Masuk → ②Pilih jadwal → ③Datang     │
├──────────────────────────────────────────────┤
│  FASILITAS  (galeri 8 foto, klik → lightbox) │
├──────────────────────────────────────────────┤
│  TESTIMONI  ★★★★★                            │
├──────────────────────────────────────────────┤
│  FAQ (accordion 6 pertanyaan)                │
├──────────────────────────────────────────────┤
│  LOKASI & JAM                                │
│  [peta]     Jl. Jend. Sudirman No.1, Kranji  │
│             Sen–Jum 08:00–16:00              │
│             Sabtu  08:00–14:00 · Minggu tutup│
├──────────────────────────────────────────────┤
│  Footer: navigasi · kontak · sosial          │
└──────────────────────────────────────────────┘
                                      (💬) ← tombol WA mengambang
```

### Form Booking — 4 langkah

```
① Kendaraan   ②Layanan   ③Jadwal   ④Tinjau
━━━━━━━━━━━━━━●━━━━━━━━━━━━━━━━━━━━━━━━━━━━

LANGKAH 3 — Pilih Jadwal

Tanggal:  [ Agustus 2026 ▾ ]
 Sn Sl Rb Km Jm Sb Mg
                 1  ×      ← Minggu dinonaktifkan
  3  4  5  6  7  8  ×      ← tanggal < besok dinonaktifkan
 10 11 12 13 14 15  ×

Jam tersedia — Senin, 3 Agustus 2026
┌──────┐┌──────┐┌──────┐┌──────┐
│08:00 ││08:30 ││09:00 ││09:30 │
│  ✓   ││ sisa1││ PENUH││  ✓   │   ← keadaan terlihat sebelum dipilih
└──────┘└──────┘└──────┘└──────┘
┌──────┐┌──────┐┌──────┐┌──────┐
│10:00 ││10:30 ││11:00 ││11:30 │
└──────┘└──────┘└──────┘└──────┘
      — istirahat 12:00–13:00 —
┌──────┐┌──────┐┌──────┐
│13:00 ││13:30 ││14:00 │
└──────┘└──────┘└──────┘

[← Kembali]                    [Lanjut →]
```

### Detail Booking Customer (tracking)

```
┌──────────────────────────────────────────┐
│ CA-20260803-0001        [Sedang Dikerjakan]│
│ Tiggo 8 Pro · B-1234-ABC                 │
│ Senin, 3 Agustus 2026 · 09:00            │
│ Free Maintenance Tiggo 8 Series          │
│ Estimasi selesai: 10:30                  │
├──────────────────────────────────────────┤
│ ● Booking dibuat        2 Agu, 20:14     │
│ │                                        │
│ ● Dikonfirmasi          2 Agu, 20:51     │
│ │  "Sampai jumpa besok pukul 09.00"      │
│ ● Sedang dikerjakan     3 Agu, 09:05     │
│ ○ Selesai                                │
├──────────────────────────────────────────┤
│ Estimasi biaya: Gratis                   │
│ [Jadwalkan Ulang]  [Batalkan]            │  ← tersembunyi bila < H-1
└──────────────────────────────────────────┘
```

### Dashboard Admin

```
┌───────────┬────────────────────────────────────────────────┐
│ CHERY     │ Dashboard                    Andre (Advisor) ▾ │
│ ARTA      ├────────────────────────────────────────────────┤
│           │ ┌────────┐┌────────┐┌────────┐┌────────┐       │
│ ▸Dashboard│ │Hari ini││ Perlu  ││Dikerja-││Selesai │       │
│  Jadwal   │ │   7    ││konfirm.││  kan   ││bln ini │       │
│  Booking  │ │booking ││   3    ││   2    ││   64   │       │
│  Customer │ └────────┘└────────┘└────────┘└────────┘       │
│  Kendaraan│                                                │
│  Invoice  │ Tren 30 hari          Okupansi slot hari ini   │
│  Laporan  │ ┌────────────────┐   08:00 ██ 2/2 PENUH        │
│ ─────────  │ │    ╱╲    ╱╲    │   08:30 █  1/2             │
│  Katalog  │ │  ╱╲  ╲  ╱  ╲   │   09:00 ██ 2/2 PENUH        │
│  Paket    │ │ ╱      ╲╱     ╲│   09:30 ░  0/2             │
│  Konten   │ └────────────────┘   …                        │
│  Pengguna │                                                │
│           │ Booking hari ini                               │
│           │ ┌────────────────────────────────────────────┐ │
│           │ │Jam  Kode      Customer  Kendaraan  Status  │ │
│           │ │08:00 CA-…0001 Rani S.   B-1234-ABC [Selesai]│ │
│           │ │09:00 CA-…0002 Budi      B-5678-CD [Dikerjakan]│
│           │ │09:00 CA-…0003 Sinta     D-9012-EF [Perlu ✓] │ │
│           │ └────────────────────────────────────────────┘ │
└───────────┴────────────────────────────────────────────────┘
```

### Detail Booking Admin + panel WhatsApp

```
┌──────────────────────────────────────────────────────────┐
│ CA-20260803-0002                        [Dikonfirmasi ▾] │
├───────────────────────────────┬──────────────────────────┤
│ Customer  Budi Santoso        │  Ubah Status             │
│ WhatsApp  0812-3456-7890      │  ( ) Sedang Dikerjakan   │
│ Kendaraan Tiggo 5X B-5678-CD  │  ( ) Selesai             │
│ Paket     First Mt. 1.000 km  │  ( ) Batalkan            │
│ Jadwal    3 Agu 2026 · 09:00  │  ( ) Tidak Hadir         │
│ Odometer  [ 1.024 ] km        │  Catatan untuk customer: │
│ Keluhan   "Bunyi di rem depan"│  [____________________]  │
│                               │  [ Simpan Perubahan ]    │
├───────────────────────────────┴──────────────────────────┤
│ 💬 Notifikasi WhatsApp                                   │
│ ┌──────────────────────────────────────────────────────┐ │
│ │ Halo Budi Santoso, servis Chery 5X (B-5678-CD)       │ │
│ │ dengan kode CA-20260803-0002 sedang kami kerjakan.   │ │
│ │ Estimasi selesai pukul 10.00. — Chery Arta           │ │
│ └──────────────────────────────────────────────────────┘ │
│ [ Buka WhatsApp ]  [ Salin Pesan ]  [ Tandai Terkirim ]  │
│ Terakhir dikirim: 3 Agu 09:07 oleh Andre                 │
├──────────────────────────────────────────────────────────┤
│ Riwayat Status                                           │
│ 2 Agu 20:14  Dibuat (web) — Budi Santoso                 │
│ 2 Agu 20:51  Pending → Dikonfirmasi — Andre              │
└──────────────────────────────────────────────────────────┘
```

## 6.6 Komponen Bersama

| Komponen | Berkas | Catatan |
|----------|--------|---------|
| `StatusBadge` | `components/status-badge.tsx` | Peta status → warna + label Indonesia; **satu-satunya** tempat warna status ditentukan |
| `SlotPicker` | `components/booking/slot-picker.tsx` | Kisi slot dengan keadaan tersedia/sisa-1/penuh |
| `PlateInput` | `components/plate-input.tsx` | 3 segmen, pindah fokus otomatis, prefix **1–2 huruf** (temuan B1) |
| `PhoneInput` | `components/phone-input.tsx` | Format saat mengetik, normalisasi `62…` saat submit |
| `DataTable` | `components/data-table.tsx` | Tabel + urut + paginasi server; otomatis berubah menjadi kartu di `< md` |
| `Timeline` | `components/timeline.tsx` | Riwayat status booking |
| `EmptyState` | `components/empty-state.tsx` | Ilustrasi + ajakan tindakan |
| `ConfirmDialog` | `components/confirm-dialog.tsx` | Pengganti `confirm()` bawaan browser |
| `Toast` | `components/ui/sonner.tsx` | Pengganti `alert()`; membaca `flash` dari shared props |
| `Lightbox` | `components/lightbox.tsx` | Galeri fasilitas & katalog, mendukung keyboard & swipe |

## 6.7 Responsif

| Breakpoint | Perilaku |
|------------|----------|
| `< 640px` | Satu kolom; menu jadi drawer; tabel jadi kartu; slot 2 kolom; tombol lebar penuh |
| `640–1023px` | Dua kolom kartu; slot 3 kolom; sidebar admin jadi drawer |
| `≥ 1024px` | Tiga–empat kolom; sidebar admin tetap terlihat; slot 4 kolom |

Target sentuh minimal 44×44px. Tabel yang tetap perlu lebar dibungkus wadah dengan
`overflow-x-auto` — **badan halaman tidak pernah menggeser horizontal**.

## 6.8 Aksesibilitas

- Semua ikon aksi punya `aria-label`; ikon dekoratif diberi `aria-hidden`.
- Fokus terlihat jelas (`focus-visible:ring-2 ring-brand-600`) — jangan pernah `outline: none` tanpa pengganti.
- Kalender & kisi slot dapat dinavigasi dengan panah keyboard; slot penuh memakai `aria-disabled`.
- Modal: fokus terperangkap, `Esc` menutup, fokus kembali ke pemicu.
- Kontras minimal 4.5:1 untuk teks; lencana status sudah diuji terhadap latarnya.
- `prefers-reduced-motion` menonaktifkan autoplay slider dan animasi melayang.
- Setiap gambar wajib punya `alt` bermakna (kolom `alt` di `car_model_images` bersifat wajib).

## 6.9 SEO & Metadata

- Judul unik per halaman lewat `<Head>` Inertia: `Tiggo 8 Pro — Katalog | Chery Arta Bekasi`.
- Data terstruktur `schema.org/AutoRepair` di beranda (nama, alamat, telepon, jam buka, geo).
- Open Graph + Twitter Card untuk beranda dan tiap halaman katalog.
- `sitemap.xml` dibangkitkan dari `car_models` aktif + halaman statis; `robots.txt` memblokir `/admin`.
- URL berbahasa Indonesia dan deskriptif (`/katalog/tiggo-8-pro`, bukan `/car?id=3`).
