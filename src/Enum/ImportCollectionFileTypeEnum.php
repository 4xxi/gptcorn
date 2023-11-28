<?php

declare(strict_types=1);

namespace App\Enum;

enum ImportCollectionFileTypeEnum: string
{
    case JSON = 'json';
    case CSV = 'csv';
}
