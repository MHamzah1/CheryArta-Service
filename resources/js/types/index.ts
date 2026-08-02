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
