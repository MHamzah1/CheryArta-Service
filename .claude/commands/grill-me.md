---
description: Wawancarai saya sampai satu fitur benar-benar dipahami bersama, lalu catat setiap keputusan ke dokumen grill
argument-hint: <topik fitur, mis. "invoice servis" atau "dashboard admin">
---

Wawancarai saya tentang: **$ARGUMENTS**

Sebelum ada kode, kita harus sepaham dulu.

**Jangan** membuat PRD atau menulis kode sampai saya bilang selesai (`selesai`, `cukup`,
`tulis PRD`).

## Keluaran: selalu ditulis ke dokumen (WAJIB)

**Jangan mengandalkan percakapan sebagai sumber kebenaran.** Percakapan bisa terpangkas; dokumen
bertahan.

1. **Buat atau perbarui** dokumen grill di awal sesi:
   `docs/grills/grill-[topik-pendek].md` (kebab-case, mis. `docs/grills/grill-invoice-servis.md`)
2. **Setiap pertanyaan beserta rekomendasi jawabannya** masuk ke berkas itu — bukan hanya ke balasan chat.
3. Susun tiap butir dengan:
   - **Pertanyaan**
   - **Rekomendasi** (beserta alasan singkat)
   - **Keputusan saya** (`[ ]` belum · `[x]` disetujui · `[~]` dilewati)
   - **Catatan** (opsional)
4. **Balasan chat tetap pendek**: status + tautan ke dokumen + apa yang perlu saya konfirmasi
   (maksimal 1–3 poin).
5. Setelah setiap jawaban saya, **perbarui dokumennya saat itu juga**.
6. Saat sesi ditutup, tambahkan tabel **Log Keputusan** dan tunjuk dokumen berikutnya
   (`docs/prds/prd-[topik].md` lewat `/write-prd`).

Bila saya minta **"semua pertanyaan sekaligus"**: tuang seluruh daftar tanya-jawab ke dokumen
grill dalam satu tarikan; chat cukup menautkannya.

## Cara mengerjakannya

### 1. Telusuri dulu, jangan langsung bertanya

Baca sebelum menyusun pertanyaan:

- `CLAUDE.md` dan seluruh `.claude/rules/00-…` sampai `60-…`
- Rancangan yang relevan di `docs/` — gunakan tabel penunjuk di `.claude/rules/00-konteks-proyek.md`
- Kode yang sudah ada untuk hal serupa (model, service, halaman, uji)
- `docs/10-roadmap-implementasi.md` — apakah fitur ini sudah punya nomor sub-fase dan estimasi

### 2. Jangan tanyakan yang sudah dijawab rancangan

Ini yang paling sering membuang waktu. **Tujuh keputusan final** di
`.claude/rules/00-konteks-proyek.md` tidak boleh dibuka ulang tanpa persetujuan saya — jangan
menjadikannya pertanyaan:

tanpa Firebase · Inertia bukan API terpisah · aturan slot (2 per jam, H-1, Minggu tutup,
08:00–14:00) · WhatsApp klik-to-chat manual · login email + password dengan nomor WA wajib ·
dua role internal + customer · antarmuka Bahasa Indonesia, `Asia/Jakarta`, IDR.

Hal yang sudah ditentukan oleh skema (`docs/04`), alur bisnis (`docs/05`), matriks hak akses
(`docs/09`), atau aturan proyek juga **tidak perlu ditanyakan**. Catat di bagian
**Keputusan implisit** (tabel atau poin) berikut sumbernya, lalu lanjut.

Sisanya: tanyakan hanya yang benar-benar mengubah bentuk pekerjaan. Pertanyaan sepele dilewati
kecuali saya minta cakupan penuh.

### 3. Wawancarai

- **Bawaan:** satu pertanyaan **kritis** setiap kali — rekomendasi Anda dulu, tunggu jawaban,
  **perbarui dokumen**, baru pertanyaan berikutnya.
- **Bila saya minta:** seluruh pertanyaan + rekomendasi sekaligus di dokumen grill. Tetap tidak
  ada PRD atau kode sampai saya bilang selesai.

### 4. Wilayah yang perlu digali (seperlunya, tidak harus semua)

| Wilayah | Yang khas di proyek ini |
|---------|-------------------------|
| **Lingkup** | Masuk Big Fase 1 atau 2? Sudah ada nomornya di `docs/10`? |
| **Pengguna** | `customer`, `service_advisor`, `super_admin` — mana yang menyentuh fitur ini? |
| **Data** | Tabel baru atau kolom baru? Sesuai `docs/04-skema-database.md`? Butuh soft delete? |
| **Perilaku** | Jalur utama, jalur alternatif, jalur gagal — bandingkan dengan `docs/05-alur-bisnis.md` |
| **Kasus tepi** | Daftar kosong, slot penuh, dua permintaan bersamaan, data sebagian, tanggal lewat |
| **Hak akses** | Baris mana di matriks `docs/09 §9.3`? Perlu Policy baru? Advisor boleh atau tidak? |
| **Uang & waktu** | `decimal:2`, dihitung di server. Tanggal `Asia/Jakarta`, bukan UTC |
| **Antarmuka** | Layout mana (`PublicLayout`/`CustomerLayout`/`AdminLayout`)? Komponen bersama yang sudah ada? |
| **Selesai bila** | Kriteria yang bisa diperiksa manusia di bawah 5 menit |
| **Non-goal** | Apa yang sengaja **tidak** diselesaikan pekerjaan ini |

### 5. Bila fitur ini bertabrakan dengan rancangan

`CLAUDE.md` menetapkan: bila kode dan dokumen bertentangan, **dokumen yang benar**. Jadi kalau
yang saya minta menyimpang dari `docs/`, jangan diam-diam mengikutinya. Katakan dokumen mana yang
dilanggar, lalu tanyakan: kita ikuti dokumennya, atau dokumennya yang diperbarui? Catat
jawabannya di dokumen grill — nanti PRD-nya menyalin itu.

## Aturan

- **Dokumen yang berlaku** — bila chat dan dokumen berbeda, dokumen menang setelah saya konfirmasi.
- Selalu beri rekomendasi Anda dulu, baru tanya apakah saya setuju.
- **Jangan asal setuju.** Uji premis saya, sebutkan konsekuensinya, katakan bila Anda tidak akan
  merilis jalur yang saya usulkan.
- Bila saya bilang **"lewati"** atau **"berikutnya"**, lanjut; tandai `[~]` di dokumen.
- Bila jawaban saya kabur, satu pertanyaan susulan yang ringkas; hasilnya masuk **Catatan**.
- Teruskan sampai saya bilang **"selesai"**, **"cukup"**, atau **"tulis PRD"**.
- Tandai risiko di dokumen dengan format:

  `⚠️ Potensi masalah: [apa] → [kenapa penting] → [usulan penanganan]`

- Cacat sistem lama di `docs/01 §1.3` adalah bahan pertanyaan yang bagus — bila fitur ini
  berpeluang mengulanginya, tanyakan sebelum dibangun, jangan sesudah.

## Berikutnya

| Sesudah grill | Perintah / keluaran |
|---------------|---------------------|
| Dokumen kebutuhan | `/write-prd` → `docs/prds/prd-[topik].md` |
| Daftar pekerjaan | `/prd-to-issues` → `docs/issues/issue-XX-[nama].md` |
| Standar penulisan kode | `.claude/rules/`, `CLAUDE.md` |
