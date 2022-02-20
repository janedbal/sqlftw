<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Dml\TableReference\EscapedTableReference;
use SqlFtw\Sql\Dml\TableReference\IndexHint;
use SqlFtw\Sql\Dml\TableReference\IndexHintAction;
use SqlFtw\Sql\Dml\TableReference\IndexHintTarget;
use SqlFtw\Sql\Dml\TableReference\InnerJoin;
use SqlFtw\Sql\Dml\TableReference\JoinSide;
use SqlFtw\Sql\Dml\TableReference\NaturalJoin;
use SqlFtw\Sql\Dml\TableReference\OuterJoin;
use SqlFtw\Sql\Dml\TableReference\StraightJoin;
use SqlFtw\Sql\Dml\TableReference\TableReferenceList;
use SqlFtw\Sql\Dml\TableReference\TableReferenceNode;
use SqlFtw\Sql\Dml\TableReference\TableReferenceParentheses;
use SqlFtw\Sql\Dml\TableReference\TableReferenceSubquery;
use SqlFtw\Sql\Dml\TableReference\TableReferenceTable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use function count;

class JoinParser
{
    use StrictBehaviorMixin;

    /** @var ParserFactory */
    private $parserFactory;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(
        ParserFactory $parserFactory,
        ExpressionParser $expressionParser
    )
    {
        $this->parserFactory = $parserFactory;
        $this->expressionParser = $expressionParser;
    }

    /**
     * table_references:
     *     escaped_table_reference [, escaped_table_reference] ...
     */
    public function parseTableReferences(TokenList $tokenList): TableReferenceNode
    {
        $references = [];
        do {
            $references[] = $this->parseTableReference($tokenList);
        } while ($tokenList->hasComma());

        if (count($references) === 1) {
            return $references[0];
        } else {
            return new TableReferenceList($references);
        }
    }

    /**
     * escaped_table_reference:
     *     table_reference
     *   | { OJ table_reference }
     */
    public function parseTableReference(TokenList $tokenList): TableReferenceNode
    {
        if ($tokenList->has(TokenType::LEFT_CURLY_BRACKET)) {
            $token = $tokenList->expectName();
            if ($token !== 'OJ') {
                $tokenList->expected('Expected ODBC escaped table reference introducer "OJ".');
            } else {
                $reference = $this->parseTableReference($tokenList);
                $tokenList->expect(TokenType::RIGHT_CURLY_BRACKET);

                return new EscapedTableReference($reference);
            }
        } else {
            return $this->parseTableReferenceInternal($tokenList);
        }
    }

    /**
     * table_reference:
     *     table_factor
     *   | join_table
     *
     * join_table:
     *     table_reference [INNER | CROSS] JOIN table_factor [join_condition]
     *   | table_reference STRAIGHT_JOIN table_factor
     *   | table_reference STRAIGHT_JOIN table_factor ON conditional_expr
     *   | table_reference {LEFT|RIGHT} [OUTER] JOIN table_reference join_condition
     *   | table_reference NATURAL [INNER | {LEFT|RIGHT} [OUTER]] JOIN table_factor
     *
     * join_condition:
     *     ON conditional_expr
     *   | USING (column_list)
     */
    private function parseTableReferenceInternal(TokenList $tokenList): TableReferenceNode
    {
        $left = $this->parseTableFactor($tokenList);

        do {
            if ($tokenList->hasKeyword(Keyword::STRAIGHT_JOIN)) {
                // STRAIGHT_JOIN
                $right = $this->parseTableFactor($tokenList);
                $condition = null;
                if ($tokenList->hasKeyword(Keyword::ON)) {
                    $condition = $this->expressionParser->parseExpression($tokenList);
                }

                $left = new StraightJoin($left, $right, $condition);
                continue;
            }
            if ($tokenList->hasKeyword(Keyword::NATURAL)) {
                // NATURAL JOIN
                $side = null;
                if ($tokenList->hasKeyword(Keyword::INNER) === null) {
                    /** @var JoinSide|null $side */
                    $side = $tokenList->getKeywordEnum(JoinSide::class);
                    if ($side !== null) {
                        $tokenList->hasKeyword(Keyword::OUTER);
                    }
                }
                $tokenList->expectKeyword(Keyword::JOIN);
                $right = $this->parseTableFactor($tokenList);

                $left = new NaturalJoin($left, $right, $side);
                continue;
            }
            /** @var JoinSide|null $side */
            $side = $tokenList->getKeywordEnum(JoinSide::class);
            if ($side !== null) {
                // OUTER JOIN
                $tokenList->hasKeyword(Keyword::OUTER);
                $right = $this->parseTableReferenceInternal($tokenList);
                [$on, $using] = $this->parseJoinCondition($tokenList);

                $left = new OuterJoin($left, $right, $side, $on, $using);
                continue;
            }
            $keyword = $tokenList->getAnyKeyword(Keyword::INNER, Keyword::CROSS, Keyword::JOIN);
            if ($keyword !== null) {
                // INNER JOIN
                $cross = false;
                if ($keyword === Keyword::INNER) {
                    $tokenList->expectKeyword(Keyword::JOIN);
                } elseif ($keyword === Keyword::CROSS) {
                    $tokenList->expectKeyword(Keyword::JOIN);
                    $cross = true;
                }
                $right = $this->parseTableFactor($tokenList);
                [$on, $using] = $this->parseJoinCondition($tokenList);

                $left = new InnerJoin($left, $right, $cross, $on, $using);
                continue;
            }

            return $left;
        } while (true);
    }

    /**
     * @return mixed[]|array{0: ExpressionNode, 1: string[]}
     */
    private function parseJoinCondition(TokenList $tokenList): array
    {
        $on = $using = null;
        if ($tokenList->hasKeyword(Keyword::ON)) {
            $on = $this->expressionParser->parseExpression($tokenList);
        } elseif ($tokenList->hasKeyword(Keyword::USING)) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $using = [];
            do {
                $using[] = $tokenList->expectName();
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
        }

        return [$on, $using];
    }

    /**
     * table_factor:
     *     tbl_name [PARTITION (partition_names)] [[AS] alias] [index_hint_list]
     *   | [LATERAL] [(] table_subquery [)] [AS] alias [(col_list)]
     *   | ( table_references )
     */
    private function parseTableFactor(TokenList $tokenList): TableReferenceNode
    {
        $selectInParentheses = false;
        if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
            $selectInParentheses = $tokenList->hasKeyword(Keyword::SELECT);
            if (!$selectInParentheses) {
                $references = $this->parseTableReferences($tokenList);
                $tokenList->get(TokenType::RIGHT_PARENTHESIS);

                return new TableReferenceParentheses($references);
            }
        }

        $keyword = $tokenList->getAnyKeyword(Keyword::SELECT, Keyword::LATERAL);
        if ($selectInParentheses || $keyword !== null) {
            if ($keyword === Keyword::LATERAL) {
                if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                    $selectInParentheses = true;
                }
                $tokenList->expectKeyword(Keyword::SELECT);
            }

            $query = $this->parserFactory->getSelectCommandParser()->parseSelect($tokenList->resetPosition(-1));

            if ($selectInParentheses) {
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }

            $tokenList->hasKeyword(Keyword::AS);
            $alias = $tokenList->expectName();
            $columns = null;
            if ($tokenList->has(TokenType::LEFT_PARENTHESIS)) {
                $columns = [];
                do {
                    $columns[] = $tokenList->expectName();
                } while ($tokenList->hasComma());
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }

            return new TableReferenceSubquery($query, $alias, $columns, $selectInParentheses, $keyword === Keyword::LATERAL);
        } else {
            $table = new QualifiedName(...$tokenList->expectQualifiedName());
            $partitions = null;
            if ($tokenList->hasKeyword(Keyword::PARTITION)) {
                $tokenList->expect(TokenType::LEFT_PARENTHESIS);
                $partitions = [];
                do {
                    $partitions[] = $tokenList->expectName();
                } while ($tokenList->hasComma());
                $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            }
            if ($tokenList->hasKeyword(Keyword::AS)) {
                $alias = $tokenList->expectName();
            } else {
                $alias = $tokenList->getName();
            }
            $indexHints = null;
            if ($tokenList->hasAnyKeyword(Keyword::USE, Keyword::IGNORE, Keyword::FORCE)) {
                $indexHints = $this->parseIndexHints($tokenList->resetPosition(-1));
            }

            return new TableReferenceTable($table, $alias, $partitions, $indexHints);
        }
    }

    /**
     * index_hint_list:
     *     index_hint [, index_hint] ...
     *
     * index_hint:
     *     USE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] ([index_list])
     *   | IGNORE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] (index_list)
     *   | FORCE {INDEX|KEY} [FOR {JOIN|ORDER BY|GROUP BY}] (index_list)
     *
     * index_list:
     *     index_name [, index_name] ...
     *
     * @return IndexHint[]
     */
    private function parseIndexHints(TokenList $tokenList): array
    {
        $hints = [];
        do {
            /** @var IndexHintAction $action */
            $action = $tokenList->getKeywordEnum(IndexHintAction::class);
            $tokenList->getAnyKeyword(Keyword::INDEX, Keyword::KEY);
            $target = null;
            if ($tokenList->hasKeyword(Keyword::FOR)) {
                $keyword = $tokenList->expectAnyKeyword(Keyword::JOIN, Keyword::ORDER, Keyword::GROUP);
                if ($keyword === Keyword::JOIN) {
                    $target = IndexHintTarget::get(IndexHintTarget::JOIN);
                } else {
                    $tokenList->expectKeyword(Keyword::BY);
                    $target = IndexHintTarget::get($keyword . ' ' . Keyword::BY);
                }
            }

            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $indexes = [];
            do {
                $indexes[] = $tokenList->expectName();
            } while ($tokenList->hasComma());
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);

            $hints[] = new IndexHint($action, $target, $indexes);
        } while ($tokenList->hasComma());

        return $hints;
    }

}
