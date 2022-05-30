<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\SqlMode;

class PlatformSettings
{
    use StrictBehaviorMixin;

    /** @var Platform */
    private $platform;

    /** @var string */
    private $delimiter;

    /** @var Charset|null */
    private $charset;

    /** @var SqlMode */
    private $mode;

    /** @var bool */
    private $multiStatements;

    /** @var bool */
    private $quoteAllNames;

    /** @var bool */
    private $canonicalizeTypes;

    /** @var bool */
    private $verboseOutput;

    /** @var bool */
    private $optionalEquals;

    /**
     * @var bool - true when parsing mysql .test files containing special non-SQL syntax
     * @internal
     */
    public $mysqlTestMode = false;

    public function __construct(
        Platform $platform,
        ?string $delimiter = null,
        ?Charset $charset = null,
        ?SqlMode $mode = null,
        bool $multiStatements = false,
        bool $quoteAllNames = true,
        bool $canonicalizeTypes = true,
        bool $verboseOutput = true,
        bool $optionalEquals = true
    ) {
        if ($delimiter === null) {
            $delimiter = ';';
        }
        $this->platform = $platform;
        $this->delimiter = $delimiter;
        $this->charset = $charset;
        $this->mode = $mode ?? $platform->getDefaultMode();
        $this->multiStatements = $multiStatements;
        $this->quoteAllNames = $quoteAllNames;
        $this->canonicalizeTypes = $canonicalizeTypes;
        $this->verboseOutput = $verboseOutput;
        $this->optionalEquals = $optionalEquals;
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

    public function setQuoteAllNames(bool $quote): void
    {
        $this->quoteAllNames = $quote;
    }

    public function quoteAllNames(): bool
    {
        return $this->quoteAllNames;
    }

    public function setCanonicalizeTypes(bool $canonicalize): void
    {
        $this->canonicalizeTypes = $canonicalize;
    }

    public function canonicalizeTypes(): bool
    {
        return $this->canonicalizeTypes;
    }

    public function verboseOutput(): bool
    {
        return $this->verboseOutput;
    }

    public function optionalEquals(): bool
    {
        return $this->optionalEquals;
    }

}
