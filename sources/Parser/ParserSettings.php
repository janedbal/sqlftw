<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\SqlMode;

class ParserSettings
{
    use StrictBehaviorMixin;

    /** @var Platform */
    private $platform;

    /** @var bool */
    private $multiStatements;

    /**
     * @var bool - true when parsing mysql .test files containing non-SQL syntax
     * @internal
     */
    public $mysqlTestMode = false;

    // state -----------------------------------------------------------------------------------------------------------

    /** @var string */
    private $delimiter;

    /** @var Charset|null */
    private $charset;

    /** @var SqlMode */
    private $mode;

    /** @var bool */
    private $inRoutine = false;

    public function __construct(
        Platform $platform,
        ?string $delimiter = null,
        ?Charset $charset = null,
        ?SqlMode $mode = null,
        bool $multiStatements = false
    ) {
        if ($delimiter === null) {
            $delimiter = ';';
        }
        $this->platform = $platform;
        $this->delimiter = $delimiter;
        $this->charset = $charset;
        $this->mode = $mode ?? $platform->getDefaultMode();
        $this->multiStatements = $multiStatements;
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function setCharset(Charset $charset): void
    {
        $this->charset = $charset;
    }

    public function getMode(): SqlMode
    {
        return $this->mode;
    }

    public function setMode(SqlMode $mode): void
    {
        $this->mode = $mode;
    }

    public function multiStatements(): bool
    {
        return $this->multiStatements;
    }

    public function setMultiStatements(bool $value): void
    {
        $this->multiStatements = $value;
    }

    public function inRoutine(): bool
    {
        return $this->inRoutine;
    }

    public function setInRoutine(bool $value): void
    {
        $this->inRoutine = $value;
    }

}
