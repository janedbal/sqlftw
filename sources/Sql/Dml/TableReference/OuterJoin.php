<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\InvalidDefinitionException;

class OuterJoin extends Join
{
    use StrictBehaviorMixin;

    /** @var JoinSide */
    private $joinSide;

    /** @var RootNode|null */
    private $condition;

    /** @var non-empty-array<string>|null */
    private $using;

    /**
     * @param non-empty-array<string>|null $using
     */
    public function __construct(
        TableReferenceNode $left,
        TableReferenceNode $right,
        JoinSide $joinSide,
        ?RootNode $condition,
        ?array $using
    ) {
        parent::__construct($left, $right);

        if ($condition === null && $using === null) {
            throw new InvalidDefinitionException('Either join condition or USING can be set, not both.');
        }

        $this->joinSide = $joinSide;
        $this->condition = $condition;
        $this->using = $using;
    }

    public function getJoinSide(): JoinSide
    {
        return $this->joinSide;
    }

    public function getCondition(): ?RootNode
    {
        return $this->condition;
    }

    /**
     * @return non-empty-array<string>|null
     */
    public function getUsing(): ?array
    {
        return $this->using;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->left->serialize($formatter) . ' ' . $this->joinSide->serialize($formatter)
            . ' JOIN ' . $this->right->serialize($formatter);

        if ($this->condition !== null) {
            $result .= ' ON ' . $this->condition->serialize($formatter);
        } elseif ($this->using !== null) {
            $result .= ' USING (' . $formatter->formatNamesList($this->using) . ')';
        }

        return $result;
    }

}
