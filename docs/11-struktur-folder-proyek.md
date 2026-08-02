# 11 — Struktur Folder Proyek

## 11.1 Pohon Direktori

```
cheryarta/
├── .claude/
│   ├── commands/                 # perintah slash khusus proyek
│   └── rules/                    # aturan koding yang wajib diikuti
├── app/
│   ├── Enums/
│   │   ├── BookingStatus.php
│   │   ├── InvoiceStatus.php
│   │   └── UserRole.php
│   ├── Exceptions/
│   │   ├── SlotFullException.php
│   │   └── InvalidStatusTransitionException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # BookingController, DashboardController, dst.
│   │   │   ├── Customer/         # BookingController, VehicleController, dst.
│   │   │   ├── Public/           # HomeController, CatalogController, dst.
│   │   │   └── Auth/             # bawaan Breeze
│   │   ├── Middleware/
│   │   │   ├── EnsureUserHasRole.php
│   │   │   └── HandleInertiaRequests.php
│   │   ├── Requests/
│   │   │   ├── Booking/StoreBookingRequest.php
│   │   │   ├── Booking/RescheduleBookingRequest.php
│   │   │   └── …
│   │   └── Resources/            # BookingResource, VehicleResource, dst.
│   ├── Models/
│   │   ├── Booking.php  BookingStatusHistory.php  CarModel.php
│   │   ├── CarModelVariant.php  CarModelImage.php  Vehicle.php
│   │   ├── ServicePackage.php  Invoice.php  InvoiceItem.php
│   │   ├── Facility.php  Faq.php  Testimonial.php  ContactMessage.php
│   │   ├── WhatsAppTemplate.php  WhatsAppMessage.php  User.php
│   ├── Policies/
│   ├── Providers/
│   ├── Services/
│   │   ├── Booking/SlotService.php
│   │   ├── Booking/BookingService.php
│   │   ├── Invoice/InvoiceService.php
│   │   └── WhatsApp/{WhatsAppNotifier.php, ClickToChatNotifier.php, WhatsAppDraft.php}
│   ├── Support/
│   │   ├── PhoneNumber.php
│   │   └── CodeGenerator.php     # kode booking & nomor invoice
│   └── Exports/                  # BookingExport (maatwebsite/excel)
├── config/
│   ├── booking.php               # SATU-SATUNYA sumber aturan slot
│   ├── company.php               # profil perusahaan
│   └── whatsapp.php              # driver & nomor perusahaan
├── database/
│   ├── factories/
│   ├── migrations/
│   ├── seeders/
│   │   ├── assets/               # 9 gambar dari sistem lama
│   │   └── *.php
├── resources/
│   ├── css/app.css               # @theme design token
│   ├── js/
│   │   ├── app.tsx               # bootstrap Inertia
│   │   ├── components/
│   │   │   ├── ui/               # shadcn/ui
│   │   │   ├── booking/          # SlotPicker, BookingSteps, …
│   │   │   ├── admin/            # DataTable, StatCard, StatusSelect, …
│   │   │   └── {status-badge,plate-input,phone-input,timeline,lightbox}.tsx
│   │   ├── layouts/
│   │   │   ├── public-layout.tsx
│   │   │   ├── customer-layout.tsx
│   │   │   └── admin-layout.tsx
│   │   ├── pages/                # cerminan struktur rute
│   │   │   ├── public/{home,catalog/index,catalog/show,services,facilities,about,faq,contact,tracking}.tsx
│   │   │   ├── auth/{login,register,forgot-password,reset-password}.tsx
│   │   │   ├── customer/{dashboard,booking/create,booking/show,history,vehicles/index,invoice/show,profile}.tsx
│   │   │   └── admin/{dashboard,schedule,bookings/*,customers/*,vehicles/*,invoices/*,reports,catalog/*,packages/*,content/*,users/*,activity-log}.tsx
│   │   ├── hooks/
│   │   ├── lib/{utils.ts,format.ts,status.ts}
│   │   └── types/{index.d.ts,booking.ts,catalog.ts,inertia.d.ts}
│   └── views/
│       ├── app.blade.php
│       └── pdf/invoice.blade.php
├── routes/web.php  console.php
├── storage/app/public/{car-models,facilities,testimonials}
├── tests/
│   ├── Feature/{Auth,Booking,Admin,Invoice}/
│   └── Unit/{SlotServiceTest,PhoneNumberTest,CodeGeneratorTest}.php
├── docs/                         # dokumen ini
└── CLAUDE.md
```

## 11.2 Konvensi Penamaan

| Jenis | Aturan | Contoh |
|-------|--------|--------|
| Tabel | jamak, snake_case | `booking_status_histories` |
| Kolom | snake_case; boolean berawalan `is_`/`has_`; waktu berakhiran `_at` | `is_active`, `confirmed_at` |
| Model | tunggal, PascalCase | `BookingStatusHistory` |
| Controller | PascalCase + `Controller`, dikelompokkan per audiens | `Admin\BookingController` |
| Form Request | `{Aksi}{Model}Request` | `StoreBookingRequest` |
| Service | kata benda + `Service` | `SlotService` |
| Rute bernama | `titik.bertingkat` | `admin.bookings.show`, `customer.booking.create` |
| Halaman React | kebab-case, cerminan rute | `pages/admin/bookings/show.tsx` |
| Komponen React | PascalCase di dalam berkas kebab-case | `StatusBadge` di `status-badge.tsx` |
| Tipe TS | PascalCase, tanpa awalan `I` | `Booking`, `BookingPageProps` |
| Kunci terjemahan/teks | Bahasa Indonesia, konsisten dengan istilah dokumen | "Sedang Dikerjakan" |

## 11.3 Aturan Penempatan Kode

| Isi | Tempatnya | Bukan di |
|-----|-----------|----------|
| Aturan slot & kuota | `SlotService` + `config/booking.php` | Controller, komponen React |
| Transisi status | `BookingService` + enum `BookingStatus` | Controller |
| Perhitungan uang | `InvoiceService` | Frontend |
| Pemformatan tampilan (rupiah, tanggal) | `lib/format.ts` | Tersebar di tiap halaman |
| Warna & label status | `lib/status.ts` + `StatusBadge` | Ditulis ulang di banyak tempat |
| Profil perusahaan | `config/company.php` → shared props | Ditulis keras di JSX |
| Query data | Model scope / Service | Komponen React |

## 11.4 Bentuk Halaman React

```tsx
// resources/js/pages/customer/booking/show.tsx
import { Head } from '@inertiajs/react';
import CustomerLayout from '@/layouts/customer-layout';
import { StatusBadge } from '@/components/status-badge';
import { Timeline } from '@/components/timeline';
import type { Booking, StatusHistory } from '@/types/booking';

interface Props {
  booking: Booking;
  histories: StatusHistory[];
  canReschedule: boolean;   // keputusan otorisasi datang dari server
  canCancel: boolean;
}

export default function BookingShow({ booking, histories, canReschedule, canCancel }: Props) {
  return (
    <CustomerLayout>
      <Head title={`Booking ${booking.code}`} />
      {/* … */}
    </CustomerLayout>
  );
}
```

Tiga hal yang wajib ada di setiap halaman: `interface Props` bertipe, `<Head title>`, dan layout
yang sesuai. Keputusan boleh-tidaknya sebuah aksi (`canReschedule`) **selalu** dihitung server —
frontend hanya menampilkannya.
