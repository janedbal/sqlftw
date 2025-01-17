<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\UserName;

class AlteredUser implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var UserName|FunctionCall */
    private $user;

    /** @var AlterUserAction|null */
    private $action;

    /**
     * @param UserName|FunctionCall $user
     */
    public function __construct($user, ?AlterUserAction $action = null)
    {
        $this->user = $user;
        $this->action = $action;
    }

    /**
     * @return UserName|FunctionCall
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getAction(): ?AlterUserAction
    {
        return $this->action;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->user->serialize($formatter);

        if ($this->action !== null) {
            $result .= ' ' . $this->action->serialize($formatter);
        }

        return $result;
    }

}
