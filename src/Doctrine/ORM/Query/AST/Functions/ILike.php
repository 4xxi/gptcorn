<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

final class ILike extends BaseFunction
{
    protected function customiseFunction(): void
    {
        $this->setFunctionPrototype('%s ILIKE %s');
        $this->addNodeMapping('StringPrimary');
        $this->addNodeMapping('StringPrimary');
    }
}
