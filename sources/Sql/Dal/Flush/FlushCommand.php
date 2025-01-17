<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Flush;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Statement;
use function array_map;
use function implode;

class FlushCommand extends Statement implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<FlushOption> */
    private $options;

    /** @var string|null */
    private $channel;

    /** @var bool */
    private $local;

    /**
     * @param non-empty-array<FlushOption> $options
     */
    public function __construct(array $options, ?string $channel = null, bool $local = false)
    {
        $this->options = $options;
        $this->channel = $channel;
        $this->local = $local;
    }

    /**
     * @return non-empty-array<FlushOption>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FLUSH ';
        if ($this->isLocal()) {
            $result .= 'LOCAL ';
        }
        $result .= implode(', ', array_map(function (FlushOption $option) use ($formatter) {
            if ($this->channel !== null && $option->equalsAny(FlushOption::RELAY_LOGS)) {
                return $option->serialize($formatter) . ' FOR CHANNEL ' . $formatter->formatString($this->channel);
            } else {
                return $option->serialize($formatter);
            }
        }, $this->options));

        return $result;
    }

}
