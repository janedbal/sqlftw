<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Features;

use SqlFtw\Sql\Keyword;

class FeaturesMysql80 extends \SqlFtw\Platform\Features\PlatformFeatures
{

    public const RESERVED_WORDS = [
        Keyword::ACCOUNT,
        Keyword::AGAINST,
        Keyword::AGGREGATE,
        Keyword::ASCII,
        Keyword::AT,
        Keyword::AVG,
        Keyword::AVG_ROW_LENGTH,
        Keyword::BEGIN,
        Keyword::BINLOG,
        Keyword::BLOCK,
        Keyword::CATALOG_NAME,
        Keyword::CLASS_ORIGIN,
        Keyword::CLIENT,
        Keyword::CLOSE,
        Keyword::COLLATION,
        Keyword::COMMENT,
        Keyword::COMMITTED,
        Keyword::COMPONENT,
        Keyword::COMPRESSED,
        Keyword::COMPRESSION,
        Keyword::CONNECTION,
        Keyword::CREATE,
        Keyword::CUBE,
        Keyword::CURSOR_NAME,
        Keyword::DISABLE,
        Keyword::DO,
        Keyword::END,
        Keyword::ENDS,
        Keyword::ENGINES,
        Keyword::ENUM,
        Keyword::ERROR,
        Keyword::ESCAPE,
        Keyword::EVENT,
        Keyword::EVENTS,
        Keyword::EVERY,
        Keyword::EXECUTE,
        Keyword::EXPIRE,
        Keyword::EXPORT,
        Keyword::FIELDS,
        Keyword::FILE,
        Keyword::FILE_BLOCK_SIZE,
        Keyword::FILTER,
        Keyword::FIXED,
        Keyword::FLUSH,
        Keyword::FOLLOWS,
        Keyword::FORMAT,
        Keyword::GENERAL,
        Keyword::GEOMETRY,
        Keyword::GET_FORMAT,
        Keyword::GLOBAL,
        Keyword::GRANTS,
        Keyword::HOSTS,
        Keyword::HOUR,
        Keyword::CHAIN,
        Keyword::CHANNEL,
        Keyword::CHARSET,
        Keyword::CHECKSUM,
        Keyword::INDEXES,
        Keyword::INITIAL_SIZE,
        Keyword::INSERT_METHOD,
        Keyword::INSTANCE,
        Keyword::LAST,
        Keyword::LINESTRING,
        Keyword::LOCKS,
        Keyword::LOGFILE,
        Keyword::MASTER_AUTO_POSITION,
        Keyword::MASTER_HOST,
        Keyword::MASTER_PASSWORD,
        Keyword::MASTER_SSL_CERT,
        Keyword::MASTER_SSL_CIPHER,
        Keyword::MASTER_SSL_CRL,
        Keyword::MASTER_TLS_VERSION,
        Keyword::MASTER_USER,
        Keyword::MAX_CONNECTIONS_PER_HOUR,
        Keyword::MAX_SIZE,
        Keyword::MAX_UPDATES_PER_HOUR,
        Keyword::MAX_USER_CONNECTIONS,
        Keyword::MEMORY,
        Keyword::MICROSECOND,
        Keyword::MINUTE,
        Keyword::MODIFY,
        Keyword::MULTILINESTRING,
        Keyword::MUTEX,
        Keyword::NAME,
        Keyword::NDBCLUSTER,
        Keyword::NEVER,
        Keyword::NEW,
        Keyword::NCHAR,
        Keyword::NODEGROUP,
        Keyword::NONBLOCKING,
        Keyword::NUMBER,
        Keyword::OLD_PASSWORD,
        Keyword::ONE,
        Keyword::OPTIONS,
        Keyword::OWNER,
        Keyword::PAGE,
        Keyword::PARSER,
        Keyword::PARTIAL,
        Keyword::PARTITIONING,
        Keyword::PARTITIONS,
        Keyword::PLUGIN_DIR,
        Keyword::PLUGINS,
        Keyword::POINT,
        Keyword::POLYGON,
        Keyword::PORT,
        Keyword::PRECEDES,
        Keyword::PREPARE,
        Keyword::PRESERVE,
        Keyword::PREV,
        Keyword::PROFILES,
        Keyword::PROXY,
        Keyword::QUICK,
        Keyword::RECOVER,
        Keyword::REDO_BUFFER_SIZE,
        Keyword::REDUNDANT,
        Keyword::RELAY_LOG_FILE,
        Keyword::RELAY_LOG_POS,
        Keyword::RELOAD,
        Keyword::REMOVE,
        Keyword::REPAIR,
        Keyword::REPLICATE_DO_TABLE,
        Keyword::REPLICATE_IGNORE_DB,
        Keyword::REPLICATE_IGNORE_TABLE,
        Keyword::REPLICATE_REWRITE_DB,
        Keyword::REPLICATION,
        Keyword::RESTORE,
        Keyword::RESUME,
        Keyword::RETURNED_SQLSTATE,
        Keyword::ROTATE,
        Keyword::ROUTINE,
        Keyword::ROW,
        Keyword::ROW_COUNT,
        Keyword::ROW_FORMAT,
        Keyword::SAVEPOINT,
        Keyword::SOUNDS,
        Keyword::SQL_AFTER_MTS_GAPS,
        Keyword::SQL_TSI_HOUR,
        Keyword::SQL_TSI_MONTH,
        Keyword::SQL_TSI_QUARTER,
        Keyword::SQL_TSI_SECOND,
        Keyword::STARTS,
        Keyword::STATUS,
        Keyword::STOP,
        Keyword::STORAGE,
        Keyword::SUBCLASS_ORIGIN,
        Keyword::TABLESPACE,
        Keyword::TRANSACTION,
        Keyword::TYPE,
        Keyword::UNCOMMITTED,
        Keyword::UNDO_BUFFER_SIZE,
        Keyword::UNICODE,
        Keyword::UNKNOWN,
        Keyword::USER,
        Keyword::USER_RESOURCES,
        Keyword::VALIDATION,
        Keyword::VALUE,
        Keyword::VIEW,
        Keyword::WAIT,
        Keyword::WARNINGS,
        Keyword::WEEK,
        Keyword::WEIGHT_STRING,
        Keyword::XID,
    ];

    public const NON_RESERVED_WORDS = [
        Keyword::ACTION,
        Keyword::AFTER,
        Keyword::ALGORITHM,
        Keyword::ALWAYS,
        Keyword::ANALYSE,
        Keyword::ANY,
        Keyword::AUTO_INCREMENT,
        Keyword::AUTOEXTEND_SIZE,
        Keyword::BACKUP,
        Keyword::BIT,
        Keyword::BOOL,
        Keyword::BOOLEAN,
        Keyword::BTREE,
        Keyword::BYTE,
        Keyword::CACHE,
        Keyword::CASCADED,
        Keyword::CIPHER,
        Keyword::COALESCE,
        Keyword::CODE,
        Keyword::COLUMN_FORMAT,
        Keyword::COLUMN_NAME,
        Keyword::COLUMNS,
        Keyword::COMMIT,
        Keyword::COMPACT,
        Keyword::COMPLETION,
        Keyword::CONCURRENT,
        Keyword::CONSISTENT,
        Keyword::CONSTRAINT_CATALOG,
        Keyword::CONSTRAINT_NAME,
        Keyword::CONSTRAINT_SCHEMA,
        Keyword::CONTAINS,
        Keyword::CONTEXT,
        Keyword::CPU,
        Keyword::CURRENT,
        Keyword::DATA,
        Keyword::DATAFILE,
        Keyword::DATE,
        Keyword::DATETIME,
        Keyword::DAY,
        Keyword::DEALLOCATE,
        Keyword::DEFAULT_AUTH,
        Keyword::DEFINER,
        Keyword::DELAY_KEY_WRITE,
        Keyword::DES_KEY_FILE,
        Keyword::DIAGNOSTICS,
        Keyword::DIRECTORY,
        Keyword::DISCARD,
        Keyword::DISK,
        Keyword::DUMPFILE,
        Keyword::DUPLICATE,
        Keyword::DYNAMIC,
        Keyword::ENABLE,
        Keyword::ENCRYPTION,
        Keyword::ENGINE,
        Keyword::ERRORS,
        Keyword::EXCHANGE,
        Keyword::EXPANSION,
        Keyword::EXTENDED,
        Keyword::EXTENT_SIZE,
        Keyword::FAST,
        Keyword::FAULTS,
        Keyword::FIRST,
        Keyword::FOUND,
        Keyword::FULL,
        Keyword::FUNCTION,
        Keyword::GEOMETRYCOLLECTION,
        Keyword::GROUP_REPLICATION,
        Keyword::HANDLER,
        Keyword::HASH,
        Keyword::HELP,
        Keyword::HOST,
        Keyword::CHANGED,
        Keyword::IDENTIFIED,
        Keyword::IGNORE_SERVER_IDS,
        Keyword::IMPORT,
        Keyword::INSTALL,
        Keyword::INVOKER,
        Keyword::IO,
        Keyword::IO_THREAD,
        Keyword::IPC,
        Keyword::ISOLATION,
        Keyword::ISSUER,
        Keyword::JSON,
        Keyword::KEY_BLOCK_SIZE,
        Keyword::LANGUAGE,
        Keyword::LEAVES,
        Keyword::LESS,
        Keyword::LEVEL,
        Keyword::LIST,
        Keyword::LOCAL,
        Keyword::LOGS,
        Keyword::MASTER,
        Keyword::MASTER_CONNECT_RETRY,
        Keyword::MASTER_DELAY,
        Keyword::MASTER_HEARTBEAT_PERIOD,
        Keyword::MASTER_LOG_FILE,
        Keyword::MASTER_LOG_POS,
        Keyword::MASTER_PORT,
        Keyword::MASTER_RETRY_COUNT,
        Keyword::MASTER_SERVER_ID,
        Keyword::MASTER_SSL,
        Keyword::MASTER_SSL_CA,
        Keyword::MASTER_SSL_CAPATH,
        Keyword::MASTER_SSL_CRLPATH,
        Keyword::MASTER_SSL_KEY,
        Keyword::MAX_QUERIES_PER_HOUR,
        Keyword::MAX_ROWS,
        Keyword::MAX_STATEMENT_TIME,
        Keyword::MEDIUM,
        Keyword::MERGE,
        Keyword::MESSAGE_TEXT,
        Keyword::MIGRATE,
        Keyword::MIN_ROWS,
        Keyword::MODE,
        Keyword::MONTH,
        Keyword::MULTIPOINT,
        Keyword::MULTIPOLYGON,
        Keyword::MYSQL_ERRNO,
        Keyword::NAMES,
        Keyword::NATIONAL,
        Keyword::NDB,
        Keyword::NEXT,
        Keyword::NO,
        Keyword::NO_WAIT,
        Keyword::NONE,
        Keyword::NVARCHAR,
        Keyword::OFFSET,
        Keyword::ONLY,
        Keyword::OPEN,
        Keyword::PACK_KEYS,
        Keyword::PARSE_GCOL_EXPR,
        Keyword::PASSWORD,
        Keyword::PHASE,
        Keyword::PLUGIN,
        Keyword::PRIVILEGES,
        Keyword::PROCESSLIST,
        Keyword::PROFILE,
        Keyword::QUARTER,
        Keyword::QUERY,
        Keyword::READ_ONLY,
        Keyword::REBUILD,
        Keyword::REDOFILE,
        Keyword::RELAY,
        Keyword::RELAY_THREAD,
        Keyword::RELAYLOG,
        Keyword::REORGANIZE,
        Keyword::REPEATABLE,
        Keyword::REPLICATE_DO_DB,
        Keyword::REPLICATE_WILD_DO_TABLE,
        Keyword::REPLICATE_WILD_IGNORE_TABLE,
        Keyword::RESET,
        Keyword::RETURNS,
        Keyword::REVERSE,
        Keyword::ROLLBACK,
        Keyword::ROLLUP,
        Keyword::ROWS,
        Keyword::RTREE,
        Keyword::SECOND,
        Keyword::SECURITY,
        Keyword::SERIAL,
        Keyword::SERIALIZABLE,
        Keyword::SERVER,
        Keyword::SESSION,
        Keyword::SHARE,
        Keyword::SHUTDOWN,
        Keyword::SCHEDULE,
        Keyword::SCHEMA_NAME,
        Keyword::SIGNED,
        Keyword::SIMPLE,
        Keyword::SLAVE,
        Keyword::SLOW,
        Keyword::SNAPSHOT,
        Keyword::SOCKET,
        Keyword::SOME,
        Keyword::SONAME,
        Keyword::SOURCE,
        Keyword::SQL_AFTER_GTIDS,
        Keyword::SQL_BEFORE_GTIDS,
        Keyword::SQL_BUFFER_RESULT,
        Keyword::SQL_CACHE,
        Keyword::SQL_NO_CACHE,
        Keyword::SQL_THREAD,
        Keyword::SQL_TSI_DAY,
        Keyword::SQL_TSI_MINUTE,
        Keyword::SQL_TSI_WEEK,
        Keyword::SQL_TSI_YEAR,
        Keyword::STACKED,
        Keyword::START,
        Keyword::STATS_AUTO_RECALC,
        Keyword::STATS_PERSISTENT,
        Keyword::STATS_SAMPLE_PAGES,
        Keyword::STRING,
        Keyword::SUBJECT,
        Keyword::SUBPARTITION,
        Keyword::SUBPARTITIONS,
        Keyword::SUPER,
        Keyword::SUSPEND,
        Keyword::SWAPS,
        Keyword::SWITCHES,
        Keyword::TABLE_CHECKSUM,
        Keyword::TABLE_NAME,
        Keyword::TABLES,
        Keyword::TEMPORARY,
        Keyword::TEMPTABLE,
        Keyword::TEXT,
        Keyword::THAN,
        Keyword::TIME,
        Keyword::TIMESTAMP,
        Keyword::TIMESTAMPADD,
        Keyword::TIMESTAMPDIFF,
        Keyword::TRIGGERS,
        Keyword::TRUNCATE,
        Keyword::TYPES,
        Keyword::UNDEFINED,
        Keyword::UNDOFILE,
        Keyword::UNINSTALL,
        Keyword::UNTIL,
        Keyword::UPGRADE,
        Keyword::USE_FRM,
        Keyword::VARIABLES,
        Keyword::WITHOUT,
        Keyword::WORK,
        Keyword::WRAPPER,
        Keyword::X509,
        Keyword::XA,
        Keyword::XML,
        Keyword::YEAR,
    ];

    public const OPERATOR_KEYWORDS = [
        Keyword::AND,
        Keyword::OR,
        Keyword::XOR,
        Keyword::NOT,
        Keyword::IN,
        Keyword::IS,
        Keyword::LIKE,
        Keyword::RLIKE,
        Keyword::REGEXP,
        Keyword::SOUNDS,
        Keyword::BETWEEN,
        Keyword::DIV,
        Keyword::MOD,
        Keyword::INTERVAL,
        Keyword::BINARY,
        Keyword::COLLATE,
        Keyword::CASE,
        Keyword::WHEN,
        Keyword::THAN,
        Keyword::ELSE,
    ];

    public const FUNCTIONS = [
        ///
    ];

}
