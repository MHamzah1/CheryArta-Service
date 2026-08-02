---
description: Periksa progres terhadap roadmap, tentukan tugas berikutnya, lalu kerjakan
argument-hint: [nomor fase, mis. 3 — kosongkan untuk deteksi otomatis]
---

Lanjutkan pengerjaan proyek sesuai roadmap. Fase: **$ARGUMENTS** (kosong = deteksi sendiri).

## 1. Tentukan posisi saat ini

- Baca `docs/10-roadmap-implementasi.md`.
- Periksa keadaan nyata repo: migration yang ada, model, controller, halaman React, uji.
- Tentukan tugas mana pada fase tersebut yang **sudah** selesai dan mana yang belum —
  berdasarkan kode yang benar-benar ada, bukan asumsi.

## 2. Laporkan sebelum mengerjakan

Tampilkan tabel ringkas: nomor tugas, status (selesai / sebagian / belum), catatan.
Lalu sebutkan tugas berikutnya yang akan dikerjakan.

## 3. Kerjakan tugas berikutnya

- Ikuti aturan di `.claude/rules/`.
- Bila tugas ini bergantung pada [pertanyaan terbuka di `docs/README.md`](../../docs/README.md)
  yang belum dijawab, **tanyakan lebih dulu** — jangan menebak.
- Penuhi seluruh butir *Definition of Done* pada `docs/10-roadmap-implementasi.md`.

## 4. Tutup dengan jujur

- Jalankan gerbang kualitas (`/cek-kualitas`).
- Laporkan apa yang selesai, apa yang belum, dan apa yang terhambat.
- Bila dokumen di `docs/` menjadi tidak akurat karena perubahan ini, perbarui dokumennya.
