<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\SqlSerializable;
use function array_values;

class Row implements SqlSerializable
{

    /** @var non-empty-array<ExpressionNode> */
    private $values;

    /**
     * @param non-empty-array<ExpressionNode> $values
     */
    public function __construct(array $values)
    {
        $this->values = array_values($values);
    }

    /**
     * @return non-empty-array<ExpressionNode>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ROW(';
        foreach ($this->values as $i => $value) {
            if ($i !== 0) {
                $result .= ', ';
            }
            $result .= $value->serialize($formatter);
        }

        return $result . ')';
    }

}