<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class ShowSlaveStatusCommand extends Statement implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $channel;

    public function __construct(?string $channel)
    {
        $this->channel = $channel;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW SLAVE STATUS'
            . ($this->channel !== null ? ' FOR CHANNEL ' . $formatter->formatName($this->channel) : '');
    }

}
