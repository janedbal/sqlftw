<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;

class ShowRelaylogEventsCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var string|null */
    private $logName;

    /** @var int|null */
    private $limit;

    /** @var  int|null */
    private $offset;

    public function __construct(?string $logName = null, ?int $limit, ?int $offset)
    {
        $this->logName = $logName;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function getLogName(): ?string
    {
        return $this->logName;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW RELAYLOG EVENTS';
        if ($this->logName) {
            $result .= ' IN ' . $formatter->formatName($this->logName);
        }
        if ($this->offset && !$this->limit) {
            $result .= ' FROM ' . $this->offset;
        }
        if ($this->limit) {
            $result .= ' LIMIT ';
            if ($this->offset) {
                $result .= $this->offset . ', ';
            }
            $result .= $this->limit;
        }

        return $result;
    }

}
