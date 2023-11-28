<?php

declare(strict_types=1);

namespace App\Component\CollectionImport\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class IsJsonOrCsvUrlValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsJsonOrCsvUrl) {
            throw new UnexpectedTypeException($constraint, IsJsonOrCsvUrl::class);
        }

        if ('' === (string) $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (preg_match('/\.(json|csv)$/', $value)) {
            return;
        }

        $this
            ->context
            ->buildViolation($constraint->message)
            ->addViolation()
        ;
    }
}
