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

class MaxValueLiteral implements KeywordLiteral
{
    use StrictBehaviorMixin;

    public function getValue(): string
    {
        return 'MAXVALUE';
    }

    public function serialize(Formatter $formatter): string
    {
        return 'MAXVALUE';
    }

}
