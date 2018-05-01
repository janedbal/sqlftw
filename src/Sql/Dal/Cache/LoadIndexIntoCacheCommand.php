<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Cache;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;

/**
 * MySQL MyISAM tables only
 */
class LoadIndexIntoCacheCommand implements \SqlFtw\Sql\Dal\Cache\CacheCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\Cache\TableIndexList[] */
    private $tableIndexLists;

    /**
     * @param \SqlFtw\Sql\Dal\Cache\TableIndexList[] $tableIndexLists
     */
    public function __construct(array $tableIndexLists)
    {
        Check::itemsOfType($tableIndexLists, TableIndexList::class);

        $this->tableIndexLists = $tableIndexLists;
    }

    /**
     * @return \SqlFtw\Sql\Dal\Cache\TableIndexList[]
     */
    public function getTableIndexLists(): array
    {
        return $this->tableIndexLists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'LOAD INDEX INTO CACHE ' . $formatter->formatSerializablesList($this->tableIndexLists);
    }

}
