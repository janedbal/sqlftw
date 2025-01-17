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
use SqlFtw\Sql\Ddl\LogfileGroup\AlterLogfileGroupCommand;
use SqlFtw\Sql\Ddl\LogfileGroup\CreateLogfileGroupCommand;
use SqlFtw\Sql\Ddl\LogfileGroup\DropLogfileGroupCommand;
use SqlFtw\Sql\Keyword;

/**
 * MySQL 5.7 only
 */
class LogfileGroupCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER LOGFILE GROUP logfile_group
     *     ADD UNDOFILE 'file_name'
     *     [INITIAL_SIZE [=] size]
     *     [WAIT]
     *     [ENGINE [=] engine_name]
     */
    public function parseAlterLogfileGroup(TokenList $tokenList): AlterLogfileGroupCommand
    {
        $tokenList->expectKeywords(Keyword::ALTER, Keyword::LOGFILE, Keyword::GROUP);
        $name = $tokenList->expectName();
        $tokenList->expectKeywords(Keyword::ADD, Keyword::UNDOFILE);
        $undoFile = $tokenList->expectString();

        $initialSize = null;
        if ($tokenList->hasKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->passSymbol('=');
            $initialSize = $tokenList->expectSize();
        }

        $wait = $tokenList->hasKeyword(Keyword::WAIT);

        $engine = null;
        if ($tokenList->hasKeyword(Keyword::ENGINE)) {
            $tokenList->passSymbol('=');
            $engine = $tokenList->expectStorageEngineName();
        }

        return new AlterLogfileGroupCommand($name, $engine, $undoFile, $initialSize, $wait);
    }

    /**
     * CREATE LOGFILE GROUP logfile_group
     *     ADD UNDOFILE 'undo_file'
     *     [INITIAL_SIZE [=] initial_size]
     *     [UNDO_BUFFER_SIZE [=] undo_buffer_size]
     *     [REDO_BUFFER_SIZE [=] redo_buffer_size]
     *     [NODEGROUP [=] nodegroup_id]
     *     [WAIT]
     *     [COMMENT [=] comment_text]
     *     [ENGINE [=] engine_name]
     */
    public function parseCreateLogfileGroup(TokenList $tokenList): CreateLogfileGroupCommand
    {
        $tokenList->expectKeywords(Keyword::CREATE, Keyword::LOGFILE, Keyword::GROUP);
        $name = $tokenList->expectName();
        $tokenList->expectKeywords(Keyword::ADD, Keyword::UNDOFILE);
        $undoFile = $tokenList->expectString();

        $initialSize = $undoBufferSize = $redoBufferSize = $nodeGroup = $comment = null;
        if ($tokenList->hasKeyword(Keyword::INITIAL_SIZE)) {
            $tokenList->passSymbol('=');
            $initialSize = $tokenList->expectSize();
        }
        if ($tokenList->hasKeyword(Keyword::UNDO_BUFFER_SIZE)) {
            $tokenList->passSymbol('=');
            $undoBufferSize = $tokenList->expectSize();
        }
        if ($tokenList->hasKeyword(Keyword::REDO_BUFFER_SIZE)) {
            $tokenList->passSymbol('=');
            $redoBufferSize = $tokenList->expectSize();
        }
        if ($tokenList->hasKeyword(Keyword::NODEGROUP)) {
            $tokenList->passSymbol('=');
            $nodeGroup = (int) $tokenList->expectInt();
        }
        $wait = $tokenList->hasKeyword(Keyword::WAIT);
        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $tokenList->passSymbol('=');
            $comment = $tokenList->expectString();
        }
        $engine = null;
        if ($tokenList->hasKeyword(Keyword::ENGINE)) {
            $tokenList->passSymbol('=');
            $engine = $tokenList->expectStorageEngineName();
        }

        return new CreateLogfileGroupCommand($name, $engine, $undoFile, $initialSize, $undoBufferSize, $redoBufferSize, $nodeGroup, $wait, $comment);
    }

    /**
     * DROP LOGFILE GROUP logfile_group
     *     ENGINE [=] engine_name
     */
    public function parseDropLogfileGroup(TokenList $tokenList): DropLogfileGroupCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::LOGFILE, Keyword::GROUP);
        $name = $tokenList->expectName();

        $engine = null;
        if ($tokenList->hasKeyword(Keyword::ENGINE)) {
            $tokenList->passSymbol('=');
            $engine = $tokenList->expectStorageEngineName();
        }

        return new DropLogfileGroupCommand($name, $engine);
    }

}
