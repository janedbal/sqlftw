<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Server;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;

class CreateSpatialReferenceSystemCommand extends Statement implements ServerCommand
{
    use StrictBehaviorMixin;

    /** @var int */
    private $srid;

    /** @var string */
    private $name;

    /** @var string */
    private $definition;

    /** @var string|null */
    private $organization;

    /** @var int|null */
    private $identifiedBy;

    /** @var string|null */
    private $description;

    /** @var bool */
    private $orReplace;

    /** @var bool */
    private $ifNotExists;

    public function __construct(
        int $srid,
        string $name,
        string $definition,
        ?string $organization = null,
        ?int $identifiedBy = null,
        ?string $description = null,
        bool $orReplace = false,
        bool $ifNotExists = false
    ) {
        if ($orReplace && $ifNotExists) {
            throw new InvalidDefinitionException('OR REPLACE and IF NOT EXISTS can not be both set.');
        }
        if ($organization === null && $identifiedBy !== null) {
            throw new InvalidDefinitionException('ORGANIZATION must be set WHEN IDENTIFIED BY set.');
        }

        $this->srid = $srid;
        $this->name = $name;
        $this->definition = $definition;
        $this->organization = $organization;
        $this->identifiedBy = $identifiedBy;
        $this->description = $description;
        $this->orReplace = $orReplace;
        $this->ifNotExists = $ifNotExists;
    }

    public function getSrid(): int
    {
        return $this->srid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function getIdentifiedBy(): ?int
    {
        return $this->identifiedBy;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function orReplace(): bool
    {
        return $this->orReplace;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->orReplace) {
            $result .= 'OR REPLACE ';
        }

        $result .= 'SPATIAL REFERENCE SYSTEM ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }

        $result .= $this->srid . ' NAME ' . $formatter->formatString($this->name)
            . ' DEFINITION ' . $formatter->formatString($this->definition);

        if ($this->organization !== null) {
            $result .= ' ORGANIZATION ' . $formatter->formatString($this->organization);
            if ($this->identifiedBy !== null) {
                $result .= ' IDENTIFIED BY ' . $this->identifiedBy;
            }
        }
        if ($this->description !== null) {
            $result .= ' DESCRIPTION ' . $formatter->formatString($this->description);
        }

        return $result;
    }

}
