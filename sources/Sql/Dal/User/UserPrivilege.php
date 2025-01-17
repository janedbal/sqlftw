<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class UserPrivilege implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var UserPrivilegeType */
    private $type;

    /** @var non-empty-array<string>|null */
    private $columns;

    /**
     * @param non-empty-array<string>|null $columns
     */
    public function __construct(UserPrivilegeType $type, ?array $columns)
    {
        $this->type = $type;
        $this->columns = $columns;
    }

    public function getType(): UserPrivilegeType
    {
        return $this->type;
    }

    /**
     * @return non-empty-array<string>|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);
        if ($this->columns !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columns) . ')';
        }

        return $result;
    }

}
