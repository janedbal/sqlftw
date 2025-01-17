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
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\UserName;

class DropRoleCommand extends Statement implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<UserName> */
    private $roles;

    /** @var bool */
    private $ifExists;

    /**
     * @param non-empty-array<UserName> $roles
     */
    public function __construct(array $roles, bool $ifExists = false)
    {
        $this->roles = $roles;
        $this->ifExists = $ifExists;
    }

    /**
     * @return non-empty-array<UserName>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP ROLE ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }

        return $result . $formatter->formatSerializablesList($this->roles);
    }

}
