<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class ResetReplicaCommand extends Statement implements ReplicationCommand
{
    use StrictBehaviorMixin;

    /** @var bool */
    private $all;

    /** @var string|null */
    private $channel;

    public function __construct(bool $all, ?string $channel = null)
    {
        $this->all = $all;
        $this->channel = $channel;
    }

    public function all(): bool
    {
        return $this->all;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RESET REPLICA';
        if ($this->all) {
            $result .= ' ALL';
        }
        if ($this->channel !== null) {
            $result .= ' FOR CHANNEL ' . $formatter->formatString($this->channel);
        }

        return $result;
    }

}
