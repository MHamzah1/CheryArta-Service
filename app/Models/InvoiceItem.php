<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris rincian invoice (docs/04 §4.2).
 *
 * `subtotal` disimpan, bukan dihitung ulang saat dibaca: harga bisa berubah
 * sesudah invoice terbit, dan tagihan yang sudah dipegang pelanggan tidak boleh
 * ikut berubah. Yang menghitungnya App\Services\InvoiceService — nilai dari
 * request diabaikan.
 *
 * @property int $id
 * @property int $invoice_id
 * @property InvoiceItemType $type
 * @property string $description
 * @property string $qty
 * @property string $unit_price
 * @property string $subtotal
 * @property int $sort_order
 * @property-read Invoice $invoice
 */
class InvoiceItem extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceItemFactory> */
    use HasFactory;

    /**
     * `subtotal` sengaja TIDAK fillable — ia hasil hitungan server
     * (docs/05 §5.6), dipasang InvoiceService lewat `forceFill`.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'description',
        'qty',
        'unit_price',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => InvoiceItemType::class,
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
