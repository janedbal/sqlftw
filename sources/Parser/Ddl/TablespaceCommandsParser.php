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
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Ddl\Tablespace\AlterTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\DropTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\TablespaceOption;
use SqlFtw\Sql\Keyword;

class TablespaceCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER [UNDO] TABLESPACE tablespace_name
     *     [{ADD|DROP} DATAFILE 'file_name'] -- NDB only
     *     [INITIAL_SIZE [=] size]      -- NDB only
     *     [AUTOEXTEND_SIZE [=] autoextend_size] -- NDB only
     *     [WAIT]                       -- NDB only
     *     [RENAME TO tablespace_name]
     *     [SET {ACTIVE|INACTIVE}]      -- InnoDB only
     *     [ENCRYPTION [=] {'Y' | 'N'}] -- InnoDB only
     *     [ENGINE [=] engine_name]
     *     [ENGINE_ATTRIBUTE [=] 'string']
     */
    public function parseAlterTablespace(TokenList $tokenList): AlterTablespaceCommand
    {
        $tokenList->expectKeyword(Keyword::ALTER);
        $undo = $tokenList->hasKeyword(Keyword::UNDO);
        $tokenList->expectKeyword(Keyword::TABLESPACE);

        $name = $tokenList->expectName();

        $options = [];
        $keyword = $tokenList->getAnyKeyword(Keyword::ADD, Keyword::DROP);
        if ($keyword !== null) {
            $tokenList->expectKeyword(Keyword::DATAFILE);
            $options[$keyword . ' ' . Keyword::DATAFILE] = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->passSymbol('=');
            $options[TablespaceOption::INITIAL_SIZE] = $tokenList->expectSize();
        }
        if ($tokenList->hasKeyword(Keyword::AUTOEXTEND_SIZE)) {
            $tokenList->passSymbol('=');
            $options[TablespaceOption::AUTOEXTEND_SIZE] = $tokenList->expectSize();
        }
        if ($tokenList->hasKeyword(Keyword::WAIT)) {
            $options[TablespaceOption::WAIT] = true;
        }
        if ($tokenList->hasKeywords(Keyword::RENAME, Keyword::TO)) {
            $options[TablespaceOption::RENAME_TO] = $tokenList->expectName();
        }
        if ($tokenList->hasKeyword(Keyword::SET)) {
            $options[TablespaceOption::SET] = $tokenList->expectAnyKeyword(Keyword::ACTIVE, Keyword::INACTIVE);
        }
        if ($tokenList->hasKeywords(Keyword::ENCRYPTION)) {
            $tokenList->passSymbol('=');
            $options[TablespaceOption::ENCRYPTION] = $tokenList->expectBool();
        }
        if ($tokenList->hasKeyword(Keyword::ENGINE)) {
            $tokenList->passSymbol('=');
            $options[TablespaceOption::ENGINE] = $tokenList->expectStorageEngineName();
        }
        if ($tokenList->hasKeyword(Keyword::ENGINE_ATTRIBUTE)) {
            $tokenList->passSymbol('=');
            $options[TablespaceOption::ENGINE_ATTRIBUTE] = $tokenList->expectString();
        }

        return new AlterTablespaceCommand($name, $options, $undo);
    }

    /**
     * CREATE [UNDO] TABLESPACE tablespace_name
     *     [ADD DATAFILE 'file_name']
     *     [FILE_BLOCK_SIZE = value]        -- InnoDB only
     *     [ENCRYPTION [=] {'Y' | 'N'}]     -- InnoDB only
     *     USE LOGFILE GROUP logfile_group  -- NDB only
     *     [EXTENT_SIZE [=] extent_size]    -- NDB only
     *     [INITIAL_SIZE [=] initial_size]  -- NDB only
     *     [AUTOEXTEND_SIZE [=] autoextend_size] -- NDB only
     *     [MAX_SIZE [=] max_size]          -- NDB only
     *     [NODEGROUP [=] nodegroup_id]     -- NDB only
     *     [WAIT | NO_WAIT]                 -- NDB only (NO_WAIT not documented)
     *     [COMMENT [=] 'string']           -- NDB only
     *     [ENGINE [=] engine_name]
     *     [ENGINE_ATTRIBUTE [=] 'string']
     */
    public function parseCreateTablespace(TokenList $tokenList): CreateTablespaceCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);
        $undo = $tokenList->hasKeyword(Keyword::UNDO);
        $tokenList->expectKeyword(Keyword::TABLESPACE);

        $name = $tokenList->expectName();

        // phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
        $keywords = [
            Keyword::ADD, Keyword::FILE_BLOCK_SIZE, Keyword::ENCRYPTION, Keyword::USE, Keyword::EXTENT_SIZE,
            Keyword::INITIAL_SIZE, Keyword::AUTOEXTEND_SIZE, Keyword::MAX_SIZE, Keyword::NODEGROUP, Keyword::WAIT,
            Keyword::NO_WAIT, Keyword::COMMENT, Keyword::ENGINE, Keyword::ENGINE_ATTRIBUTE,
        ];
        $options = [];
        while (($keyword = $tokenList->getAnyKeyword(...$keywords)) !== null) {
            switch ($keyword) {
                case Keyword::ADD:
                    $tokenList->expectKeyword(Keyword::DATAFILE);
                    $options[TablespaceOption::ADD_DATAFILE] = $tokenList->expectString();
                    break;
                case Keyword::FILE_BLOCK_SIZE:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::FILE_BLOCK_SIZE] = $tokenList->expectSize();
                    break;
                case Keyword::ENCRYPTION:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::ENCRYPTION] = $tokenList->expectBool();
                    break;
                case Keyword::USE:
                    $tokenList->expectKeywords(Keyword::LOGFILE, Keyword::GROUP);
                    $options[TablespaceOption::USE_LOGFILE_GROUP] = $tokenList->expectName();
                    break;
                case Keyword::EXTENT_SIZE:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::EXTENT_SIZE] = $tokenList->expectSize();
                    break;
                case Keyword::INITIAL_SIZE:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::INITIAL_SIZE] = $tokenList->expectSize();
                    break;
                case Keyword::AUTOEXTEND_SIZE:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::AUTOEXTEND_SIZE] = $tokenList->expectSize();
                    break;
                case Keyword::MAX_SIZE:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::MAX_SIZE] = $tokenList->expectSize();
                    break;
                case Keyword::NODEGROUP:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::NODEGROUP] = (int) $tokenList->expectUnsignedInt();
                    break;
                case Keyword::WAIT:
                    $options[TablespaceOption::WAIT] = true;
                    break;
                case Keyword::NO_WAIT:
                    $options[TablespaceOption::WAIT] = false;
                    break;
                case Keyword::COMMENT:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::COMMENT] = $tokenList->expectString();
                    break;
                case Keyword::ENGINE:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::ENGINE] = $tokenList->expectStorageEngineName();
                    break;
                case Keyword::ENGINE_ATTRIBUTE:
                    $tokenList->passSymbol('=');
                    $options[TablespaceOption::ENGINE_ATTRIBUTE] = $tokenList->expectString();
                    break;
            }
        }

        return new CreateTablespaceCommand($name, $options, $undo);
    }

    /**
     * DROP [UNDO] TABLESPACE tablespace_name
     *     [ENGINE [=] engine_name]
     */
    public function parseDropTablespace(TokenList $tokenList): DropTablespaceCommand
    {
        $tokenList->expectKeyword(Keyword::DROP);
        $undo = $tokenList->hasKeyword(Keyword::UNDO);
        $tokenList->expectKeyword(Keyword::TABLESPACE);

        $name = $tokenList->expectName();
        $engine = null;
        if ($tokenList->hasKeyword(Keyword::ENGINE)) {
            $tokenList->passSymbol('=');
            $engine = $tokenList->expectName();
        }

        return new DropTablespaceCommand($name, $engine, $undo);
    }

}
