<?php

declare(strict_types=1);

namespace App\Enum;

enum PartnerType: string
{
    case TECHNICAL = 'TECHNICAL';
    case FINANCIAL = 'FINANCIAL';
    case DONOR = 'DONOR';

    public function label(): string
    {
        return match ($this) {
            self::TECHNICAL => 'Partenaire technique',
            self::FINANCIAL => 'Partenaire financier',
            self::DONOR => 'Donateur',
        };
    }
}
