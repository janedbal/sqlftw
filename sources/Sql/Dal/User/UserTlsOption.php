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
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;
use function is_string;

class UserTlsOption implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var UserTlsOptionType */
    private $type;

    /** @var string|null */
    private $value;

    public function __construct(UserTlsOptionType $type, ?string $value = null)
    {
        if (!$type->equalsAny(UserTlsOptionType::SSL, UserTlsOptionType::X509)) {
            if (!is_string($value) || $value === '') {
                throw new InvalidDefinitionException("Value of option '$type' must be a non-empty string.");
            }
        }
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): UserTlsOptionType
    {
        return $this->type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type->serialize($formatter)
            . ($this->value !== null ? ' ' . $formatter->formatString($this->value) : '');
    }

}
