<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Prepared;

use SqlFtw\Formatter\Formatter;

class ExecuteCommand implements \SqlFtw\Sql\Dml\Prepared\PreparedStatementCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string[]|null */
    private $variables;

    public function __construct(string $name, ?array $variables = null)
    {
        $this->name = $name;
        $this->variables = $variables;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'EXECUTE ' . $formatter->formatName($this->name);
        if ($this->variables !== null) {
            $result .= ' USING ' . implode(', ', $this->variables);
        }

        return $result;
    }

}
