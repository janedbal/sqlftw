<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Binlog\BinlogCommand;
use SqlFtw\Sql\Keyword;

class BinlogCommandParser
{
    use StrictBehaviorMixin;

    /**
     * BINLOG 'str'
     */
    public function parseBinlog(TokenList $tokenList): BinlogCommand
    {
        $tokenList->expectKeyword(Keyword::BINLOG);
        $value = $tokenList->expectString();

        return new BinlogCommand($value);
    }

}
