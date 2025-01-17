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
 * EXISTS (SELECT ...)
 */
class ExistsExpression implements RootNode
{
    use StrictBehaviorMixin;

    /** @var Subquery */
    private $subquery;

    public function __construct(Subquery $subquery)
    {
        $this->subquery = $subquery;
    }

    public function getSubquery(): Subquery
    {
        return $this->subquery;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'EXISTS (' . $this->subquery->serialize($formatter) . ')';
    }

}
