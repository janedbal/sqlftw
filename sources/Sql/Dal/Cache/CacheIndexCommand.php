<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Cache;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;
use function is_array;

/**
 * MySQL MyISAM tables only
 */
class CacheIndexCommand extends Statement implements CacheCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $keyCache;

    /** @var non-empty-array<TableIndexList> */
    private $tableIndexLists;

    /** @var non-empty-array<string>|bool|null */
    private $partitions;

    /**
     * @param non-empty-array<TableIndexList> $tableIndexLists
     * @param non-empty-array<string>|bool|null $partitions
     */
    public function __construct(string $keyCache, array $tableIndexLists, $partitions = null)
    {
        $this->keyCache = $keyCache;
        $this->tableIndexLists = $tableIndexLists;
        $this->partitions = $partitions;
    }

    public function getKeyCache(): string
    {
        return $this->keyCache;
    }

    /**
     * @return non-empty-array<TableIndexList>
     */
    public function getTableIndexLists(): array
    {
        return $this->tableIndexLists;
    }

    /**
     * @return non-empty-array<string>|bool|null
     */
    public function getPartitions()
    {
        return $this->partitions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CACHE INDEX ' . $formatter->formatSerializablesList($this->tableIndexLists);

        if ($this->partitions !== null) {
            $result .= ' PARTITION';
            if (is_array($this->partitions)) {
                $result .= ' (' . $formatter->formatNamesList($this->partitions) . ')';
            } else {
                $result .= ' (ALL)';
            }
        }

        return $result . ' IN ' . $formatter->formatName($this->keyCache);
    }

}
