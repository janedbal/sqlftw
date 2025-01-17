<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Schema;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\Table\Option\ThreeStateValue;
use function ltrim;

class SchemaOptions implements SchemaCommand
{
    use StrictBehaviorMixin;

    /** @var Charset|null */
    private $charset;

    /** @var Collation|null */
    private $collation;

    /** @var bool|null */
    private $encryption;

    /** @var ThreeStateValue|null */
    private $readOnly;

    public function __construct(
        ?Charset $charset,
        ?Collation $collation = null,
        ?bool $encryption = null,
        ?ThreeStateValue $readOnly = null
    )
    {
        $this->charset = $charset;
        $this->collation = $collation;
        $this->encryption = $encryption;
        $this->readOnly = $readOnly;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?Collation
    {
        return $this->collation;
    }

    public function getEncryption(): ?bool
    {
        return $this->encryption;
    }

    public function getReadOnly(): ?ThreeStateValue
    {
        return $this->readOnly;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }
        if ($this->collation !== null) {
            $result .= ' COLLATE ' . $this->collation->serialize($formatter);
        }
        if ($this->encryption !== null) {
            $result .= ' ENCRYPTION ' . ($this->encryption ? "'Y'" : "'N'");
        }
        if ($this->readOnly !== null) {
            $result .= ' READ ONLY ' . $this->readOnly->serialize($formatter);
        }

        return ltrim($result);
    }

}
