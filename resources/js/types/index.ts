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
