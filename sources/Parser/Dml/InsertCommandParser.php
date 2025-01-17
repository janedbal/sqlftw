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
use SqlFtw\Sql\Dml\Assignment;
use SqlFtw\Sql\Dml\Insert\InsertCommand;
use SqlFtw\Sql\Dml\Insert\InsertPriority;
use SqlFtw\Sql\Dml\Insert\InsertSelectCommand;
use SqlFtw\Sql\Dml\Insert\InsertSetCommand;
use SqlFtw\Sql\Dml\Insert\InsertValuesCommand;
use SqlFtw\Sql\Dml\Insert\OnDuplicateKeyActions;
use SqlFtw\Sql\Dml\Insert\ReplaceCommand;
use SqlFtw\Sql\Dml\Insert\ReplaceSelectCommand;
use SqlFtw\Sql\Dml\Insert\ReplaceSetCommand;
use SqlFtw\Sql\Dml\Insert\ReplaceValuesCommand;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Statement;

class InsertCommandParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var QueryParser */
    private $queryParser;

    public function __construct(ExpressionParser $expressionParser, QueryParser $queryParser)
    {
        $this->expressionParser = $expressionParser;
        $this->queryParser = $queryParser;
    }

    /**
     * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     { {VALUES | VALUE} (value_list) [, (value_list)] ... }
     *     [AS row_alias[(col_alias [, col_alias] ...)]]
     *     [ON DUPLICATE KEY UPDATE assignment_list]
     *
     * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     SET assignment_list
     *     [AS row_alias[(col_alias [, col_alias] ...)]]
     *     [ON DUPLICATE KEY UPDATE assignment_list]
     *
     * INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     { SELECT ...
     *       | TABLE table_name
     *       | VALUES row_constructor_list
     *     }
     *     [ON DUPLICATE KEY UPDATE assignment_list]
     *
     * value_list:
     *     value [, value] ...
     *
     * value:
     *     {expr | DEFAULT}
     *
     * row_constructor_list:
     *     ROW(value_list)[, ROW(value_list)][, ...]
     *
     * assignment_list:
     *     assignment [, assignment] ...
     *
     * assignment:
     *     col_name =
     *         value
     *       | [row_alias.]col_name
     *       | [tbl_name.]col_name
     *       | [row_alias.]col_alias
     *
     * @return InsertCommand&Statement
     */
    public function parseInsert(TokenList $tokenList): InsertCommand
    {
        $tokenList->expectKeyword(Keyword::INSERT);

        $priority = $tokenList->getKeywordEnum(InsertPriority::class);
        $ignore = $tokenList->hasKeyword(Keyword::IGNORE);
        $tokenList->passKeyword(Keyword::INTO);
        $table = $tokenList->expectQualifiedName();

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        $position = $tokenList->getPosition();
        if ($tokenList->hasKeyword(Keyword::VALUE)
            || ($tokenList->hasKeyword(Keyword::VALUES) && !$tokenList->hasKeyword(Keyword::ROW))
        ) {
            $rows = $this->parseRows($tokenList);

            $alias = $columnAliases = null;
            if ($tokenList->hasKeyword(Keyword::AS)) {
                $alias = $tokenList->expectName();
                if ($tokenList->hasSymbol('(')) {
                    do {
                        $columnAliases[] = $tokenList->expectName();
                    } while ($tokenList->hasSymbol(','));
                    $tokenList->expectSymbol(')');
                }
            }

            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertValuesCommand($table, $rows, $columns, $alias, $columnAliases, $partitions, $priority, $ignore, $update);
        }
        $tokenList->rewind($position);

        if ($tokenList->hasKeyword(Keyword::SET)) {
            $assignments = $this->parseAssignments($tokenList);

            $alias = null;
            if ($tokenList->hasKeyword(Keyword::AS)) {
                $alias = $tokenList->expectName();
            }

            $update = $this->parseOnDuplicateKeyUpdate($tokenList);

            return new InsertSetCommand($table, $assignments, $columns, $alias, $partitions, $priority, $ignore, $update);
        }

        $query = $this->queryParser->parseQuery($tokenList);
        $update = $this->parseOnDuplicateKeyUpdate($tokenList);

        return new InsertSelectCommand($table, $query, $columns, $partitions, $priority, $ignore, $update);
    }

    /**
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     {VALUES | VALUE} ({expr | DEFAULT}, ...), (...), ...
     *
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     SET col_name={expr | DEFAULT}, ...
     *
     * REPLACE [LOW_PRIORITY | DELAYED]
     *     [INTO] tbl_name
     *     [PARTITION (partition_name, ...)]
     *     [(col_name, ...)]
     *     SELECT ...
     *
     * @return ReplaceCommand&Statement
     */
    public function parseReplace(TokenList $tokenList): ReplaceCommand
    {
        $tokenList->expectKeyword(Keyword::REPLACE);

        $priority = $tokenList->getKeywordEnum(InsertPriority::class);
        $ignore = $tokenList->hasKeyword(Keyword::IGNORE);
        $tokenList->passKeyword(Keyword::INTO);
        $table = $tokenList->expectQualifiedName();

        $partitions = $this->parsePartitionsList($tokenList);
        $columns = $this->parseColumnList($tokenList);

        if ($tokenList->hasSymbol('(')) {
            $tokenList->expectAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE, Keyword::VALUES);
            $query = $this->queryParser->parseQuery($tokenList->rewind(-1));
            $tokenList->expectSymbol(')');

            return new ReplaceSelectCommand($table, $query, $columns, $partitions, $priority, $ignore);
        } elseif ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::WITH, Keyword::TABLE)) { // no Keyword::VALUES!
            $query = $this->queryParser->parseQuery($tokenList->rewind(-1));

            return new ReplaceSelectCommand($table, $query, $columns, $partitions, $priority, $ignore);
        } elseif ($tokenList->hasKeyword(Keyword::SET)) {
            $assignments = $this->parseAssignments($tokenList);

            return new ReplaceSetCommand($table, $assignments, $columns, $partitions, $priority, $ignore);
        } else {
            $tokenList->expectAnyKeyword(Keyword::VALUE, Keyword::VALUES);
            $rows = $this->parseRows($tokenList);

            return new ReplaceValuesCommand($table, $rows, $columns, $partitions, $priority, $ignore);
        }
    }

    /**
     * @return non-empty-array<string>|null
     */
    private function parsePartitionsList(TokenList $tokenList): ?array
    {
        $partitions = null;
        if ($tokenList->hasKeyword(Keyword::PARTITION)) {
            $tokenList->expectSymbol('(');
            $partitions = [];
            do {
                $partitions[] = $tokenList->expectName();
            } while ($tokenList->hasSymbol(','));
            $tokenList->expectSymbol(')');
        }

        return $partitions;
    }

    /**
     * @return ColumnIdentifier[]|null
     */
    private function parseColumnList(TokenList $tokenList): ?array
    {
        $position = $tokenList->getPosition();
        $columns = null;
        if ($tokenList->hasSymbol('(')) {
            if ($tokenList->hasSymbol(')')) {
                return [];
            }
            if ($tokenList->hasAnyKeyword(Keyword::SELECT, Keyword::TABLE, Keyword::VALUES, Keyword::WITH)) {
                // this is not a column list
                $tokenList->rewind($position);
                return null;
            }
            $columns = [];
            do {
                $columns[] = $this->expressionParser->parseColumnIdentifier($tokenList);
            } while ($tokenList->hasSymbol(','));
            $tokenList->expectSymbol(')');
        }

        return $columns;
    }

    private function parseOnDuplicateKeyUpdate(TokenList $tokenList): ?OnDuplicateKeyActions
    {
        if (!$tokenList->hasKeywords(Keyword::ON, Keyword::DUPLICATE, Keyword::KEY, Keyword::UPDATE)) {
            return null;
        }

        $assignments = $this->parseAssignments($tokenList);

        return new OnDuplicateKeyActions($assignments);
    }

    /**
     * @return non-empty-array<Assignment>
     */
    private function parseAssignments(TokenList $tokenList): array
    {
        $assignments = [];
        do {
            $column = $this->expressionParser->parseColumnIdentifier($tokenList);
            $tokenList->expectOperator(Operator::EQUAL);
            $assignments[] = new Assignment($column, $this->expressionParser->parseExpression($tokenList));
        } while ($tokenList->hasSymbol(','));

        return $assignments;
    }

    /**
     * @return non-empty-array<array<ExpressionNode>>
     */
    private function parseRows(TokenList $tokenList): array
    {
        $rows = [];
        do {
            $tokenList->expectSymbol('(');

            $values = [];
            if (!$tokenList->hasSymbol(')')) {
                do {
                    $values[] = $this->expressionParser->parseAssignExpression($tokenList);
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }

            $rows[] = $values;
        } while ($tokenList->hasSymbol(','));

        return $rows;
    }

}
