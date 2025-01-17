<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

/**
 * ..., ..., ...
 */
class ListExpression implements ArgumentNode
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<ExpressionNode> */
    private $items;

    /**
     * @param non-empty-array<ExpressionNode> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return non-empty-array<ExpressionNode>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatSerializablesList($this->items);
    }

}
