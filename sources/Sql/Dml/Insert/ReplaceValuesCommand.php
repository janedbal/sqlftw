<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\QualifiedName;
use function array_map;
use function implode;

class ReplaceValuesCommand extends InsertOrReplaceCommand implements ReplaceCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<array<ExpressionNode>> */
    private $rows;

    /**
     * @param non-empty-array<array<ExpressionNode>> $rows
     * @param array<ColumnIdentifier>|null $columns
     * @param non-empty-array<string>|null $partitions
     */
    public function __construct(
        QualifiedName $table,
        array $rows,
        ?array $columns = null,
        ?array $partitions = null,
        ?InsertPriority $priority = null,
        bool $ignore = false
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->rows = $rows;
    }

    /**
     * @return non-empty-array<array<ExpressionNode>>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'REPLACE' . $this->serializeBody($formatter);

        $result .= ' VALUES ' . implode(', ', array_map(static function (array $values) use ($formatter): string {
            return '(' . implode(', ', array_map(static function (ExpressionNode $value) use ($formatter): string {
                return $value->serialize($formatter);
            }, $values)) . ')';
        }, $this->rows));

        return $result;
    }

}
