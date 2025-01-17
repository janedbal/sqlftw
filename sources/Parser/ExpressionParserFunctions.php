<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition

namespace SqlFtw\Parser;

use Dogma\ShouldNotHappenException;
use SqlFtw\Parser\Dml\QueryParser;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\Query\WindowSpecification;
use SqlFtw\Sql\Expression\AliasExpression;
use SqlFtw\Sql\Expression\Asterisk;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\CastType;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\JsonErrorCondition;
use SqlFtw\Sql\Expression\JsonTableExistsPathColumn;
use SqlFtw\Sql\Expression\JsonTableNestedColumns;
use SqlFtw\Sql\Expression\JsonTableOrdinalityColumn;
use SqlFtw\Sql\Expression\JsonTablePathColumn;
use SqlFtw\Sql\Expression\ListExpression;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\OrderByExpression;
use SqlFtw\Sql\Expression\Parentheses;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Expression\TimeTypeLiteral;
use SqlFtw\Sql\Expression\TimeZone;
use SqlFtw\Sql\Keyword;
use function explode;
use function in_array;
use function strtoupper;

trait ExpressionParserFunctions
{

    public function parseFunctionCall(TokenList $tokenList, string $name1, ?string $name2 = null): FunctionCall
    {
        $function = $name2 === null && BuiltInFunction::validateValue($name1)
            ? BuiltInFunction::get($name1)
            : new QualifiedName($name2 ?? $name1, $name2 !== null ? $name1 : null);

        $arguments = [];
        if ($function instanceof BuiltInFunction) {
            $name = $function->getValue();
            if ($name === BuiltInFunction::CONVERT) {
                return $this->parseConvert($tokenList, $function);
            } elseif ($name === BuiltInFunction::COUNT) {
                if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                    $arguments[] = new Asterisk();
                }
            } elseif ($name === BuiltInFunction::TRIM) {
                return $this->parseTrim($tokenList, $function);
            } elseif ($name === BuiltInFunction::JSON_VALUE) {
                return $this->parseJsonValue($tokenList, $function);
            } elseif ($name === BuiltInFunction::JSON_TABLE) {
                return $this->parseJsonTable($tokenList, false);
            }
            $namedParams = $function->getNamedParams();
        } else {
            $namedParams = [];
        }

        do {
            if ($tokenList->hasSymbol(')')) {
                break;
            }
            foreach ($namedParams as $keyword => $type) {
                if (!$tokenList->hasKeywords(...explode(' ', $keyword))) {
                    continue;
                }
                switch ($type) {
                    case RootNode::class:
                        $arguments[$keyword] = $this->parseExpression($tokenList);
                        continue 3;
                    case Charset::class:
                        $arguments[$keyword] = $tokenList->expectCharsetName();
                        continue 3;
                    case CastType::class:
                        $arguments[$keyword] = $this->parseCastType($tokenList);
                        continue 3;
                    case OrderByExpression::class:
                        $arguments[$keyword] = new ListExpression($this->parseOrderBy($tokenList));
                        continue 3;
                    case Literal::class:
                        $arguments[$keyword] = $this->parseLiteral($tokenList);
                        continue 3;
                    case TimeZone::class:
                        $interval = $tokenList->hasKeyword(Keyword::INTERVAL);
                        $zone = strtoupper($tokenList->expectString());
                        if (($interval === false && $zone === 'UTC') || $zone === '+00:00') {
                            $arguments[$keyword] = TimeZone::get(TimeZone::UTC);
                        } else {
                            throw new ParserException("Invalid time zone specification. Only 'UTC' or [INTERVAL] '+00:00' is supported.", $tokenList);
                        }
                        continue 3;
                    case false:
                        // skip parsing other arguments
                        while (!$tokenList->isFinished()) {
                            $token = $tokenList->get();
                            if ($token !== null && $token->value === ')' && ($token->type & TokenType::SYMBOL) !== 0) {
                                break;
                            }
                        }
                        $tokenList->rewind(-1);
                        continue 3;
                    case null:
                        if (in_array($keyword, [Keyword::DATE, Keyword::TIME, Keyword::DATETIME], true)) {
                            $arguments[] = new TimeTypeLiteral($keyword);
                            continue 3;
                        }
                    default:
                        throw new ShouldNotHappenException('Unsupported named parameter type.');
                }
            }

            if ($arguments !== []) {
                $tokenList->expectSymbol(',');
            }

            $expression = $this->parseExpression($tokenList);
            // todo: not sure where alias is allowed. can built-in functions have aliased params or UDF only?
            if (!isset($namedParams[Keyword::AS]) && $tokenList->hasKeyword(Keyword::AS)) {
                $alias = $tokenList->expectName();
                $expression = new AliasExpression($expression, $alias);
            } elseif (($alias = $tokenList->getNonKeywordName()) !== null) {
                // non-reserved is not enough here
                $expression = new AliasExpression($expression, $alias);
            }
            $arguments[] = $expression;
        } while (true);

        // AGG_FUNC(...) [from_first_last] [null_treatment] [over_clause]
        $fromFirst = null;
        if ($function instanceof BuiltInFunction && $function->hasFromFirstLast()) {
            if ($tokenList->hasKeywords(Keyword::FROM, Keyword::FIRST)) {
                $fromFirst = true;
            } elseif ($tokenList->hasKeywords(Keyword::FROM, Keyword::LAST)) {
                throw new ParserException('FROM LAST is not yet supported by MySQL.', $tokenList);
            }
        }

        $respectNulls = null;
        if ($function instanceof BuiltInFunction && $function->hasNullTreatment()) {
            if ($tokenList->hasKeywords(Keyword::RESPECT, Keyword::NULLS)) {
                $respectNulls = true;
            } elseif ($tokenList->hasKeywords(Keyword::IGNORE, Keyword::NULLS)) {
                throw new ParserException('IGNORE NULLS is not yet supported by MySQL.', $tokenList);
            }
        }

        $over = null;
        if ($function instanceof BuiltInFunction && $function->isWindow() && $tokenList->getKeyword(Keyword::OVER) !== null) {
            $over = $this->parseOver($tokenList);
        }

        return new FunctionCall($function, $arguments, $over, $respectNulls, $fromFirst);
    }

    /**
     * CONVERT(string, type), CONVERT(expr USING charset_name)
     *
     * type:
     *   BINARY[(N)]
     *   CHAR[(N)] [charset_info]
     *   DATE
     *   DATETIME
     *   DECIMAL[(M[,D])]
     *   JSON
     *   NCHAR[(N)]
     *   SIGNED [INTEGER]
     *   TIME
     *   UNSIGNED [INTEGER]
     *
     * charset_info:
     *   CHARACTER SET charset_name
     *   ASCII
     *   UNICODE
     */
    private function parseConvert(TokenList $tokenList, BuiltInFunction $function): FunctionCall
    {
        $arguments = [$this->parseExpression($tokenList)];
        if ($tokenList->hasSymbol(',')) {
            $type = $this->parseCastType($tokenList);
            // todo: charset ???
            $arguments[] = $type;
        } else {
            $tokenList->expectKeyword(Keyword::USING);
            $arguments[] = $tokenList->expectCharsetName();
        }

        $tokenList->expectSymbol(')');

        return new FunctionCall($function, $arguments);
    }

    /**
     * TRIM([{BOTH | LEADING | TRAILING} [remstr] FROM] str), TRIM([remstr FROM] str)
     */
    private function parseTrim(TokenList $tokenList, BuiltInFunction $function): FunctionCall
    {
        $arguments = [];
        $keyword = $tokenList->getAnyKeyword(Keyword::LEADING, Keyword::TRAILING, Keyword::BOTH);
        if ($keyword !== null) {
            if ($tokenList->hasKeyword(Keyword::FROM)) {
                // TRIM(FOO FROM str)
                $second = $this->parseExpression($tokenList);
                $arguments[$keyword] = $second;
            } else {
                // TRIM(FOO remstr FROM str)
                $arguments[$keyword] = $this->parseExpression($tokenList);
                $tokenList->expectKeyword(Keyword::FROM);
                $arguments[] = $this->parseExpression($tokenList);
            }
        } else {
            $first = $this->parseExpression($tokenList);
            if ($tokenList->hasKeyword(Keyword::FROM)) {
                // TRIM(remstr FROM str)
                $arguments[Keyword::FROM] = $first;
                $arguments[] = $this->parseExpression($tokenList);
            } else {
                // TRIM(str)
                $arguments[] = $first;
            }
        }

        $tokenList->expectSymbol(')');

        return new FunctionCall($function, $arguments);
    }

    /**
     * JSON_VALUE(json_doc, path [RETURNING type] [on_empty] [on_error])
     *
     * on_empty:
     *     {NULL | ERROR | DEFAULT value} ON EMPTY
     *
     * on_error:
     *     {NULL | ERROR | DEFAULT value} ON ERROR
     */
    private function parseJsonValue(TokenList $tokenList, BuiltInFunction $function): FunctionCall
    {
        $params = [$this->parseExpression($tokenList)];
        $tokenList->expectSymbol(',');
        $params[] = $this->parseExpression($tokenList);

        if ($tokenList->hasKeyword(Keyword::RETURNING)) {
            $params[Keyword::RETURNING] = $this->parseCastType($tokenList);
        }

        [$onEmpty, $onError] = $this->parseOnEmptyOnError($tokenList);
        if ($onEmpty !== null) {
            $params[Keyword::ON . ' ' . Keyword::EMPTY] = $onEmpty;
        }
        if ($onError !== null) {
            $params[Keyword::ON . ' ' . Keyword::ERROR] = $onError;
        }

        $tokenList->expectSymbol(')');

        return new FunctionCall($function, $params);
    }

    /**
     * JSON_TABLE(expr, path COLUMNS (column_list)) [AS] alias
     *
     * column_list:
     *   column[, column][, ...]
     *
     * column:
     *   name FOR ORDINALITY
     *   |  name type PATH string path [on_empty] [on_error]
     *   |  name type EXISTS PATH string path
     *   |  NESTED [PATH] path COLUMNS (column_list)
     *
     * on_empty:
     *   {NULL | DEFAULT json_string | ERROR} ON EMPTY
     *
     * on_error:
     *   {NULL | DEFAULT json_string | ERROR} ON ERROR
     */
    public function parseJsonTable(TokenList $tokenList, bool $parseIntro = true): FunctionCall
    {
        if ($parseIntro) {
            $tokenList->expectKeyword(Keyword::JSON_TABLE);
            $tokenList->expectSymbol('(');
        }

        $expression = $this->parseExpression($tokenList);
        $tokenList->expectSymbol(',');
        $path = $tokenList->expectStringValue();

        $tokenList->expectKeyword(Keyword::COLUMNS);
        $columns = $this->parseJsonTableColumns($tokenList);

        $tokenList->expectSymbol(')');

        return new FunctionCall(BuiltInFunction::get(BuiltInFunction::JSON_TABLE), [$expression, $path, Keyword::COLUMNS => $columns]);
    }

    private function parseJsonTableColumns(TokenList $tokenList): Parentheses
    {
        $tokenList->expectSymbol('(');
        $columns = [];
        do {
            if ($tokenList->hasKeyword(Keyword::NESTED)) {
                $tokenList->passKeyword(Keyword::PATH);
                $path = $tokenList->expectStringValue();
                $tokenList->expectKeyword(Keyword::COLUMNS);
                $columns[] = new JsonTableNestedColumns($path, $this->parseJsonTableColumns($tokenList));
                continue;
            }

            $name = $tokenList->expectName();

            if ($tokenList->hasKeywords(Keyword::FOR, Keyword::ORDINALITY)) {
                $columns[] = new JsonTableOrdinalityColumn($name);
                continue;
            }

            $type = $this->parseColumnType($tokenList);
            $keyword = $tokenList->expectAnyKeyword(Keyword::PATH, Keyword::EXISTS);
            if ($keyword === Keyword::PATH) {
                $path = $tokenList->expectStringValue();
                [$onEmpty, $onError] = $this->parseOnEmptyOnError($tokenList);

                $columns[] = new JsonTablePathColumn($name, $type, $path, $onEmpty, $onError);
            } else {
                $tokenList->expectKeyword(Keyword::PATH);
                $path = $tokenList->expectStringValue();

                $columns[] = new JsonTableExistsPathColumn($name, $type, $path);
            }
        } while ($tokenList->hasSymbol(','));

        $tokenList->expectSymbol(')');

        return new Parentheses(new ListExpression($columns));
    }

    /**
     * @return array{JsonErrorCondition|null, JsonErrorCondition|null}
     */
    private function parseOnEmptyOnError(TokenList $tokenList): array
    {
        $onEmpty = $onError = null;
        while (($keyword = $tokenList->getAnyKeyword(Keyword::NULL, Keyword::ERROR, Keyword::DEFAULT)) !== null) {
            if ($keyword === Keyword::NULL) {
                $default = true;
            } elseif ($keyword === Keyword::ERROR) {
                $default = false;
            } else {
                $default = $this->parseLiteral($tokenList);
            }
            $tokenList->expectKeyword(Keyword::ON);
            $event = $tokenList->expectAnyKeyword(Keyword::EMPTY, Keyword::ERROR);
            if ($event === Keyword::EMPTY) {
                if (isset($onEmpty)) {
                    throw new ParserException('ON EMPTY defined twice in JSON_TABLE', $tokenList);
                }
                $onEmpty = new JsonErrorCondition($default);
            } else {
                if (isset($onError)) {
                    throw new ParserException('ON ERROR defined twice in JSON_TABLE', $tokenList);
                }
                $onError = new JsonErrorCondition($default);
            }
        }

        return [$onEmpty, $onError];
    }

    /**
     * over_clause:
     *   {OVER (window_spec) | OVER window_name}
     *
     * window_spec:
     *   [window_name] [partition_clause] [order_clause] [frame_clause]
     *
     * partition_clause:
     *   PARTITION BY expr [, expr] ...
     *
     * order_clause:
     *   ORDER BY expr [ASC|DESC] [, expr [ASC|DESC]] ...
     *
     * frame_clause:
     *   frame_units frame_extent
     *
     * frame_units:
     *   {ROWS | RANGE}
     *
     * frame_extent:
     *   {frame_start | frame_between}
     *
     * frame_between:
     *   BETWEEN frame_start AND frame_end
     *
     * frame_start, frame_end: {
     *     CURRENT ROW
     *   | UNBOUNDED PRECEDING
     *   | UNBOUNDED FOLLOWING
     *   | expr PRECEDING
     *   | expr FOLLOWING
     * }
     *
     * @return WindowSpecification|string
     */
    private function parseOver(TokenList $tokenList)
    {
        if ($tokenList->hasSymbol('(')) {
            /** @var QueryParser $queryParser */
            $queryParser = ($this->queryParserProxy)();
            $window = $queryParser->parseWindow($tokenList);
            $tokenList->expectSymbol(')');

            return $window;
        } else {
            return $tokenList->expectNonReservedNameOrString();
        }
    }

}
