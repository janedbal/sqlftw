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
use function is_int;

class UserPasswordLockOption implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var UserPasswordLockOptionType */
    private $type;

    /** @var int|null */
    private $value;

    /**
     * @param string|int|null $value
     */
    public function __construct(UserPasswordLockOptionType $type, $value = null)
    {
        UserPasswordLockOptionType::validate($type->getValue(), $value);

        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): UserPasswordLockOptionType
    {
        return $this->type;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);

        if (is_int($this->value) && $this->type->equalsAny(UserPasswordLockOptionType::PASSWORD_EXPIRE)) {
            $result .= ' INTERVAL ' . $this->value . ' DAY';
        } elseif (is_int($this->value) && $this->type->equalsAny(UserPasswordLockOptionType::PASSWORD_REUSE_INTERVAL)) {
            $result .= ' ' . $this->value . ' DAY';
        } elseif ($this->value !== null) {
            $result .= ' ' . $this->value;
        }

        return $result;
    }

}
