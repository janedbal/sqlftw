<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Throwable;

class InvalidVersionException extends ParserException
{

    /** @var string */
    private $feature;

    public function __construct(string $feature, TokenList $tokenList, ?Throwable $previous = null)
    {
        $platform = $tokenList->getSettings()->getPlatform()->format();

        parent::__construct("Platform $platform does not support feature $feature.", $tokenList, $previous);

        $this->feature = $feature;
    }

    public function getFeature(): string
    {
        return $this->feature;
    }

}
