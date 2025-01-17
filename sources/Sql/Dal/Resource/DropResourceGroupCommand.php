<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Resource;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Statement;

class DropResourceGroupCommand extends Statement implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var bool */
    private $force;

    public function __construct(string $name, bool $force = false)
    {
        $this->name = $name;
        $this->force = $force;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'CREATE RESOURCE GROUP ' . $formatter->formatName($this->name) . ($this->force ? ' FORCE' : '');
    }

}
