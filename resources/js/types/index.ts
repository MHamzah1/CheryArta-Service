import { LucideIcon } from 'lucide-react';

export type UserRole = 'super_admin' | 'service_advisor' | 'customer';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** Role yang boleh melihat menu ini. Kosong = semua yang sudah login. */
    roles?: UserRole[];
}

/**
 * Profil perusahaan dari config/company.php, dibagikan lewat shared props.
 * Halaman TIDAK BOLEH menulis alamat/telepon langsung di JSX.
 */
export interface Company {
    name: string;
    tagline: string;
    address: {
        street: string;
        city: string;
        province: string;
        country: string;
        full: string;
    };
    phones: {
        landline: string;
        mobile: string;
    };
    email: string;
    wa_number: string;
    operating_hours_text: string[];
    social: {
        instagram: string | null;
        facebook: string | null;
        maps_url: string | null;
    };
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
    info?: string | null;
}

export interface SharedData {
    name: string;
    /** Asal URL absolut (tanpa garis miring akhir), untuk tag Open Graph. */
    appUrl: string;
    auth: Auth;
    company: Company;
    flash: FlashMessages;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    phone_wa: string | null;
    role: UserRole;
    is_active: boolean;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

/** Ringkasan model katalog untuk dropdown. */
export interface CarModelOption {
    id: number;
    name: string;
}

export interface Vehicle {
    id: number;
    car_model_id: number | null;
    /** Terisi bila kendaraannya bukan Chery — lihat pertanyaan terbuka Q3. */
    model_name_manual: string | null;
    plate_prefix: string;
    plate_number: string;
    plate_suffix: string;
    /** Bentuk gabungan `B-1234-ABC`, disusun server. */
    plate_full: string;
    year: number | null;
    color: string | null;
    vin: string | null;
    last_odometer: number | null;
    is_primary: boolean;
    car_model?: CarModelOption | null;
}

/** Pilihan dropdown yang datang dari PHP Enum (`Enum::options()`). */
export interface SelectOption {
    value: string;
    label: string;
}

/**
 * Spesifikasi disunting sebagai pasangan berurutan supaya urutannya terjaga
 * dan kunci kembar bisa ditolak; server menyimpannya sebagai objek.
 */
export interface SpecPair {
    key: string;
    value: string;
    /** Dituntut `FormDataConvertible` milik Inertia agar bisa ikut di `useForm`. */
    [field: string]: string;
}

/** Baris daftar paket layanan di /admin/paket-layanan. */
export interface ServicePackageRow {
    id: number;
    code: string;
    name: string;
    category: string;
    category_label: string;
    applicable_series: string[];
    estimated_duration_minutes: number;
    price: string | number;
    is_free: boolean;
    is_active: boolean;
    sort_order: number;
    /** Dihitung server — paket yang sudah dipakai booking tidak bisa dihapus. */
    can_delete: boolean;
}

/** Paket layanan lengkap untuk form ubah. */
export interface ServicePackage {
    id: number;
    code: string;
    name: string;
    category: string;
    description: string | null;
    applicable_series: string[] | null;
    estimated_duration_minutes: number;
    price: string | number;
    is_free: boolean;
    is_active: boolean;
    sort_order: number;
}

/** Baris daftar katalog di /admin/katalog. */
export interface CarModelRow {
    id: number;
    name: string;
    slug: string;
    category_label: string;
    fuel_type_label: string;
    series_code: string | null;
    price_start: string | number | null;
    is_active: boolean;
    sort_order: number;
    variants_count: number;
    images_count: number;
    vehicles_count: number;
    thumbnail_url: string | null;
    thumbnail_alt: string | null;
    /** Model yang sudah dipakai kendaraan pelanggan hanya bisa dinonaktifkan. */
    can_delete: boolean;
}

/** Model katalog lengkap untuk form ubah. */
export interface CarModelDetail {
    id: number;
    name: string;
    slug: string;
    category: string;
    fuel_type: string;
    series_code: string | null;
    price_start: string | number | null;
    short_description: string;
    description: string | null;
    specs: Record<string, string> | null;
    brochure_url: string | null;
    is_active: boolean;
    sort_order: number;
}

export interface CarModelVariant {
    id: number;
    name: string;
    price: string | number | null;
    specs: Record<string, string> | null;
    is_active: boolean;
    sort_order: number;
}

export interface CarModelImage {
    id: number;
    url: string;
    alt: string;
    is_primary: boolean;
    sort_order: number;
}

/**
 * Batas kalender booking, dihitung server dari `config/booking.php`.
 * Angka aturan booking TIDAK boleh ditulis ulang di React (temuan B2).
 */
export interface SlotRules {
    /** Web paling cepat besok; walk-in boleh hari ini — dihitung server. */
    earliest_date: string;
    latest_date: string;
    /** 0 = Minggu … 6 = Sabtu. */
    closed_weekdays: number[];
    quota_per_slot: number;
    /** `web` atau `walk_in`; menentukan aturan H-1 yang dipakai server. */
    source: string;
}

export interface SlotAvailability {
    /** Bentuk `H:i`, mis. "09:00". */
    time: string;
    remaining: number;
    is_full: boolean;
}

/** Jawaban `GET /booking/slots?date=` — satu-satunya endpoint JSON alur booking. */
export interface SlotResponse {
    date: string;
    is_bookable: boolean;
    /** Kalimat siap tampil bila tanggalnya tidak bisa dipesan. */
    reason: string | null;
    slots: SlotAvailability[];
}

export interface BookingVehicleOption {
    id: number;
    plate_full: string;
    display_model: string;
    year: number | null;
    last_odometer: number | null;
    is_primary: boolean;
    series_code: string | null;
}

export interface ServicePackageOption {
    id: number;
    name: string;
    description: string | null;
    applicable_series: string[];
    estimated_duration_minutes: number;
    price: string | number;
    is_free: boolean;
}

/** Bentuk ringkas booking, dipakai daftar riwayat. */
export interface BookingSummary {
    booking_code: string;
    booking_date: string;
    booking_time: string;
    status: string;
    estimated_finish_at: string | null;
    vehicle_plate: string;
    vehicle_model: string;
    package_name: string;
    package_price: string | number;
    package_is_free: boolean;
}

export interface BookingDetail extends BookingSummary {
    complaint: string | null;
    odometer: number | null;
    cancel_reason: string | null;
    source: string;
    package_description: string | null;
    package_duration_minutes: number;
    rescheduled_from: { booking_code: string; booking_date: string; booking_time: string } | null;
}

export interface TimelineEntry {
    id: number;
    from_status: string | null;
    to_status: string;
    note: string | null;
    created_at: string | null;
}

/**
 * Hasil pelacakan publik. Sengaja TIDAK memuat nama, telepon, plat, atau
 * keluhan — lihat App\Support\BookingPresenter::publicTracking().
 */
export interface PublicTracking {
    booking_code: string;
    booking_date: string;
    booking_time: string;
    status: string;
    estimated_finish_at: string | null;
}

/* ---------------------------------------------------------------------------
 * Halaman publik — F1.6 (docs/06 §6.4)
 *
 * Seluruh bentuk di bawah disusun App\Support\PublicContent. Tidak satu pun
 * memuat nama lengkap, telepon, email, atau plat pelanggan: nama pada
 * testimoni adalah nama yang diketik admin, bukan `users.name` (docs/09 §9.4).
 * ------------------------------------------------------------------------- */

export interface PublicServicePackage {
    id: number;
    name: string;
    category_label: string;
    description: string | null;
    estimated_duration_minutes: number;
    price: string | number;
    is_free: boolean;
}

export interface ServicePackageGroup {
    label: string;
    packages: PublicServicePackage[];
}

/** Kartu katalog di beranda dan di `/katalog`. */
export interface CarModelCard {
    name: string;
    slug: string;
    category: string;
    category_label: string;
    fuel_type: string;
    fuel_type_label: string;
    price_start: string | number | null;
    short_description: string;
    thumbnail_url: string | null;
    thumbnail_alt: string | null;
}

/** Model katalog versi publik — tanpa kolom pengelolaan (is_active, sort_order). */
export interface PublicCarModel {
    name: string;
    slug: string;
    category_label: string;
    fuel_type_label: string;
    price_start: string | number | null;
    short_description: string;
    description: string | null;
    specs: Record<string, string> | null;
    brochure_url: string | null;
}

export interface PublicCarModelImage {
    id: number;
    thumbnail_url: string;
    url: string;
    alt: string;
    is_primary: boolean;
}

export interface PublicCarModelVariant {
    id: number;
    name: string;
    price: string | number | null;
    specs: Record<string, string> | null;
}

export interface CatalogFilters {
    kategori: string | null;
    bahan_bakar: string | null;
}

export interface PublicFacility {
    id: number;
    title: string;
    description: string;
    thumbnail_url: string | null;
    image_url: string | null;
}

export interface PublicFaq {
    id: number;
    question: string;
    answer: string;
    category: string | null;
}

export interface FaqGroup {
    label: string;
    faqs: PublicFaq[];
}

export interface PublicTestimonial {
    id: number;
    customer_name: string;
    car_model: string | null;
    rating: number;
    content: string;
}

/**
 * Ketersediaan hari buka terdekat untuk kartu di hero. Tanggalnya dihitung
 * SlotService — jangan pernah menyimpulkan "besok" di React (temuan B2).
 */
export interface SlotPreview {
    date: string;
    slots: SlotAvailability[];
}

/** Keunggulan dari config/company.php; `icon` dipetakan di lib/icon-map.ts. */
export interface CompanyAdvantage {
    icon: string;
    title: string;
}

/* ---------------------------------------------------------------------------
 * Panel admin — A2 Booking, A3 Jadwal, A6 Customer & Kendaraan (docs/07)
 * ------------------------------------------------------------------------- */

/** Baris daftar booking di /admin/bookings. */
export interface AdminBookingRow {
    booking_code: string;
    booking_date: string;
    booking_time: string;
    status: string;
    source: string;
    source_label: string;
    customer_name: string;
    vehicle_plate: string;
    vehicle_model: string;
    package_name: string;
    handled_by_name: string | null;
}

export interface AdminBookingDetail extends AdminBookingRow {
    complaint: string | null;
    odometer: number | null;
    admin_note: string | null;
    cancel_reason: string | null;
    estimated_finish_at: string | null;
    customer: {
        id: number;
        name: string;
        email: string;
        phone_wa: string | null;
        is_active: boolean;
    };
    vehicle: {
        id: number;
        plate_full: string;
        display_model: string;
        year: number | null;
        last_odometer: number | null;
    };
    package_price: string | number;
    package_is_free: boolean;
    package_duration_minutes: number;
    confirmed_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    created_at: string | null;
    rescheduled_from: { booking_code: string; booking_date: string; booking_time: string } | null;
}

/** Timeline versi advisor — sama dengan customer, ditambah pelakunya. */
export interface AdminTimelineEntry extends TimelineEntry {
    actor_name: string | null;
}

/**
 * Status tujuan yang sah dari status sekarang, disusun server dari
 * App\Enums\BookingStatus. Jangan pernah menyusun daftar ini di React.
 */
export interface StatusTransitionOption {
    value: string;
    label: string;
    tone: string;
    requires_note: boolean;
}

/** Draft klik-to-chat; null bila pelanggan tidak punya nomor WhatsApp. */
export interface WhatsAppDraft {
    url: string;
    message: string;
    phone_display: string;
}

/** Keadaan saringan daftar booking, dikembalikan server apa adanya. */
export interface AdminBookingFilters {
    cari: string | null;
    status: string | null;
    dari: string | null;
    sampai: string | null;
    paket: number | null;
    advisor: number | null;
    urutan: string;
}

/** Satu baris slot di jadwal harian. */
export interface ScheduleRow {
    time: string;
    remaining: number;
    is_full: boolean;
    bookings: ScheduleCard[];
}

export interface ScheduleCard {
    booking_code: string;
    status: string;
    customer_name: string;
    vehicle_plate: string;
    vehicle_model: string;
    package_name: string;
}

/** Baris daftar customer di /admin/customers. */
export interface CustomerRow {
    id: number;
    name: string;
    email: string;
    phone_wa: string | null;
    is_active: boolean;
    vehicles_count: number;
    bookings_count: number;
    last_service_date: string | null;
}

export interface CustomerDetail {
    id: number;
    name: string;
    email: string;
    phone_wa: string | null;
    address: string | null;
    is_active: boolean;
    must_reset_password: boolean;
    created_at: string | null;
    last_login_at: string | null;
}

export interface CustomerStats {
    total_bookings: number;
    completed_bookings: number;
    upcoming_bookings: number;
    last_service_date: string | null;
}

export interface CustomerVehicleRow {
    id: number;
    plate_full: string;
    display_model: string;
    year: number | null;
    color: string | null;
    last_odometer: number | null;
    is_primary: boolean;
    bookings_count: number;
}

/** Baris daftar kendaraan di /admin/vehicles. */
export interface AdminVehicleRow {
    id: number;
    plate_full: string;
    display_model: string;
    year: number | null;
    color: string | null;
    last_odometer: number | null;
    services_count: number;
    last_service_date: string | null;
    owner: { id: number; name: string };
}

/** Hasil pencarian pelanggan di form walk-in. */
export interface WalkInCustomerResult {
    id: number;
    name: string;
    email: string;
    phone_wa: string | null;
    is_active: boolean;
    vehicles_count: number;
}

/** Pelanggan yang sedang dipilih di form walk-in, beserta kendaraannya. */
export interface WalkInSelectedCustomer {
    id: number;
    name: string;
    email: string;
    phone_wa: string | null;
    is_active: boolean;
    vehicles: BookingVehicleOption[];
}

/** Bentuk paginasi Laravel, dipakai seluruh tabel daftar. */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}
