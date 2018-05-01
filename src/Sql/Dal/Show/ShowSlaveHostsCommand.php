<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;

class ShowSlaveHostsCommand implements \SqlFtw\Sql\Dal\Show\ShowCommand
{
    use \Dogma\StrictBehaviorMixin;

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW SLAVE HOSTS';
    }

}
