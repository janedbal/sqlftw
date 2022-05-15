<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Show\ShowBinaryLogsCommand;
use SqlFtw\Sql\Dal\Show\ShowBinlogEventsCommand;
use SqlFtw\Sql\Dal\Show\ShowCharacterSetCommand;
use SqlFtw\Sql\Dal\Show\ShowCollationCommand;
use SqlFtw\Sql\Dal\Show\ShowColumnsCommand;
use SqlFtw\Sql\Dal\Show\ShowCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateEventCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateFunctionCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateProcedureCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateSchemaCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateTableCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateTriggerCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateUserCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateViewCommand;
use SqlFtw\Sql\Dal\Show\ShowEngineCommand;
use SqlFtw\Sql\Dal\Show\ShowEngineOption;
use SqlFtw\Sql\Dal\Show\ShowEnginesCommand;
use SqlFtw\Sql\Dal\Show\ShowErrorsCommand;
use SqlFtw\Sql\Dal\Show\ShowEventsCommand;
use SqlFtw\Sql\Dal\Show\ShowFunctionCodeCommand;
use SqlFtw\Sql\Dal\Show\ShowFunctionStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowGrantsCommand;
use SqlFtw\Sql\Dal\Show\ShowIndexesCommand;
use SqlFtw\Sql\Dal\Show\ShowMasterStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowOpenTablesCommand;
use SqlFtw\Sql\Dal\Show\ShowPluginsCommand;
use SqlFtw\Sql\Dal\Show\ShowPrivilegesCommand;
use SqlFtw\Sql\Dal\Show\ShowProcedureCodeCommand;
use SqlFtw\Sql\Dal\Show\ShowProcedureStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowProcessListCommand;
use SqlFtw\Sql\Dal\Show\ShowProfileCommand;
use SqlFtw\Sql\Dal\Show\ShowProfilesCommand;
use SqlFtw\Sql\Dal\Show\ShowProfileType;
use SqlFtw\Sql\Dal\Show\ShowRelaylogEventsCommand;
use SqlFtw\Sql\Dal\Show\ShowSchemasCommand;
use SqlFtw\Sql\Dal\Show\ShowSlaveHostsCommand;
use SqlFtw\Sql\Dal\Show\ShowSlaveStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowTablesCommand;
use SqlFtw\Sql\Dal\Show\ShowTableStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowTriggersCommand;
use SqlFtw\Sql\Dal\Show\ShowVariablesCommand;
use SqlFtw\Sql\Dal\Show\ShowWarningsCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\Scope;

class ShowCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    public function parseShow(TokenList $tokenList): ShowCommand
    {
        $tokenList->expectKeyword(Keyword::SHOW);
        $position = $tokenList->getPosition();

        // COUNT is not a keyword in 8.0
        // todo: case sensitive?
        if ($tokenList->hasNameOrKeyword(Keyword::COUNT)) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $tokenList->expectOperator(Operator::MULTIPLY);
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            $third = $tokenList->expectAnyKeyword(Keyword::ERRORS, Keyword::WARNINGS);
            if ($third === Keyword::ERRORS) {
                // SHOW COUNT(*) ERRORS
                return ShowErrorsCommand::createCount();
            } else {
                // SHOW COUNT(*) WARNINGS
                return ShowWarningsCommand::createCount();
            }
        }

        $second = $tokenList->expectAnyKeyword(
            Keyword::BINARY, Keyword::BINLOG, Keyword::CHARACTER, Keyword::COLLATION, Keyword::COLUMNS,
            Keyword::CREATE, Keyword::DATABASES, Keyword::ENGINE, Keyword::STORAGE, Keyword::ENGINES, Keyword::ERRORS,
            Keyword::EVENTS, Keyword::FIELDS, Keyword::FULL, Keyword::FUNCTION, Keyword::GLOBAL, Keyword::GRANTS,
            Keyword::INDEX, Keyword::INDEXES, Keyword::KEYS, Keyword::MASTER, Keyword::OPEN, Keyword::PLUGINS,
            Keyword::PRIVILEGES, Keyword::PROCEDURE, Keyword::PROFILE, Keyword::PROCESSLIST, Keyword::PROFILES,
            Keyword::RELAYLOG, Keyword::SCHEMAS, Keyword::SESSION, Keyword::SLAVE, Keyword::STATUS, Keyword::TABLE,
            Keyword::TABLES, Keyword::TRIGGERS, Keyword::VARIABLES, Keyword::WARNINGS
        );
        switch ($second) {
            case Keyword::BINARY:
                // SHOW {BINARY | MASTER} LOGS
                $tokenList->expectKeyword(Keyword::LOGS);

                return new ShowBinaryLogsCommand();
            case Keyword::BINLOG:
                // SHOW BINLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
                return $this->parseShowBinlogEvents($tokenList);
            case Keyword::CHARACTER:
                // SHOW CHARACTER SET [LIKE 'pattern' | WHERE expr]
                return $this->parseShowCharacterSet($tokenList);
            case Keyword::COLLATION:
                // SHOW COLLATION [LIKE 'pattern' | WHERE expr]
                return $this->parseShowCollation($tokenList);
            case Keyword::COLUMNS:
            case Keyword::FIELDS:
            case Keyword::EXTENDED:
                // SHOW [EXTENDED] [FULL] {COLUMNS | FIELDS}
                return $this->parseShowColumns($tokenList->resetPosition($position));
            case Keyword::CREATE:
                // SHOW CREATE ...
                return $this->parseShowCreate($tokenList);
            case Keyword::DATABASES:
            case Keyword::SCHEMAS:
                // SHOW {DATABASES | SCHEMAS} [LIKE 'pattern' | WHERE expr]
                return $this->parseShowSchemas($tokenList);
            case Keyword::ENGINE:
                // SHOW ENGINE engine_name {STATUS | MUTEX}
                return $this->parseShowEngine($tokenList);
            case Keyword::FULL:
                $third = $tokenList->expectAnyKeyword(Keyword::COLUMNS, Keyword::FIELDS, Keyword::PROCESSLIST, Keyword::TABLES);
                if ($third === Keyword::PROCESSLIST) {
                    // SHOW [FULL] PROCESSLIST
                    return $this->parseShowProcessList($tokenList->resetPosition($position));
                } elseif ($third === Keyword::TABLES) {
                    // SHOW [FULL] TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                    return $this->parseShowTables($tokenList->resetPosition($position));
                } else {
                    return $this->parseShowColumns($tokenList->resetPosition($position));
                }
            case Keyword::STORAGE:
            case Keyword::ENGINES:
                // SHOW [STORAGE] ENGINES
                if ($second === Keyword::STORAGE) {
                    $tokenList->expectKeyword(Keyword::ENGINES);
                }

                return new ShowEnginesCommand();
            case Keyword::ERRORS:
                // SHOW ERRORS [LIMIT [offset,] row_count]
                return $this->parseShowErrors($tokenList);
            case Keyword::EVENTS:
                // SHOW EVENTS [{FROM | IN} schema_name] [LIKE 'pattern' | WHERE expr]
                return $this->parseShowEvents($tokenList);
            case Keyword::FUNCTION:
                $third = $tokenList->expectAnyKeyword(Keyword::CODE, Keyword::STATUS);
                if ($third === Keyword::CODE) {
                    // SHOW FUNCTION CODE func_name
                    return new ShowFunctionCodeCommand(new QualifiedName(...$tokenList->expectQualifiedName()));
                } else {
                    // SHOW FUNCTION STATUS [LIKE 'pattern' | WHERE expr]
                    return $this->parseShowFunctionStatus($tokenList);
                }
            case Keyword::GLOBAL:
            case Keyword::SESSION:
                $third = $tokenList->expectAnyKeyword(Keyword::STATUS, Keyword::VARIABLES);
                if ($third === Keyword::STATUS) {
                    // SHOW [GLOBAL | SESSION] STATUS [LIKE 'pattern' | WHERE expr]
                    return $this->parseShowStatus($tokenList->resetPosition($position));
                } else {
                    return $this->parseShowVariables($tokenList->resetPosition($position));
                }
            case Keyword::GRANTS:
                // SHOW GRANTS [FOR user_or_role [USING role [, role] ...]]
                return $this->parseShowGrants($tokenList);
            case Keyword::INDEX:
            case Keyword::INDEXES:
            case Keyword::KEYS:
                // SHOW {INDEX | INDEXES | KEYS} {FROM | IN} tbl_name [{FROM | IN} db_name] [WHERE expr]
                return $this->parseShowIndexes($tokenList);
            case Keyword::MASTER:
                $third = $tokenList->expectAnyKeyword(Keyword::STATUS, Keyword::LOGS);
                if ($third === Keyword::STATUS) {
                    // SHOW MASTER STATUS
                    return new ShowMasterStatusCommand();
                } else {
                    // SHOW {BINARY | MASTER} LOGS
                    return new ShowBinaryLogsCommand();
                }
            case Keyword::OPEN:
                // SHOW OPEN TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                return $this->parseShowOpenTables($tokenList);
            case Keyword::PLUGINS:
                // SHOW PLUGINS
                return new ShowPluginsCommand();
            case Keyword::PRIVILEGES:
                // SHOW PRIVILEGES
                return new ShowPrivilegesCommand();
            case Keyword::PROCEDURE:
                $third = $tokenList->expectAnyKeyword(Keyword::CODE, Keyword::STATUS);
                if ($third === Keyword::CODE) {
                    // SHOW PROCEDURE CODE proc_name
                    return new ShowProcedureCodeCommand(new QualifiedName(...$tokenList->expectQualifiedName()));
                } else {
                    // SHOW PROCEDURE STATUS [LIKE 'pattern' | WHERE expr]
                    return $this->parseShowProcedureStatus($tokenList);
                }
            case Keyword::PROCESSLIST:
                // SHOW [FULL] PROCESSLIST
                return $this->parseShowProcessList($tokenList->resetPosition($position));
            case Keyword::PROFILE:
                // SHOW PROFILE [type [, type] ... ] [FOR QUERY n] [LIMIT row_count [OFFSET offset]]
                return $this->parseShowProfile($tokenList);
            case Keyword::PROFILES:
                // SHOW PROFILES
                return new ShowProfilesCommand();
            case Keyword::RELAYLOG:
                // SHOW RELAYLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
                return $this->parseShowRelaylogEvents($tokenList);
            case Keyword::SLAVE:
                $third = $tokenList->expectAnyKeyword(Keyword::HOSTS, Keyword::STATUS);
                if ($third === Keyword::HOSTS) {
                    // SHOW SLAVE HOSTS
                    return new ShowSlaveHostsCommand();
                } else {
                    // SHOW SLAVE STATUS [FOR CHANNEL channel]
                    return $this->parseShowSlaveStatus($tokenList);
                }
            case Keyword::STATUS:
                // SHOW [GLOBAL | SESSION] STATUS [LIKE 'pattern' | WHERE expr]
                return $this->parseShowStatus($tokenList->resetPosition($position));
            case Keyword::TABLE:
                // SHOW TABLE STATUS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                return $this->parseShowTableStatus($tokenList);
            case Keyword::TABLES:
                // SHOW [FULL] TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                return $this->parseShowTables($tokenList->resetPosition($position));
            case Keyword::TRIGGERS:
                // SHOW TRIGGERS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                return $this->parseShowTriggers($tokenList);
            case Keyword::VARIABLES:
                // SHOW [GLOBAL | SESSION] VARIABLES [LIKE 'pattern' | WHERE expr]
                return $this->parseShowVariables($tokenList->resetPosition($position));
            case Keyword::WARNINGS:
                // SHOW WARNINGS [LIMIT [offset,] row_count]
                return $this->parseShowWarnings($tokenList);
            default:
                throw new ShouldNotHappenException('');
        }
    }

    /**
     * SHOW BINLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
     */
    private function parseShowBinlogEvents(TokenList $tokenList): ShowBinlogEventsCommand
    {
        $tokenList->expectKeyword(Keyword::EVENTS);
        $logName = $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::IN)) {
            $logName = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::FROM)) {
            $offset = $tokenList->expectInt();
        }
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $tokenList->expectInt();
            if ($tokenList->hasComma()) {
                $offset = $limit;
                $limit = $tokenList->getInt();
            }
        }

        return new ShowBinlogEventsCommand($logName, $limit, $offset);
    }

    /**
     * SHOW CHARACTER SET [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowCharacterSet(TokenList $tokenList): ShowCharacterSetCommand
    {
        $tokenList->expectKeyword(Keyword::SET);
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowCharacterSetCommand($like, $where);
    }

    /**
     * SHOW COLLATION [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowCollation(TokenList $tokenList): ShowCollationCommand
    {
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowCollationCommand($like, $where);
    }

    /**
     * SHOW [EXTENDED] [FULL] {COLUMNS | FIELDS}
     *     {FROM | IN} tbl_name
     *     [{FROM | IN} db_name]
     *     [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowColumns(TokenList $tokenList): ShowColumnsCommand
    {
        $extended = $tokenList->hasKeyword(Keyword::EXTENDED);
        $full = $tokenList->hasKeyword(Keyword::FULL);
        $tokenList->expectAnyKeyword(Keyword::COLUMNS, Keyword::FIELDS);

        $tokenList->expectAnyKeyword(Keyword::FROM, Keyword::IN);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());
        if ($table->getSchema() === null && $tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
            $schema = $tokenList->expectName();
            $table = new QualifiedName($table->getName(), $schema);
        }

        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowColumnsCommand($table, $like, $where, $full, $extended);
    }

    private function parseShowCreate(TokenList $tokenList): ShowCommand
    {
        $third = $tokenList->expectAnyKeyword(
            Keyword::DATABASE,
            Keyword::SCHEMA,
            Keyword::EVENT,
            Keyword::FUNCTION,
            Keyword::PROCEDURE,
            Keyword::TABLE,
            Keyword::TRIGGER,
            Keyword::USER,
            Keyword::VIEW
        );
        switch ($third) {
            case Keyword::DATABASE:
            case Keyword::SCHEMA:
                // SHOW CREATE {DATABASE | SCHEMA} db_name
                $name = $tokenList->expectName();

                return new ShowCreateSchemaCommand($name);
            case Keyword::EVENT:
                // SHOW CREATE EVENT event_name
                $name = new QualifiedName(...$tokenList->expectQualifiedName());

                return new ShowCreateEventCommand($name);
            case Keyword::FUNCTION:
                // SHOW CREATE FUNCTION func_name
                $name = new QualifiedName(...$tokenList->expectQualifiedName());

                return new ShowCreateFunctionCommand($name);
            case Keyword::PROCEDURE:
                // SHOW CREATE PROCEDURE proc_name
                $name = new QualifiedName(...$tokenList->expectQualifiedName());

                return new ShowCreateProcedureCommand($name);
            case Keyword::TABLE:
                // SHOW CREATE TABLE tbl_name
                $name = new QualifiedName(...$tokenList->expectQualifiedName());

                return new ShowCreateTableCommand($name);
            case Keyword::TRIGGER:
                // SHOW CREATE TRIGGER trigger_name
                $name = new QualifiedName(...$tokenList->expectQualifiedName());

                return new ShowCreateTriggerCommand($name);
            case Keyword::USER:
                // SHOW CREATE USER user
                $name = $tokenList->expectName();

                return new ShowCreateUserCommand($name);
            case Keyword::VIEW:
                // SHOW CREATE VIEW view_name
                $name = new QualifiedName(...$tokenList->expectQualifiedName());

                return new ShowCreateViewCommand($name);
            default:
                throw new ShouldNotHappenException('');
        }
    }

    /**
     * SHOW {DATABASES | SCHEMAS} [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowSchemas(TokenList $tokenList): ShowSchemasCommand
    {
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowSchemasCommand($like, $where);
    }

    /**
     * SHOW ENGINE engine_name {STATUS | MUTEX}
     */
    private function parseShowEngine(TokenList $tokenList): ShowEngineCommand
    {
        $engine = $tokenList->expectName();
        /** @var ShowEngineOption $what */
        $what = $tokenList->expectKeywordEnum(ShowEngineOption::class);

        return new ShowEngineCommand($engine, $what);
    }

    /**
     * SHOW ERRORS [LIMIT [offset,] row_count]
     */
    private function parseShowErrors(TokenList $tokenList): ShowErrorsCommand
    {
        $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $tokenList->expectInt();
            if ($tokenList->hasComma()) {
                $offset = $limit;
                $limit = $tokenList->expectInt();
            }
        }

        return new ShowErrorsCommand($limit, $offset);
    }

    /**
     * SHOW EVENTS [{FROM | IN} schema_name] [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowEvents(TokenList $tokenList): ShowEventsCommand
    {
        $from = $like = $where = null;
        if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
            $from = $tokenList->expectName();
        }
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowEventsCommand($from, $like, $where);
    }

    /**
     * SHOW FUNCTION STATUS [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowFunctionStatus(TokenList $tokenList): ShowFunctionStatusCommand
    {
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowFunctionStatusCommand($like, $where);
    }

    /**
     * SHOW GRANTS [FOR user_or_role [USING role [, role] ...]]
     */
    private function parseShowGrants(TokenList $tokenList): ShowGrantsCommand
    {
        $forUser = null;
        $usingRoles = [];
        if ($tokenList->hasKeyword(Keyword::FOR)) {
            $forUser = $this->expressionParser->parseUserExpression($tokenList);
            if ($tokenList->hasKeyword(Keyword::USING)) {
                do {
                    $usingRoles[] = $tokenList->expectUserName();
                } while ($tokenList->hasComma());
            }
        }

        return new ShowGrantsCommand($forUser, $usingRoles !== [] ? $usingRoles : null);
    }

    /**
     * SHOW {INDEX | INDEXES | KEYS} {FROM | IN} tbl_name [{FROM | IN} db_name] [WHERE expr]
     */
    private function parseShowIndexes(TokenList $tokenList): ShowIndexesCommand
    {
        $tokenList->expectAnyKeyword(Keyword::FROM, Keyword::IN);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());
        if ($table->getSchema() === null && $tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
            $schema = $tokenList->expectName();
            $table = new QualifiedName($table->getName(), $schema);
        }
        $where = null;
        if ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowIndexesCommand($table, $where);
    }

    /**
     * SHOW OPEN TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowOpenTables(TokenList $tokenList): ShowOpenTablesCommand
    {
        $tokenList->expectKeyword(Keyword::TABLES);
        $from = $like = $where = null;
        if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
            $from = $tokenList->expectName();
        }
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowOpenTablesCommand($from, $like, $where);
    }

    /**
     * SHOW PROCEDURE STATUS [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowProcedureStatus(TokenList $tokenList): ShowProcedureStatusCommand
    {
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowProcedureStatusCommand($like, $where);
    }

    /**
     * SHOW [FULL] PROCESSLIST
     */
    private function parseShowProcessList(TokenList $tokenList): ShowProcessListCommand
    {
        $full = $tokenList->hasKeyword(Keyword::FULL);
        $tokenList->expectKeyword(Keyword::PROCESSLIST);

        return new ShowProcessListCommand($full);
    }

    /**
     * SHOW PROFILE [type [, type] ... ] [FOR QUERY n] [LIMIT row_count [OFFSET offset]]
     *
     * type:
     *     ALL | BLOCK IO | CONTEXT SWITCHES | CPU | IPC | MEMORY | PAGE FAULTS | SOURCE | SWAPS
     */
    private function parseShowProfile(TokenList $tokenList): ShowProfileCommand
    {
        $types = [];
        $type = $tokenList->getMultiKeywordsEnum(ShowProfileType::class);
        if ($type !== null) {
            $types[] = $type;
            while ($tokenList->hasComma()) {
                $types[] = $tokenList->expectMultiKeywordsEnum(ShowProfileType::class);
            }
        }
        $query = $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::FOR)) {
            $tokenList->expectKeyword(Keyword::QUERY);
            $query = $tokenList->expectInt();
        }
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $tokenList->expectInt();
            if ($tokenList->hasKeyword(Keyword::OFFSET)) {
                $offset = $tokenList->expectInt();
            }
        }

        return new ShowProfileCommand($types, $query, $limit, $offset);
    }

    /**
     * SHOW RELAYLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
     */
    private function parseShowRelaylogEvents(TokenList $tokenList): ShowRelaylogEventsCommand
    {
        $tokenList->expectKeyword(Keyword::EVENTS);
        $logName = $from = $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::IN)) {
            $logName = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::FROM)) {
            $from = $tokenList->expectInt();
        }
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $tokenList->expectInt();
            if ($tokenList->hasComma()) {
                $offset = $limit;
                $limit = $tokenList->expectInt();
            }
        }

        return new ShowRelaylogEventsCommand($logName, $from, $limit, $offset);
    }

    /**
     * SHOW SLAVE STATUS [FOR CHANNEL channel]
     */
    private function parseShowSlaveStatus(TokenList $tokenList): ShowSlaveStatusCommand
    {
        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectName();
        }

        return new ShowSlaveStatusCommand($channel);
    }

    /**
     * SHOW [GLOBAL | SESSION] STATUS [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowStatus(TokenList $tokenList): ShowStatusCommand
    {
        $scope = $tokenList->getAnyKeyword(Keyword::GLOBAL, Keyword::SESSION);
        $tokenList->expectKeyword(Keyword::STATUS);
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowStatusCommand($scope !== null ? Scope::get($scope) : null, $like, $where);
    }

    /**
     * SHOW TABLE STATUS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowTableStatus(TokenList $tokenList): ShowTableStatusCommand
    {
        $tokenList->expectKeyword(Keyword::STATUS);
        $from = $like = $where = null;
        if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
            $from = $tokenList->expectName();
        }
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowTableStatusCommand($from, $like, $where);
    }

    /**
     * SHOW [FULL] TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowTables(TokenList $tokenList): ShowTablesCommand
    {
        $full = $tokenList->hasKeyword(Keyword::FULL);
        $tokenList->expectKeyword(Keyword::TABLES);
        $schema = null;
        if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
            $schema = $tokenList->expectName();
        }
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowTablesCommand($schema, $full, $like, $where);
    }

    /**
     * SHOW TRIGGERS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowTriggers(TokenList $tokenList): ShowTriggersCommand
    {
        $from = $like = $where = null;
        if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
            $from = $tokenList->expectName();
        }
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowTriggersCommand($from, $like, $where);
    }

    /**
     * SHOW WARNINGS [LIMIT [offset,] row_count]
     */
    private function parseShowWarnings(TokenList $tokenList): ShowWarningsCommand
    {
        $limit = $offset = null;
        if ($tokenList->hasKeyword(Keyword::LIMIT)) {
            $limit = $tokenList->expectInt();
            if ($tokenList->hasComma()) {
                $offset = $limit;
                $limit = $tokenList->expectInt();
            }
        }

        return new ShowWarningsCommand($limit, $offset);
    }

    /**
     * SHOW [GLOBAL | SESSION] VARIABLES [LIKE 'pattern' | WHERE expr]
     */
    private function parseShowVariables(TokenList $tokenList): ShowVariablesCommand
    {
        $scope = $tokenList->getAnyKeyword(Keyword::GLOBAL, Keyword::SESSION);
        $tokenList->expectKeyword(Keyword::VARIABLES);
        $like = $where = null;
        if ($tokenList->hasKeyword(Keyword::LIKE)) {
            $like = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
            $where = $this->expressionParser->parseExpression($tokenList);
        }

        return new ShowVariablesCommand($scope !== null ? Scope::get($scope) : null, $like, $where);
    }

}
