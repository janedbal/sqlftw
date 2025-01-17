<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Partition;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class PartitioningDefinition implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var PartitioningCondition */
    private $condition;

    /** @var non-empty-array<PartitionDefinition>|null */
    private $partitions;

    /** @var int|null */
    private $partitionsNumber;

    /** @var PartitioningCondition|null */
    private $subpartitionsCondition;

    /** @var int|null */
    private $subpartitionsNumber;

    /**
     * @param non-empty-array<PartitionDefinition>|null $partitions
     */
    public function __construct(
        PartitioningCondition $condition,
        ?array $partitions,
        ?int $partitionsNumber = null,
        ?PartitioningCondition $subpartitionsCondition = null,
        ?int $subpartitionsNumber = null
    ) {
        $this->condition = $condition;
        $this->partitions = $partitions;
        $this->partitionsNumber = $partitionsNumber;
        $this->subpartitionsCondition = $subpartitionsCondition;
        $this->subpartitionsNumber = $subpartitionsNumber;
    }

    public function getCondition(): PartitioningCondition
    {
        return $this->condition;
    }

    /**
     * @return non-empty-array<PartitionDefinition>|null
     */
    public function getPartitions(): ?array
    {
        return $this->partitions;
    }

    public function getPartitionsNumber(): ?int
    {
        return $this->partitionsNumber;
    }

    public function getSubpartitionsCondition(): ?PartitioningCondition
    {
        return $this->subpartitionsCondition;
    }

    public function getSubpartitionsNumber(): ?int
    {
        return $this->subpartitionsNumber;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'PARTITION BY ' . $this->condition->serialize($formatter);
        if ($this->partitionsNumber !== null) {
            $result .= ' PARTITIONS ' . $this->partitionsNumber;
        }
        if ($this->subpartitionsCondition !== null) {
            $result .= ' SUBPARTITION BY ' . $this->subpartitionsCondition->serialize($formatter);
            if ($this->subpartitionsNumber !== null) {
                $result .= ' SUBPARTITIONS ' . $this->subpartitionsNumber;
            }
        }
        if ($this->partitions !== null) {
            $result .= '(' . $formatter->formatSerializablesList($this->partitions) . ')';
        }

        return $result;
    }

}
