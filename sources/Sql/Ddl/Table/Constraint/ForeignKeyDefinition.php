<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\InvalidDefinitionException;
use function count;

class ForeignKeyDefinition implements TableItem, ConstraintBody
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $columns;

    /** @var ReferenceDefinition */
    private $reference;

    /** @var string|null */
    private $indexName;

    /**
     * @param non-empty-array<string> $columns
     */
    public function __construct(
        array $columns,
        ReferenceDefinition $reference,
        ?string $indexName = null
    ) {
        if (count($columns) !== count($reference->getSourceColumns())) {
            throw new InvalidDefinitionException('Number of foreign key columns and source columns does not match.');
        }

        $this->columns = $columns;
        $this->reference = $reference;
        $this->indexName = $indexName;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getReference(): ReferenceDefinition
    {
        return $this->reference;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FOREIGN KEY';
        if ($this->indexName !== null) {
            $result .= ' ' . $formatter->formatName($this->indexName);
        }
        $result .= ' (' . $formatter->formatNamesList($this->columns) . ') ' . $this->reference->serialize($formatter);

        return $result;
    }

}
