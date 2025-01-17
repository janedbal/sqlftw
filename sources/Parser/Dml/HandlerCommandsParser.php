<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dml\Handler\HandlerCloseCommand;
use SqlFtw\Sql\Dml\Handler\HandlerOpenCommand;
use SqlFtw\Sql\Dml\Handler\HandlerReadCommand;
use SqlFtw\Sql\Dml\Handler\HandlerReadTarget;
use SqlFtw\Sql\Keyword;

class HandlerCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * HANDLER tbl_name OPEN [[AS] alias]
     */
    public function parseHandlerOpen(TokenList $tokenList): HandlerOpenCommand
    {
        $tokenList->expectKeyword(Keyword::HANDLER);
        $table = $tokenList->expectQualifiedName();
        $tokenList->expectKeyword(Keyword::OPEN);

        $tokenList->passKeyword(Keyword::AS);
        $alias = $tokenList->getName();

        return new HandlerOpenCommand($table, $alias);
    }

    /**
     * HANDLER tbl_name READ index_name { = | <= | >= | < | > } (value1,value2, ...)
     *     [ WHERE where_condition ] [LIMIT ... ]
     * HANDLER tbl_name READ index_name { FIRST | NEXT | PREV | LAST }
     *     [ WHERE where_condition ] [LIMIT ... ]
     * HANDLER tbl_name READ { FIRST | NEXT }
     *     [ WHERE where_condition ] [LIMIT ... ]
     */
    public function parseHandlerRead(TokenList $tokenList): HandlerReadCommand
    {
        $tokenList->expectKeyword(Keyword::HANDLER);
        $table = $tokenList->expectQualifiedName();
        $tokenList->expectKeyword(Keyword::READ);

        $values = $index = null;
        $what = $tokenList->getAnyKeyword(Keyword::FIRST, Keyword::NEXT);
        if ($what === null) {
            $index = $tokenList->getNonReservedName();
            $what = $tokenList->getAnyKeyword(...HandlerReadTarget::getKeywords());
        }
        if ($what !== null) {
            $what = HandlerReadTarget::get($what);
        }
        if ($what === null) {
            $what = $tokenList->expectAnyOperator(...HandlerReadTarget::getOperators());
            $what = HandlerReadTarget::get($what);

            $values = [];
            $tokenList->expectSymbol('(');
            do {
                $values[] = $this->expressionParser->parseLiteral($tokenList);
            } while ($tokenList->hasSymbol(','));
            $tokenList->expectSymbol(')');
        }

        $where = $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            [$limit, $offset] = $this->parseLimitAndOffset($tokenList);
        }

        return new HandlerReadCommand($table, $what, $index, $values, $where, $limit, $offset);
    }

    /**
     * limit:
     *     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *
     * @return int[]|null[]|array{int, int|null} ($limit, $offset)
     */
    private function parseLimitAndOffset(TokenList $tokenList): array
    {
        $limit = (int) $tokenList->expectUnsignedInt();
        $offset = null;
        if ($tokenList->hasKeyword(Keyword::OFFSET)) {
            $offset = (int) $tokenList->expectUnsignedInt();
        } elseif ($tokenList->hasSymbol(',')) {
            $offset = $limit;
            $limit = (int) $tokenList->expectUnsignedInt();
        }

        return [$limit, $offset];
    }

    /**
     * HANDLER tbl_name CLOSE
     */
    public function parseHandlerClose(TokenList $tokenList): HandlerCloseCommand
    {
        $tokenList->expectKeyword(Keyword::HANDLER);
        $table = $tokenList->expectQualifiedName();
        $tokenList->expectKeyword(Keyword::CLOSE);

        return new HandlerCloseCommand($table);
    }

}
