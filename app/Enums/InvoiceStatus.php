<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Diterbitkan',
            self::Paid => 'Lunas',
            self::Void => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'noshow',
            self::Issued => 'confirmed',
            self::Paid => 'done',
            self::Void => 'cancel',
        };
    }

    /** Invoice yang sudah diterbitkan tidak boleh disunting lagi. */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /** Terlihat oleh customer. */
    public function isVisibleToCustomer(): bool
    {
        return in_array($this, [self::Issued, self::Paid], true);
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, match ($this) {
            self::Draft => [self::Issued, self::Void],
            self::Issued => [self::Paid, self::Void],
            self::Paid, self::Void => [],
        }, true);
    }

    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'tone' => $status->tone(),
            ],
            self::cases(),
        );
    }
}
