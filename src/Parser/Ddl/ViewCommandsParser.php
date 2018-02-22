<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use SqlFtw\Parser\Dml\SelectCommandParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewOption;
use SqlFtw\Sql\Ddl\View\ViewAlgorithm;
use SqlFtw\Sql\Ddl\View\ViewCheckOption;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\UserName;

class ViewCommandsParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\Dml\SelectCommandParser */
    private $selectCommandParser;

    public function __construct(SelectCommandParser $selectCommandParser)
    {
        $this->selectCommandParser = $selectCommandParser;
    }

    /**
     * ALTER
     *     [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}]
     *     [DEFINER = { user | CURRENT_USER }]
     *     [SQL SECURITY { DEFINER | INVOKER }]
     *     VIEW view_name [(column_list)]
     *     AS select_statement
     *     [WITH [CASCADED | LOCAL] CHECK OPTION]
     */
    public function parseAlterView(TokenList $tokenList): AlterViewCommand
    {
        $tokenList->consumeKeyword(Keyword::ALTER);

        return new AlterViewCommand(...$this->parseViewDefinition($tokenList));
    }

    /**
     * CREATE
     *     [OR REPLACE]
     *     [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}]
     *     [DEFINER = { user | CURRENT_USER }]
     *     [SQL SECURITY { DEFINER | INVOKER }]
     *     VIEW view_name [(column_list)]
     *     AS select_statement
     *     [WITH [CASCADED | LOCAL] CHECK OPTION]
     */
    public function parseCreateView(TokenList $tokenList): CreateViewCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $orReplace = (bool) $tokenList->mayConsumeKeywords(Keyword::OR, Keyword::REPLACE);

        $params = $this->parseViewDefinition($tokenList) + [$orReplace];

        return new CreateViewCommand(...$params);
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return mixed[]
     */
    private function parseViewDefinition(TokenList $tokenList): array
    {
        $algorithm = $definer = $sqlSecurity = $checkOption = null;
        if ($tokenList->mayConsumeKeyword(Keyword::ALGORITHM)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            /** @var \SqlFtw\Sql\Ddl\View\ViewAlgorithm $algorithm */
            $algorithm = $tokenList->consumeKeywordEnum(ViewAlgorithm::class);
        }
        if ($tokenList->mayConsumeKeyword(Keyword::DEFINER)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $definer = new UserName(...$tokenList->consumeUserName());
        }
        if ($tokenList->mayConsumeKeyword(Keyword::SQL)) {
            $tokenList->consumeKeyword(Keyword::SECURITY);
            /** @var \SqlFtw\Sql\Ddl\SqlSecurity $sqlSecurity */
            $sqlSecurity = $tokenList->consumeKeywordEnum(SqlSecurity::class);
        }

        $tokenList->consumeKeyword(Keyword::VIEW);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        $columns = null;
        if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
            $columns = [];
            do {
                $columns[] = $tokenList->consumeName();
            } while ($tokenList->mayConsumeComma());

            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }

        $tokenList->consumeKeyword(Keyword::AS);
        $body = $this->selectCommandParser->parseSelect($tokenList);

        if ($tokenList->mayConsumeKeyword(Keyword::WITH)) {
            $option = $tokenList->mayConsumeAnyKeyword(Keyword::CASCADED, Keyword::LOCAL);
            if ($option !== null) {
                $checkOption = ViewCheckOption::get($option . ' CHECK OPTION');
            } else {
                $checkOption = ViewCheckOption::get(ViewCheckOption::CHECK_OPTION);
            }
            $tokenList->consumeKeywords(Keyword::CHECK, Keyword::OPTION);
        }

        return [$name, $body, $columns, $definer, $sqlSecurity, $algorithm, $checkOption];
    }

    /**
     * DROP VIEW [IF EXISTS]
     *     view_name [, view_name] ...
     *     [RESTRICT | CASCADE]
     */
    public function parseDropView(TokenList $tokenList): DropViewCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::VIEW);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);

        $names = [];
        do {
            $names[] = new QualifiedName(...$tokenList->consumeQualifiedName());
        } while ($tokenList->mayConsumeComma());

        $option = $tokenList->mayConsumeAnyKeyword(Keyword::RESTRICT, Keyword::CASCADE);
        if ($option !== null) {
            $option = DropViewOption::get($option);
        }

        return new DropViewCommand($names, $ifExists, $option);
    }

}
