<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class JsonContains extends FunctionNode
{
    private Node $haystack;
    private InputParameter $needle;

    /**
     * @throws QueryException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            '(%s::jsonb @> %s::jsonb)',
            $this->haystack->dispatch($sqlWalker),
            $sqlWalker->walkInputParameter($this->needle)
        );
    }

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->haystack = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->needle = $parser->InputParameter();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
