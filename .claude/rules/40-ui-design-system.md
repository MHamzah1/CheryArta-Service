# Aturan 40 — Design System

Rujukan lengkap: `docs/06-desain-ui-ux.md`.

## Warna

Gunakan **hanya** token dari `resources/css/app.css`. Hex mentah di JSX/komponen ditolak.

| Peran | Token | Pemakaian |
|-------|-------|-----------|
| Utama | `brand-700` (#1e3a8a) | Header, tombol utama, tautan aktif |
| Utama gelap | `brand-900` | Latar gradien, sidebar admin |
| Bahaya | `danger-500` (#dc2626) atau `destructive` | Tindakan merusak, penanda penting — **hemat**. Jangan pakai `accent-*` untuk merah: `--color-accent` milik shadcn berwarna netral |
| Sorotan | `gold-400` (#fbbf24) | CTA utama di hero, lencana istimewa |
| Teks | `ink`, `ink-soft` | Isi & keterangan |
| Garis | `line` | Pembatas, tepi kartu |
| Latar | `surface` (putih), `canvas` (abu sangat muda) | Kartu vs halaman |

Aturan proporsi: biru mendominasi, emas hanya untuk satu ajakan utama per layar, merah hanya
untuk peringatan/penghapusan. Sistem lama memakai gradien biru→merah di mana-mana — jangan diulang.

## Status Booking

Peta warna & label **hanya** di `lib/status.ts`, dipakai lewat `<StatusBadge status={…} />`:

| Status | Label | Token |
|--------|-------|-------|
| `pending` | Menunggu Konfirmasi | `status-pending` |
| `confirmed` | Dikonfirmasi | `status-confirmed` |
| `in_progress` | Sedang Dikerjakan | `status-progress` |
| `completed` | Selesai | `status-done` |
| `cancelled` | Dibatalkan | `status-cancel` |
| `no_show` | Tidak Hadir | `status-noshow` |

Lencana selalu **warna + teks**, tidak pernah warna saja.

## Tipografi

Inter, di-*self-host*. Skala: Display 2rem→3.5rem/800 · H2 1.5rem→2.25rem/700 ·
H3 1.125rem→1.25rem/600 · Body 1rem/400 · Caption 0.875rem/500.
Panjang baris teks isi maksimal ±70 karakter (`max-w-prose`).

## Jarak & Bentuk

- Skala jarak kelipatan 4px: `4 8 12 16 24 32 48 64 96`.
- Padding seksi publik: `py-16` mobile → `py-24` desktop; lebar isi `max-w-7xl mx-auto px-4`.
- Radius: kartu `rounded-2xl`, tombol/input `rounded-xl`, lencana `rounded-full`.
- **Satu tingkat elevasi**: `shadow-card` untuk kartu, `shadow-pop` untuk popover/modal. Tidak ada
  bayangan tebal bertumpuk seperti sistem lama.

## Pola Komponen

| Kebutuhan | Pakai |
|-----------|-------|
| Umpan balik aksi | Toast (dari flash props) |
| Konfirmasi tindakan merusak | `ConfirmDialog` — teks tombol menyebut aksinya ("Batalkan Booking"), bukan "OK" |
| Daftar kosong | `EmptyState` dengan ajakan tindakan, bukan tabel kosong |
| Sedang memuat | Skeleton, bukan spinner layar penuh |
| Galat form | Pesan inline di bawah field + fokus ke field pertama yang salah |
| Data tabel | `DataTable` (otomatis jadi kartu di `< md`) |

## Bahasa Antarmuka

- Bahasa Indonesia, sapaan "Anda".
- Judul tombol berupa kata kerja: "Simpan Perubahan", "Buat Booking", "Terbitkan Invoice".
- Tanggal: "Senin, 3 Agustus 2026". Jam: "09.00". Uang: "Rp 450.000".
- Istilah konsisten: **Booking** (bukan "pesanan"), **Servis** (bukan "service"),
  **Kendaraan** (bukan "mobil" di konteks data), **Paket Layanan** (bukan "jenis service").
- Pesan galat menjelaskan apa yang harus dilakukan, bukan menyalahkan pengguna.

## Responsif

Titik henti: `< 640` satu kolom · `640–1023` dua kolom · `≥ 1024` tiga–empat kolom.
Target sentuh ≥ 44×44px. Uji setiap halaman pada 360px sebelum dianggap selesai.

## Gerak

- Transisi 150–250ms, `ease-out`.
- Hormati `prefers-reduced-motion`: matikan autoplay slider dan animasi melayang.
- Tanpa animasi yang menunda interaksi.
