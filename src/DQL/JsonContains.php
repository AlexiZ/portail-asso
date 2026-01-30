<?php

declare(strict_types=1);

namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * JSON_CONTAINS(json_doc, value).
 */
final class JsonContains extends FunctionNode
{
    private Node $jsonDocument;
    private Node $value;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->jsonDocument = $parser->StringPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->value = $parser->StringPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'JSON_CONTAINS(%s, %s)',
            $this->jsonDocument->dispatch($sqlWalker),
            $this->value->dispatch($sqlWalker)
        );
    }
}
