<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Option\TableOption;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\Ddl\Table\Partition\PartitioningDefinition;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Dml\Query\Query;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use function is_array;

/**
 * @phpstan-import-type TableOptionValue from TableOption
 */
class CreateTableCommand extends Statement implements AnyCreateTableCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var non-empty-array<TableItem>|null */
    private $items;

    /** @var TableOptionsList|null */
    private $options;

    /** @var PartitioningDefinition|null */
    private $partitioning;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifNotExists;

    /** @var DuplicateOption|null */
    private $duplicateOption;

    /** @var Query|null */
    private $query;

    /** @var bool */
    private $startTransaction;

    /**
     * @param non-empty-array<TableItem>|null $items
     * @param TableOptionsList|array<TableOptionValue>|null $options
     */
    public function __construct(
        QualifiedName $name,
        ?array $items,
        $options = null,
        ?PartitioningDefinition $partitioning = null,
        bool $temporary = false,
        bool $ifNotExists = false,
        ?DuplicateOption $duplicateOption = null,
        ?Query $query = null,
        bool $startTransaction = false
    ) {
        if ($duplicateOption !== null && $query === null) {
            throw new InvalidDefinitionException('IGNORE/REPLACE can be uses only with CREATE TABLE AS ... command.');
        }

        $this->name = $name;
        $this->items = $items;
        $this->options = is_array($options) ? new TableOptionsList($options) : $options;
        $this->partitioning = $partitioning;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
        $this->duplicateOption = $duplicateOption;
        $this->query = $query;
        $this->startTransaction = $startTransaction;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return non-empty-array<TableItem>|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    public function getOptions(): ?TableOptionsList
    {
        return $this->options;
    }

    public function getPartitioning(): ?PartitioningDefinition
    {
        return $this->partitioning;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function getDuplicateOption(): ?DuplicateOption
    {
        return $this->duplicateOption;
    }

    public function getQuery(): ?Query
    {
        return $this->query;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->temporary) {
            $result .= 'TEMPORARY ';
        }
        $result .= 'TABLE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS';
        }
        $result .= $this->name->serialize($formatter);

        if ($this->items !== null) {
            $result .= " (\n" . $formatter->indent . $formatter->formatSerializablesList($this->items, ",\n" . $formatter->indent) . "\n)";
        }

        if ($this->options !== null && !$this->options->isEmpty()) {
            $result .= ' ' . $this->options->serialize($formatter, ', ', ' ');
        }

        if ($this->partitioning !== null) {
            $result .= $this->partitioning->serialize($formatter);
        }

        if ($this->duplicateOption !== null) {
            $result .= "\n" . $this->duplicateOption->serialize($formatter);
        }

        if ($this->query !== null) {
            $result .= "\nAS " . $this->query->serialize($formatter);
        }

        if ($this->startTransaction) {
            $result .= "\nSTART TRANSACTION";
        }

        return $result;
    }

}
