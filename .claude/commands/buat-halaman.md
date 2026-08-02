---
description: Buat satu halaman Inertia React baru beserta controller, rute, dan tipe props-nya
argument-hint: <rute/halaman> — mis. admin/bookings/show atau public/faq
---

Buat halaman: **$ARGUMENTS**

## Langkah

1. Cek `docs/06-desain-ui-ux.md §6.4 (peta situs)` dan `§6.5 (wireframe)` — kalau halaman ini
   ada wireframe-nya, ikuti. Kalau tidak ada, tanyakan tujuan halaman sebelum menulis kode.

2. **Controller method** — kirim hanya data yang dipakai halaman ini, lewat API Resource.
   Keputusan otorisasi (`canEdit`, `canCancel`, dst.) dihitung di server dan dikirim sebagai
   props boolean.

3. **Rute** — nama mengikuti pola `{audiens}.{sumberdaya}.{aksi}`, URL berbahasa Indonesia,
   di dalam grup middleware yang benar.

4. **Halaman React** dengan kerangka wajib:

```tsx
import { Head } from '@inertiajs/react';
import XLayout from '@/layouts/x-layout';
import type { … } from '@/types/…';

interface Props { … }   // tanpa any

export default function NamaHalaman({ … }: Props) {
  return (
    <XLayout>
      <Head title="…" />
      …
    </XLayout>
  );
}
```

5. **Tipe props** di `resources/js/types/`.

6. **Periksa sebelum selesai:**
   - Tampil benar pada 360px, 768px, 1280px
   - Warna hanya dari design token, status lewat `<StatusBadge>`
   - Ada `EmptyState` untuk kondisi data kosong
   - Ada penanganan galat & keadaan memuat
   - Tombol ikon punya `aria-label`
   - `npx tsc --noEmit` dan `npm run lint` bersih
