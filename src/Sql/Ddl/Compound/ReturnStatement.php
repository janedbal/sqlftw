<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class ReturnStatement implements \SqlFtw\Sql\Statement
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Statement */
    private $statement;

    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    public function getStatement(): Statement
    {
        return $this->statement;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'RETURN ' . $this->statement->serialize($formatter) . ";\n";
    }

}
