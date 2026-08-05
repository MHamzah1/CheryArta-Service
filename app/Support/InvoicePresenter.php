<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * Bentuk props invoice untuk Inertia dan PDF.
 *
 * Dipusatkan dengan alasan yang sama seperti BookingPresenter di F1.4:
 * **keamanan, bukan kerapian**. Bentuk staf (`adminDetail`) dan bentuk pemilik
 * (`ownerDetail`) berdiri berdampingan di satu berkas supaya bedanya terlihat
 * saat dibaca — bukan tersembunyi di dua controller yang berbeda. Yang tidak
 * boleh menyeberang ke sisi pelanggan: catatan internal advisor, identitas
 * pembuat invoice, dan nomor WhatsApp pelanggan lain.
 *
 * Uang dikirim APA ADANYA sebagai string `decimal:2`; yang memformatnya
 * `formatRupiah()` di `lib/format.ts`. Label & warna status tidak ada di sini —
 * itu milik `lib/status.ts` (.claude/rules/40).
 */
final class InvoicePresenter
{
    /** @return array<string, mixed> */
    public static function item(InvoiceItem $item): array
    {
        return [
            'id' => $item->getKey(),
            'type' => $item->type->value,
            'type_label' => $item->type->label(),
            'description' => $item->description,
            'qty' => $item->qty,
            'unit_price' => $item->unit_price,
            'subtotal' => $item->subtotal,
            'sort_order' => $item->sort_order,
        ];
    }

    /**
     * Bentuk paling ringkas — dipakai baris daftar A8 dan panel di detail
     * booking.
     *
     * @return array<string, mixed>
     */
    public static function summary(Invoice $invoice): array
    {
        return [
            'id' => $invoice->getKey(),
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status->value,
            'subtotal' => $invoice->subtotal,
            'discount' => $invoice->discount,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'issued_at' => $invoice->issued_at?->toIso8601String(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'payment_method' => $invoice->payment_method?->value,
            'payment_method_label' => $invoice->payment_method?->label(),
            'void_reason' => $invoice->void_reason,
            'created_at' => $invoice->created_at->toIso8601String(),
        ];
    }

    /**
     * Baris daftar `/admin/invoices` (docs/07 §A8).
     *
     * Memuat nama pelanggan dan kode booking — keduanya yang dicari advisor,
     * dan keduanya TIDAK pernah ikut ke bentuk pemilik maupun ke halaman publik.
     *
     * @return array<string, mixed>
     */
    public static function adminRow(Invoice $invoice): array
    {
        return [
            ...self::summary($invoice),
            'booking_code' => $invoice->booking->booking_code,
            'booking_date' => $invoice->booking->booking_date->toDateString(),
            'customer_name' => $invoice->booking->user->name,
            'vehicle_plate' => $invoice->booking->vehicle->plate_full,
        ];
    }

    /**
     * Penyunting invoice untuk staf.
     *
     * @return array<string, mixed>
     */
    public static function adminDetail(Invoice $invoice): array
    {
        return [
            ...self::adminRow($invoice),
            'notes' => $invoice->notes,
            'created_by_name' => $invoice->createdBy?->name,
            'items' => $invoice->items->map(self::item(...))->all(),
            'booking' => [
                'booking_code' => $invoice->booking->booking_code,
                'booking_date' => $invoice->booking->booking_date->toDateString(),
                'booking_time' => SlotTime::short($invoice->booking->booking_time),
                'package_name' => $invoice->booking->servicePackage->name,
                'vehicle_model' => $invoice->booking->vehicle->display_model,
                'vehicle_plate' => $invoice->booking->vehicle->plate_full,
                'odometer' => $invoice->booking->odometer,
                'customer_name' => $invoice->booking->user->name,
                'customer_phone' => $invoice->booking->user->phone_wa,
            ],
        ];
    }

    /**
     * Invoice yang dilihat pemiliknya sendiri.
     *
     * Empat hal SENGAJA tidak ada di sini: `notes` (catatan internal advisor),
     * `created_by_name`, nomor WhatsApp, dan nama pelanggan sebagai field
     * terpisah — pemiliknya sudah tahu siapa dirinya, dan setiap field identitas
     * yang ikut ke props adalah satu kesempatan lagi untuk bocor ke tempat yang
     * salah (.claude/rules/50).
     *
     * @return array<string, mixed>
     */
    public static function ownerDetail(Invoice $invoice): array
    {
        return [
            ...self::summary($invoice),
            'items' => $invoice->items->map(self::item(...))->all(),
            'booking' => [
                'booking_code' => $invoice->booking->booking_code,
                'booking_date' => $invoice->booking->booking_date->toDateString(),
                'booking_time' => SlotTime::short($invoice->booking->booking_time),
                'package_name' => $invoice->booking->servicePackage->name,
                'vehicle_model' => $invoice->booking->vehicle->display_model,
                'vehicle_plate' => $invoice->booking->vehicle->plate_full,
                'odometer' => $invoice->booking->odometer,
            ],
        ];
    }
}
