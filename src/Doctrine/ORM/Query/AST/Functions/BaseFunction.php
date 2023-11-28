<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

abstract class BaseFunction extends FunctionNode
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    protected string $functionPrototype;

    /** @var string[] */
    protected array $nodesMapping = [];

    /** @var Node[] */
    protected array $nodes = [];

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $this->customiseFunction();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->feedParserWithNodes($parser);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $dispatched = [];
        foreach ($this->nodes as $node) {
            $dispatched[] = $node->dispatch($sqlWalker);
        }

        return vsprintf($this->functionPrototype, $dispatched);
    }

    abstract protected function customiseFunction(): void;

    protected function setFunctionPrototype(string $functionPrototype): void
    {
        $this->functionPrototype = $functionPrototype;
    }

    protected function addNodeMapping(string $parserMethod): void
    {
        $this->nodesMapping[] = $parserMethod;
    }

    /**
     * @throws QueryException
     */
    protected function feedParserWithNodes(Parser $parser): void
    {
        $nodesMappingCount = count($this->nodesMapping);
        $lastNode = $nodesMappingCount - 1;
        for ($i = 0; $i < $nodesMappingCount; ++$i) {
            $parserMethod = $this->nodesMapping[$i];
            /** @var Node $node */
            $node = $parser->{$parserMethod}();
            $this->nodes[$i] = $node;
            if ($i < $lastNode) {
                $parser->match(Lexer::T_COMMA);
            }
        }
    }
}
