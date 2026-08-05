<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Concerns\RecordsActivity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tagihan satu booking yang sudah selesai (docs/04 §4.2, docs/05 §5.6).
 *
 * Model ini TIDAK menghitung total, tidak menerbitkan nomor, dan tidak
 * mengubah statusnya sendiri — semuanya milik App\Services\InvoiceService
 * (.claude/rules/10). Yang ada di sini hanyalah bentuk data dan pertanyaan
 * yang bisa dijawab satu baris tentang dirinya sendiri.
 *
 * @property int $id
 * @property int $booking_id
 * @property string|null $invoice_number
 * @property string $subtotal
 * @property string $discount
 * @property string $tax
 * @property string $total
 * @property InvoiceStatus $status
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $paid_at
 * @property PaymentMethod|null $payment_method
 * @property string|null $void_reason
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Booking $booking
 * @property-read User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InvoiceItem> $items
 */
class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Kolom invoice yang dicatat A12 (docs/07 §A12, docs/09 §9.7).
     *
     * `status` ADA di sini, berbeda dari Booking yang mengecualikannya. Booking
     * punya `booking_status_histories` sebagai sumber kebenaran timeline;
     * invoice tidak punya tabel semacam itu, dan docs/09 §9.7 mewajibkan void
     * invoice tercatat. Tanpa `status` di daftar ini, kewajiban itu tidak
     * terpenuhi oleh apa pun.
     *
     * @return list<string>
     */
    protected function activityLogAttributes(): array
    {
        return [
            'invoice_number',
            'status',
            'subtotal',
            'discount',
            'tax',
            'total',
            'payment_method',
            'void_reason',
            'notes',
        ];
    }

    /**
     * HANYA tiga kolom yang boleh datang dari request.
     *
     * `status`, `invoice_number`, `subtotal`, `total`, `issued_at`, `paid_at`,
     * `payment_method`, `void_reason`, `booking_id`, dan `created_by` ditentukan
     * server dan dipasang InvoiceService lewat `forceFill` — menerima salah
     * satunya dari request adalah larangan mutlak (.claude/rules/50).
     *
     * @var list<string>
     */
    protected $fillable = [
        'discount',
        'tax',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'payment_method' => PaymentMethod::class,
            // decimal:2, BUKAN float. Uang yang dibulatkan biner akan meleset
            // sen demi sen dan selisihnya baru terlihat di laporan bulanan.
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** Staf yang membuat invoice ini. @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Terlihat oleh pemiliknya (keputusan grill Q7).
     *
     * Patokannya `issued_at`, BUKAN status. Bedanya nyata pada invoice yang
     * pernah diterbitkan lalu di-void: dengan patokan status ia akan hilang
     * menjadi 404 dari mata pelanggan yang sudah memegang PDF-nya, padahal
     * justru itu keadaan yang paling perlu ia ketahui. Enum tidak bisa
     * membedakan void-yang-pernah-terbit dari void-yang-tidak-pernah, jadi
     * pertanyaannya dijawab di sini.
     */
    public function isVisibleToOwner(): bool
    {
        return $this->issued_at !== null;
    }

    /** Masih bisa disunting advisor. */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Invoice yang masih berlaku untuk booking-nya.
     *
     * Dipakai relasi `Booking::invoice()` dan pemeriksaan "sudah punya invoice
     * aktif" di InvoiceService. Satu definisi, dua pemakai — menuliskannya dua
     * kali berarti menunggu keduanya berselisih (cacat B2).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        // Kolomnya DIKUALIFIKASI: `bookings` juga punya kolom `status`, dan
        // scope ini dipakai di atas kueri yang mem-`join` ke sana (laporan
        // pendapatan, total invoice per customer). Tanpa prefix, SQLite
        // menolaknya sebagai "ambiguous column name" dan MySQL memilih salah
        // satu tanpa memberi tahu — yang kedua jauh lebih berbahaya.
        $query->where($query->qualifyColumn('status'), '!=', InvoiceStatus::Void->value);
    }

    /**
     * Ikut dijumlahkan laporan Pendapatan (docs/07 §A9).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeCountsAsRevenue(Builder $query): void
    {
        $query->whereIn($query->qualifyColumn('status'), InvoiceStatus::revenueValues());
    }
}
