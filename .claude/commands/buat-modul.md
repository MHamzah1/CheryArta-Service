---
description: Buat satu modul CRUD lengkap (migration, model, policy, request, controller, halaman React, uji) sesuai rancangan di docs/
argument-hint: <NamaModel> [audiens: admin|customer|public]
---

Buat modul lengkap untuk: **$ARGUMENTS**

## Langkah wajib, berurutan

1. **Baca rancangan dulu.** Buka `docs/04-skema-database.md` untuk definisi tabel model ini, dan
   `docs/07-modul-admin.md` bila audiensnya admin. Kalau modelnya tidak ada di sana, berhenti dan
   tanyakan — jangan mengarang skema.

2. **Migration** — ikuti `.claude/rules/30-database.md`: foreign key eksplisit beserta perilaku
   hapusnya, index untuk kolom yang difilter, `down()` yang benar, soft delete bila dokumen
   menyebutkannya.

3. **Model** — `$fillable` eksplisit, `casts()` untuk enum/tanggal/decimal, relasi, scope yang
   dipakai berulang. Tanpa logika bisnis.

4. **Factory** — untuk keperluan uji.

5. **Policy** — `viewAny`, `view`, `create`, `update`, `delete` sesuai matriks hak akses di
   `docs/09-keamanan-hak-akses.md §9.3`. Daftarkan di `AuthServiceProvider`.

6. **Form Request** — `Store…Request` dan `Update…Request`, pesan galat berbahasa Indonesia dan
   spesifik. Field yang ditentukan server tidak boleh ada di sini.

7. **Controller** — di `Http/Controllers/{Admin|Customer|Public}/`, tipis: otorisasi → validasi →
   Service (bila ada aturan bisnis) → `Inertia::render`/`redirect`. Daftar selalu dipaginasi dan
   difilter di server.

8. **Rute** — di `routes/web.php`, di dalam grup middleware yang tepat, dengan nama rute
   mengikuti pola `admin.*` / `customer.*`. URL berbahasa Indonesia.

9. **Halaman React** — `resources/js/pages/…` dengan `interface Props` bertipe, `<Head>`, layout
   yang sesuai. Pakai komponen bersama yang sudah ada (`DataTable`, `StatusBadge`, `EmptyState`),
   jangan membuat duplikatnya. Ikuti `.claude/rules/20-frontend-react-inertia.md` dan `40-ui-design-system.md`.

10. **Tipe TypeScript** — tambahkan di `resources/js/types/`.

11. **Uji Pest** — jalur sukses, jalur validasi gagal, dan jalur otorisasi ditolak untuk setiap
    role yang tidak berhak.

12. **Verifikasi** — jalankan `php artisan test`, `./vendor/bin/pint`, `npx tsc --noEmit`.
    Laporkan hasilnya apa adanya.

## Jangan

- Jangan menaruh aturan bisnis di controller atau komponen React.
- Jangan memakai `$guarded = []`.
- Jangan menampilkan tombol aksi tanpa pemeriksaan otorisasi di server.
- Jangan menyatakan selesai bila ada perintah verifikasi yang gagal.
