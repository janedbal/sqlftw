<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

/**
 * Interface for SELECT, TABLE and VALUES commands
 */
interface SimpleQuery extends Query
{

    /**
     * @return static
     */
    public function removeOrderBy(): self;

    /**
     * @return static
     */
    public function removeLimit(): self;

    /**
     * @return static
     */
    public function removeInto(): self;

}
