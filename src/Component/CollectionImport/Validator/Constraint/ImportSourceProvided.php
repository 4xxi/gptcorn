<?php

declare(strict_types=1);

namespace App\Component\CollectionImport\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class ImportSourceProvided extends Constraint
{
    public string $message = 'Please provide either a file or a URL.';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
