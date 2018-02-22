<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use SqlFtw\Sql\Keyword;

class SelectLockWaitOption extends \SqlFtw\Sql\SqlEnum
{

    public const NO_WAIT = Keyword::NOWAIT;
    public const SKIP_LOCKED = Keyword::SKIP . ' ' . Keyword::LOCKED;

}
