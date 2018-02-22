<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Utility;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\TableName;

class DescribeTableCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName */
    private $table;

    /** @var string|null */
    private $column;

    public function __construct(TableName $table, ?string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DESCRIBE ' . $this->table->serialize($formatter);

        if ($this->column) {
            if (strtr($this->column, '_%', 'xx') === $this->column) {
                $result .= $formatter->formatName($this->column);
            } else {
                $result .= $formatter->formatString($this->column);
            }
        }

        return $result;
    }

}
