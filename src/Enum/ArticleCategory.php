<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleCategory: string
{
    case NEWS = 'NEWS';
    case FIELD_ACTIVITY = 'FIELD_ACTIVITY';
    case ANNOUNCEMENT = 'ANNOUNCEMENT';
    case RECRUITMENT = 'RECRUITMENT';

    public function label(): string
    {
        return match ($this) {
            self::NEWS => 'Actualité',
            self::FIELD_ACTIVITY => 'Activité de terrain',
            self::ANNOUNCEMENT => 'Communiqué',
            self::RECRUITMENT => 'Recrutement',
        };
    }
}
