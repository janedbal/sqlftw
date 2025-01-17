<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use SqlFtw\Util\TypeChecker;

class GetDiagnosticsStatement extends Statement implements CompoundStatementItem, Command
{
    use StrictBehaviorMixin;

    /** @var DiagnosticsArea|null */
    private $area;

    /** @var non-empty-array<DiagnosticsItem>|null */
    private $statementItems;

    /** @var RootNode|null */
    private $conditionNumber;

    /** @var non-empty-array<DiagnosticsItem>|null */
    private $conditionItems;

    /**
     * @param non-empty-array<DiagnosticsItem>|null $statementItems
     * @param non-empty-array<DiagnosticsItem>|null $conditionItems
     */
    public function __construct(
        ?DiagnosticsArea $area,
        ?array $statementItems,
        ?RootNode $conditionNumber,
        ?array $conditionItems
    ) {
        if ((($statementItems !== null) ^ ($conditionItems === null))) { // @phpstan-ignore-line XOR needed
            throw new InvalidDefinitionException('When statementItems are set, conditionItems must not be set.');
        }
        if (!(($conditionNumber !== null) ^ ($conditionItems === null))) { // @phpstan-ignore-line XOR needed
            throw new InvalidDefinitionException('When conditionNumber is set, conditionItems must be set.');
        }

        if ($conditionItems !== null) {
            foreach ($conditionItems as $item) {
                $item = $item->getItem();
                TypeChecker::check($item, ConditionInformationItem::class);
            }
        } elseif ($statementItems !== null) {
            foreach ($statementItems as $item) {
                $item = $item->getItem();
                TypeChecker::check($item, StatementInformationItem::class);
            }
        }

        $this->area = $area;
        $this->statementItems = $statementItems;
        $this->conditionNumber = $conditionNumber;
        $this->conditionItems = $conditionItems;
    }

    public function getArea(): ?DiagnosticsArea
    {
        return $this->area;
    }

    /**
     * @return non-empty-array<DiagnosticsItem>
     */
    public function getStatementItems(): ?array
    {
        return $this->conditionItems;
    }

    public function getConditionNumber(): ?RootNode
    {
        return $this->conditionNumber;
    }

    /**
     * @return non-empty-array<DiagnosticsItem>
     */
    public function getConditionItems(): ?array
    {
        return $this->conditionItems;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'GET';
        if ($this->area !== null) {
            $result .= ' ' . $this->area->serialize($formatter);
        }
        $result .= ' DIAGNOSTICS ';
        if ($this->statementItems !== null) {
            $result .= $formatter->formatSerializablesList($this->statementItems);
        } elseif ($this->conditionNumber !== null && $this->conditionItems !== null) {
            $result .= 'CONDITION ' . $this->conditionNumber->serialize($formatter) . ' ' . $formatter->formatSerializablesList($this->conditionItems);
        } else {
            throw new ShouldNotHappenException('Either conditionItems or statementItems must be set.');
        }

        return $result;
    }

}
