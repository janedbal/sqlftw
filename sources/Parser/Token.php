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

final class Token
{
    use StrictBehaviorMixin;

    /** @var int */
    public $type;

    /** @var int */
    public $position;

    /** @var int */
    public $row;

    /** @var string */
    public $value;

    /** @var string|null */
    public $original;

    /** @var string|null */
    public $condition;

    /** @var LexerException|null */
    public $exception;

    public function __construct(
        int $type,
        int $position,
        int $row,
        string $value,
        ?string $original = null,
        ?string $condition = null,
        ?LexerException $exception = null
    ) {
        $this->type = $type;
        $this->position = $position;
        $this->row = $row;
        $this->value = $value;
        $this->original = $original;
        $this->condition = $condition;
        $this->exception = $exception;
    }

}
