# Aturan 20 — Frontend (React + Inertia + TypeScript)

## Struktur Halaman

Setiap berkas di `resources/js/pages/` wajib punya:

1. `interface Props` bertipe — **tanpa `any`**
2. `<Head title="…" />`
3. Layout yang sesuai (`PublicLayout` / `CustomerLayout` / `AdminLayout`)
4. Ekspor default berupa komponen fungsi

```tsx
interface Props {
  bookings: Paginated<Booking>;
  filters: BookingFilters;
}

export default function BookingIndex({ bookings, filters }: Props) { … }
```

## Aturan Data

- Data **selalu** datang dari props Inertia. Dilarang `fetch`/`axios` untuk data halaman.
  Pengecualian satu-satunya: ketersediaan slot (`GET /booking/slots`), karena bergantung pada
  tanggal yang dipilih pengguna.
- Dilarang menyimpan salinan props ke state kecuali memang perlu disunting.
- Navigasi memakai `<Link>` atau `router.visit()`, bukan `window.location`.
- Form memakai `useForm` dari `@inertiajs/react`; galat diambil dari `errors`, bukan divalidasi ulang sendiri.
- Filter tabel memakai query string + `router.get(url, params, { preserveState: true, replace: true })`.

## Aturan Otorisasi di UI

Keputusan boleh/tidak **selalu** datang dari server sebagai props boolean (`canEdit`, `canCancel`).
Dilarang menyimpulkan sendiri dari `auth.user.role` di komponen untuk menentukan aksi berbahaya —
menyembunyikan menu berdasarkan role boleh, tetapi itu hanya kenyamanan, bukan pengaman.

## Komponen

- shadcn/ui sebagai dasar (`components/ui/`) — jangan menyuntingnya sembarangan; bungkus bila perlu.
- Komponen bersama di `components/`, komponen khusus domain di `components/{booking,admin}/`.
- Satu komponen = satu tanggung jawab. Lebih dari ±150 baris, pecah.
- Nama berkas kebab-case, nama komponen PascalCase.
- Dilarang menduplikasi `StatusBadge`, `PlateInput`, `PhoneInput`, `DataTable` — gunakan yang ada.

## Styling

- Tailwind saja. Dilarang CSS global baru dan `style={{…}}` inline kecuali nilainya dinamis
  (mis. lebar bar grafik).
- Warna **hanya** dari design token (`bg-brand-700`, `text-ink-soft`). Dilarang hex mentah di JSX.
- Warna & label status hanya dari `lib/status.ts` — satu sumber kebenaran.
- Mobile-first: kelas dasar untuk layar kecil, lalu `sm:` `md:` `lg:`.
- Tabel lebar dibungkus `overflow-x-auto`; badan halaman tidak boleh menggeser horizontal.

## Dilarang Keras

| Larangan | Alasan |
|----------|--------|
| `dangerouslySetInnerHTML` | Sumber XSS sistem lama (temuan S5) |
| `alert()`, `confirm()`, `prompt()` | Gunakan toast & `ConfirmDialog` |
| `any` dalam tipe props | Menghilangkan manfaat TypeScript |
| `console.log` tertinggal | Sampah di produksi |
| Menghitung total harga di frontend | Server sumber kebenaran (temuan invoice) |
| Menulis aturan slot (angka 2, H-1) di React | Hanya `config/booking.php` |
| Impor dari CDN | CSP melarang; semua di-bundle |

## Aksesibilitas

- Tombol ikon wajib `aria-label`; ikon dekoratif `aria-hidden="true"`.
- Fokus selalu terlihat: `focus-visible:ring-2 focus-visible:ring-brand-600`.
- Modal: fokus terperangkap, `Esc` menutup (bawaan Radix — jangan dimatikan).
- Slot penuh memakai `aria-disabled` + teks "Penuh", bukan hanya warna abu.
- Gambar wajib `alt` bermakna.

## Format Tampilan

Seluruh pemformatan di `lib/format.ts`:

```ts
formatRupiah(450000)              // "Rp 450.000"
formatTanggal('2026-08-03')       // "Senin, 3 Agustus 2026"
formatJam('09:00')                // "09.00"
formatPlat('B-1234-ABC')          // "B 1234 ABC"
formatTelepon('6281234567890')    // "0812-3456-7890"
```

Dilarang memformat tanggal/uang secara ad-hoc di dalam halaman.

## Kinerja

- Halaman admin yang berat dimuat dengan `React.lazy` bila perlu.
- Gambar memakai `loading="lazy"` kecuali gambar hero.
- Daftar panjang dipaginasi di server, bukan di-render seluruhnya.
- `useMemo`/`useCallback` hanya bila memang terbukti perlu — bukan refleks.
