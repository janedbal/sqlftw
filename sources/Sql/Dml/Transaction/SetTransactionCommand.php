<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Transaction;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\Scope;
use SqlFtw\Sql\Statement;

class SetTransactionCommand extends Statement implements TransactionCommand
{
    use StrictBehaviorMixin;

    /** @var Scope|null */
    private $scope;

    /** @var TransactionIsolationLevel|null */
    private $isolationLevel;

    /** @var bool|null */
    private $write;

    public function __construct(?Scope $scope, ?TransactionIsolationLevel $isolationLevel, ?bool $write = null)
    {
        $this->scope = $scope;
        $this->isolationLevel = $isolationLevel;
        $this->write = $write;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET';
        if ($this->scope !== null) {
            $result .= ' ' . $this->scope->serialize($formatter);
        }
        $result .= ' TRANSACTION';
        if ($this->isolationLevel !== null) {
            $result .= ' ISOLATION LEVEL ' . $this->isolationLevel->serialize($formatter);
        }
        if ($this->isolationLevel !== null && $this->write !== null) {
            $result .= ',';
        }
        if ($this->write !== null) {
            $result .= $this->write ? ' READ WRITE' : ' READ ONLY';
        }

        return $result;
    }

}
