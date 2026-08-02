# Aturan 60 — Testing & Git

## Testing

Kerangka: **Pest** untuk PHP, **Vitest** untuk util TypeScript murni.

### Wajib punya uji

| Area | Uji minimum |
|------|-------------|
| Aturan slot | Kuota penuh ditolak · dua permintaan bersamaan tidak menembus kuota · booking hari ini ditolak (H-1) · hari Minggu ditolak · booking > 60 hari ditolak |
| Status booking | Setiap transisi sah berhasil · transisi tidak sah menghasilkan 422 · riwayat status tertulis |
| Otorisasi | Customer tidak bisa membuka booking milik orang lain · customer ditolak di seluruh rute `/admin` · Advisor ditolak pada rute khusus Super Admin |
| Autentikasi | Registrasi menormalisasi nomor WA · akun nonaktif tidak bisa login · rate limit login bekerja |
| Invoice | Total dihitung server · invoice `issued` tidak bisa disunting · hanya SA yang boleh `void` |
| Kendaraan | Plat 1 **dan** 2 huruf diterima (temuan B1) · plat kembar milik satu pengguna ditolak |
| Utilitas | `PhoneNumber::normalize` untuk `08…`, `62…`, `+62…`, format bertanda hubung |

### Cara menulis

- Nama uji berupa kalimat: `it('menolak booking pada hari Minggu')`.
- Gunakan Factory, jangan menulis data manual.
- Uji fitur menembak rute sungguhan (`$this->actingAs($user)->post(...)`), bukan memanggil service langsung — kecuali uji unit.
- Satu perilaku per uji; jangan menumpuk lima asersi tak berkaitan.
- `RefreshDatabase` untuk uji yang menyentuh database.

### Sebelum menyatakan selesai

```bash
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse
npm run lint && npx tsc --noEmit
npm run build
```

Semua harus lulus. Kalau ada yang gagal, laporkan apa adanya — jangan menyatakan selesai.

## Git

- Branch: `main` (stabil) · `feat/…` · `fix/…` · `chore/…`
- Dilarang commit langsung ke `main` untuk perubahan besar.
- Pesan commit mengikuti Conventional Commits, berbahasa Indonesia:

```
feat(booking): tambah pemilih slot dengan info ketersediaan
fix(auth): normalisasi nomor WA sebelum disimpan
refactor(slot): pindahkan aturan kuota ke SlotService
docs(rancangan): perbarui skema invoice
test(booking): tambah uji kuota bersamaan
```

- Satu commit = satu perubahan logis. Jangan mencampur format ulang dengan perubahan perilaku.
- Jangan commit: `.env`, `storage/*`, `node_modules`, `public/build`, `public/storage`, dump database.
- Jangan pernah menyertakan kredensial nyata di repo, termasuk di seeder dan dokumen.

## Sebelum Membuat Pull Request

- [ ] `php artisan test` lulus
- [ ] Pint, PHPStan, ESLint, `tsc --noEmit` bersih
- [ ] Diuji manual pada 360px dan 1280px
- [ ] Tidak ada `console.log`, `dd()`, `dump()`, atau data contoh yang tertinggal
- [ ] Migration baru sudah diuji `migrate` **dan** `rollback`
- [ ] Dokumen di `docs/` diperbarui bila perilaku atau skema berubah
