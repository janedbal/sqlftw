<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\InvalidVersionException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Ddl\Instance\AlterInstanceAction;
use SqlFtw\Sql\Ddl\Instance\AlterInstanceCommand;
use SqlFtw\Sql\Keyword;

class InstanceCommandParser
{
    use StrictBehaviorMixin;

    /**
     * 8.0 https://dev.mysql.com/doc/refman/8.0/en/alter-instance.html
     * ALTER INSTANCE instance_action
     *
     * instance_action: {
     *   | {ENABLE|DISABLE} INNODB REDO_LOG
     *   | ROTATE INNODB MASTER KEY
     *   | ROTATE BINLOG MASTER KEY
     *   | RELOAD TLS
     *      [FOR CHANNEL {mysql_main | mysql_admin}]
     *      [NO ROLLBACK ON ERROR]
     *   | RELOAD KEYRING
     * }
     *
     * 5.7 https://dev.mysql.com/doc/refman/5.7/en/alter-instance.html
     * ALTER INSTANCE ROTATE INNODB MASTER KEY
     */
    public function parseAlterInstance(TokenList $tokenList): AlterInstanceCommand
    {
        if ($tokenList->using(null, 80000)) {
            $tokenList->expectKeywords(Keyword::ALTER, Keyword::INSTANCE);

            $action = $tokenList->expectMultiNameEnum(AlterInstanceAction::class);

            $forChannel = null;
            $noRollbackOnError = false;
            if ($action->equalsValue(AlterInstanceAction::RELOAD_TLS)) {
                if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
                    $forChannel = $tokenList->expectNonReservedNameOrString();
                }
                $noRollbackOnError = $tokenList->hasKeywords(Keyword::NO, Keyword::ROLLBACK, Keyword::ON, Keyword::ERROR);
            }

            return new AlterInstanceCommand($action, $forChannel, $noRollbackOnError);
        } elseif ($tokenList->using(null, 50700)) {
            $tokenList->expectKeywords(Keyword::ALTER, Keyword::INSTANCE, Keyword::ROTATE);
            $tokenList->expectName('INNODB');
            $tokenList->expectKeywords(Keyword::MASTER, Keyword::KEY);

            return new AlterInstanceCommand(AlterInstanceAction::get(AlterInstanceAction::ROTATE_INNODB_MASTER_KEY));
        } else {
            throw new InvalidVersionException('ALTER INSTANCE is implemented since 5.7', $tokenList);
        }
    }

}
