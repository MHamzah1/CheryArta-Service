<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case ServiceAdvisor = 'service_advisor';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::ServiceAdvisor => 'Service Advisor',
            self::Customer => 'Customer',
        };
    }

    /** Role yang boleh mengakses panel internal /admin. */
    public function isStaff(): bool
    {
        return in_array($this, [self::SuperAdmin, self::ServiceAdvisor], true);
    }

    /** Nilai role staf, untuk klausa where/whereIn. */
    public static function staffValues(): array
    {
        return [self::SuperAdmin->value, self::ServiceAdvisor->value];
    }

    /** Pilihan untuk dropdown di panel admin. */
    public static function options(): array
    {
        return array_map(
            fn (self $role) => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
