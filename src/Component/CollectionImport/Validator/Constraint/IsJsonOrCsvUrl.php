<?php

declare(strict_types=1);

namespace App\Component\CollectionImport\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class IsJsonOrCsvUrl extends Constraint
{
    public string $message = 'The URL must link to a JSON or CSV file.';

    public function getTargets(): array|string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
