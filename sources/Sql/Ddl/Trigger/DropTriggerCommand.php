<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class DropTriggerCommand extends Statement implements TriggerCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var bool */
    private $ifExists;

    public function __construct(QualifiedName $name, bool $ifExists = false)
    {
        $this->name = $name;
        $this->ifExists = $ifExists;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP TRIGGER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $this->name->serialize($formatter);

        return $result;
    }

}
