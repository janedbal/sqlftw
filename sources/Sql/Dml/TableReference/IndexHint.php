<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\PrimaryLiteral;
use SqlFtw\Sql\SqlSerializable;

class IndexHint implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var IndexHintAction */
    private $action;

    /** @var IndexHintTarget|null */
    private $target;

    /** @var array<string|PrimaryLiteral> */
    private $indexes;

    /**
     * @param array<string|PrimaryLiteral> $indexes
     */
    public function __construct(IndexHintAction $action, ?IndexHintTarget $target, array $indexes)
    {
        $this->action = $action;
        $this->target = $target;
        $this->indexes = $indexes;
    }

    public function getAction(): IndexHintAction
    {
        return $this->action;
    }

    public function getTarget(): ?IndexHintTarget
    {
        return $this->target;
    }

    /**
     * @return array<string|PrimaryLiteral>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->action->serialize($formatter) . ' INDEX';
        if ($this->target !== null) {
            $result .= ' FOR ' . $this->target->serialize($formatter);
        }
        $result .= ' (' . ($this->indexes !== [] ? $formatter->formatNamesList($this->indexes) : '') . ')';

        return $result;
    }

}
