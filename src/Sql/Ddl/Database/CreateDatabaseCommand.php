<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Database;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;

class CreateDatabaseCommand implements \SqlFtw\Sql\Ddl\Database\DatabaseCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string|null */
    private $name;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    /** @var \SqlFtw\Sql\Collation|null */
    private $collation;

    /** @var bool */
    private $ifNotExists;

    public function __construct(?string $name, ?Charset $charset, ?Collation $collation = null, bool $ifNotExists = false)
    {
        $this->name = $name;
        $this->charset = $charset;
        $this->collation = $collation;
        $this->ifNotExists = $ifNotExists;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?Collation
    {
        return $this->collation;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE DATABASE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $formatter->formatName($this->name);
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET = ' . $this->charset->serialize($formatter);
        }
        if ($this->collation !== null) {
            $result .= ' COLLATION = ' . $this->collation->serialize($formatter);
        }

        return $result;
    }

}
