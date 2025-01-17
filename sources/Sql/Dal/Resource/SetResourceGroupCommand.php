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

class SetResourceGroupCommand extends Statement implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var non-empty-array<int>|null */
    private $threadIds;

    /**
     * @param non-empty-array<int>|null $threadIds
     */
    public function __construct(string $name, ?array $threadIds = null)
    {
        $this->name = $name;
        $this->threadIds = $threadIds;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET RESOURCE GROUP ' . $formatter->formatName($this->name);
        if ($this->threadIds !== null) {
            $result .= ' FOR ' . $formatter->formatValuesList($this->threadIds);
        }

        return $result;
    }

}
