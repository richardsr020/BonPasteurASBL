<?php

declare(strict_types=1);

namespace App\Enum;

enum ArchiveAction: string
{
    case UPLOAD = 'UPLOAD';
    case UPDATE_FILE = 'UPDATE_FILE';
    case VIEW_PREVIEW = 'VIEW_PREVIEW';
    case DOWNLOAD = 'DOWNLOAD';
    case MOVE = 'MOVE';
    case SOFT_DELETE = 'SOFT_DELETE';
    case RESTORE = 'RESTORE';
    case PURGE = 'PURGE';
    case EXPORT_PDF = 'EXPORT_PDF';
}
