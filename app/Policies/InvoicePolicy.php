<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;

/**
 * Hak akses invoice (docs/09 §9.3, docs/07 §A14).
 *
 * Baris matriksnya: staf boleh membuat, menyunting, menerbitkan, dan menandai
 * lunas; **void hanya Super Admin**. Customer hanya boleh melihat invoicenya
 * sendiri, dan itu pun hanya yang sudah pernah diterbitkan.
 *
 * Policy ini menjawab "siapa". "Boleh berpindah ke status mana" dijawab
 * App\Enums\InvoiceStatus lewat InvoiceService — pemisahan yang sama seperti
 * BookingStatus sejak F1.5, dan itulah yang membuat percobaan transisi mustahil
 * dijawab 403.
 *
 * `Customer\InvoiceController` tetap mengambil invoice lewat relasi
 * (`$user->invoices()`), sehingga milik orang lain tidak pernah sampai ke sini;
 * policy adalah lapis keduanya (.claude/rules/50 #2).
 */
class InvoicePolicy
{
    /** Daftar `/admin/invoices`. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staf melihat seluruh invoice — itu pekerjaannya. Pemiliknya hanya
     * melihat yang sudah pernah diterbitkan (keputusan grill Q7): draft belum
     * menjadi tagihan, dan keberadaannya bukan urusan pelanggan.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $invoice->booking->user_id === $user->getKey()
            && $invoice->isVisibleToOwner();
    }

    /**
     * Membuat invoice untuk satu booking. Apakah booking-nya memang layak
     * (sudah `completed`, belum punya invoice aktif) dijawab InvoiceService
     * dengan barisnya terkunci — bukan di sini, karena jawabannya bisa basi
     * sedetik kemudian.
     */
    public function create(User $user, Booking $booking): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->isStaff() && $invoice->isEditable();
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->isStaff();
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $user->isStaff();
    }

    /** Super Admin saja (docs/09 §9.3) — satu-satunya baris invoice yang dibatasi. */
    public function void(User $user, Invoice $invoice): bool
    {
        return $user->isSuperAdmin();
    }

    /** Unduh PDF: siapa pun yang boleh melihat invoicenya. */
    public function download(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
