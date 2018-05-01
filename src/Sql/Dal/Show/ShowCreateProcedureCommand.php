<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class ShowCreateProcedureCommand implements \SqlFtw\Sql\Dal\Show\ShowCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $name;

    public function __construct(QualifiedName $name)
    {
        $this->name = $name;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW CREATE PROCEDURE ' . $this->name->serialize($formatter);
    }

}
