<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use SqlFtw\Util\TypeChecker;
use function array_filter;
use function implode;

/**
 * @phpstan-import-type SlaveOptionValue from SlaveOption
 */
class ChangeMasterToCommand extends Statement implements ReplicationCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string, SlaveOptionValue|null> */
    private $options;

    /** @var string|null */
    private $channel;

    /**
     * @param non-empty-array<string, SlaveOptionValue> $options
     */
    public function __construct(array $options, ?string $channel = null)
    {
        foreach ($options as $option => $value) {
            if (!SlaveOption::validateValue($option)) {
                throw new InvalidDefinitionException("Unknown option '$option' for CHANGE MASTER TO.");
            }
            TypeChecker::check($value, SlaveOption::getTypes()[$option], $option);

            $options[(string) $option] = $value;
        }

        $this->options = $options;
        $this->channel = $channel;
    }

    /**
     * @return non-empty-array<string, SlaveOptionValue|null>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return SlaveOptionValue|null $option
     */
    public function getOption(string $option)
    {
        SlaveOption::get($option);

        return $this->options[$option] ?? null;
    }

    /**
     * @param SlaveOptionValue|null $value
     */
    public function setOption(string $option, $value): void
    {
        SlaveOption::get($option);
        TypeChecker::check($value, SlaveOption::getTypes()[$option], $option);

        $this->options[$option] = $value;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = "CHANGE MASTER TO \n  " . implode(",\n  ", array_filter(Arr::mapPairs(
            $this->options,
            static function (string $option, $value) use ($formatter): ?string {
                if ($value === null) {
                    return null;
                } elseif ($option === SlaveOption::IGNORE_SERVER_IDS) {
                    return $option . ' = (' . $formatter->formatValuesList($value) . ')';
                } elseif ($value instanceof TimeInterval) {
                    return $option . ' = INTERVAL ' . $formatter->formatValue($value);
                } else {
                    return $option . ' = ' . $formatter->formatValue($value);
                }
            }
        )));

        if ($this->channel !== null) {
            $result .= "\nFOR CHANNEL " . $formatter->formatString($this->channel);
        }

        return $result;
    }

}
