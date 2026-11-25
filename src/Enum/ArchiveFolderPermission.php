<?php

declare(strict_types=1);

namespace App\Enum;

enum ArchiveFolderPermission: string
{
    case VIEW = 'VIEW';
    case UPLOAD = 'UPLOAD';
    case EDIT = 'EDIT';
    case DELETE = 'DELETE';
    case SOFT_DELETE = 'SOFT_DELETE';
    case EXPORT = 'EXPORT';
}
