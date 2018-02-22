<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use SqlFtw\Formatter\Formatter;

class TriggerPosition implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Trigger\TriggerOrder */
    private $order;

    /** @var string */
    private $otherTrigger;

    public function __construct(TriggerOrder $order, string $otherTrigger)
    {
        $this->order = $order;
        $this->otherTrigger = $otherTrigger;
    }

    public function getOrder(): TriggerOrder
    {
        return $this->order;
    }

    public function getOtherTrigger(): string
    {
        return $this->otherTrigger;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->order->serialize($formatter) . ' ' . $formatter->formatName($this->otherTrigger);
    }

}
