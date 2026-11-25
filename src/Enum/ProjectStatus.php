<?php

declare(strict_types=1);

namespace App\Enum;

enum ProjectStatus: string
{
    case ONGOING = 'ONGOING';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::ONGOING => 'En cours',
            self::COMPLETED => 'Terminé',
        };
    }
}
