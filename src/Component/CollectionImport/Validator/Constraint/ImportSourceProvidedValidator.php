<?php

declare(strict_types=1);

namespace App\Component\CollectionImport\Validator\Constraint;

use App\Entity\CollectionImport;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ImportSourceProvidedValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ImportSourceProvided) {
            throw new UnexpectedTypeException($constraint, ImportSourceProvided::class);
        }

        if (!$value instanceof CollectionImport) {
            throw new UnexpectedValueException($value, CollectionImport::class);
        }

        $githubUrl = $value->getGithubUrl();
        $fileUpload = $value->getFileUpload();
        if (
            null !== $githubUrl
            || null !== $fileUpload
        ) {
            return;
        }

        $this
            ->context
            ->buildViolation($constraint->message)
            ->addViolation()
        ;
    }
}
