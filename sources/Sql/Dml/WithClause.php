<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class WithClause implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<WithExpression> */
    private $expressions;

    /** @var bool */
    private $recursive;

    /**
     * @param non-empty-array<WithExpression> $expressions
     */
    public function __construct(array $expressions, bool $recursive = false)
    {
        $this->expressions = $expressions;
        $this->recursive = $recursive;
    }

    /**
     * @return non-empty-array<WithExpression>
     */
    public function getExpressions(): array
    {
        return $this->expressions;
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'WITH';
        if ($this->recursive) {
            $result .= ' RECURSIVE';
        }

        return $result . "\n    " . $formatter->formatSerializablesList($this->expressions, ",\n    ");
    }

}
