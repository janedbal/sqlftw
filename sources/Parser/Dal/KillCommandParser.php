<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Kill\KillCommand;
use SqlFtw\Sql\Keyword;

class KillCommandParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * KILL [CONNECTION | QUERY] processlist_id
     */
    public function parseKill(TokenList $tokenList): KillCommand
    {
        $tokenList->expectKeyword(Keyword::KILL);
        $tokenList->getAnyKeyword(Keyword::CONNECTION, Keyword::QUERY);
        $id = $this->expressionParser->parseExpression($tokenList);

        return new KillCommand($id);
    }

}
