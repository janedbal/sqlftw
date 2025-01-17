<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\SchemaObjectsCommand;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class DropViewCommand extends Statement implements ViewCommand, SchemaObjectsCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<QualifiedName> */
    private $names;

    /** @var bool */
    private $ifExists;

    /** @var DropViewOption|null */
    private $option;

    /**
     * @param non-empty-array<QualifiedName> $names
     */
    public function __construct(array $names, bool $ifExists = false, ?DropViewOption $option = null)
    {
        $this->names = $names;
        $this->ifExists = $ifExists;
        $this->option = $option;
    }

    /**
     * @return non-empty-array<QualifiedName>
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function getOption(): ?DropViewOption
    {
        return $this->option;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP VIEW ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->names);
        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }

        return $result;
    }

}
