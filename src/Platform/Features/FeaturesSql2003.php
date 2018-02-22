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

class FeaturesSql2003 extends \SqlFtw\Platform\Features\PlatformFeatures
{

    public const RESERVED_WORDS = [
        Keyword::ABS,
        Keyword::ALL,
        Keyword::ALLOCATE,
        Keyword::ALTER,
        Keyword::AND,
        Keyword::ANY,
        Keyword::ARE,
        Keyword::ARRAY,
        Keyword::AS,
        Keyword::ASENSITIVE,
        Keyword::ASYMMETRIC,
        Keyword::AT,
        Keyword::ATOMIC,
        Keyword::AUTHORIZATION,
        Keyword::AVG,
        Keyword::BEGIN,
        Keyword::BETWEEN,
        Keyword::BIGINT,
        Keyword::BINARY,
        Keyword::BLOB,
        Keyword::BOOLEAN,
        Keyword::BOTH,
        Keyword::BY,
        Keyword::CALL,
        Keyword::CALLED,
        Keyword::CARDINALITY,
        Keyword::CASCADED,
        Keyword::CASE,
        Keyword::CAST,
        Keyword::CEIL,
        Keyword::CEILING,
        Keyword::CHAR,
        Keyword::CHAR_LENGTH,
        Keyword::CHARACTER,
        Keyword::CHARACTER_LENGTH,
        Keyword::CHECK,
        Keyword::CLOB,
        Keyword::CLOSE,
        Keyword::COALESCE,
        Keyword::COLLATE,
        Keyword::COLLECT,
        Keyword::COLUMN,
        Keyword::COMMIT,
        Keyword::CONDITION,
        Keyword::CONNECT,
        Keyword::CONSTRAINT,
        Keyword::CONVERT,
        Keyword::CORR,
        Keyword::CORRESPONDING,
        Keyword::COUNT,
        Keyword::COVAR_POP,
        Keyword::COVAR_SAMP,
        Keyword::CREATE,
        Keyword::CROSS,
        Keyword::CUBE,
        Keyword::CUME_DIST,
        Keyword::CURRENT,
        Keyword::CURRENT_DATE,
        Keyword::CURRENT_DEFAULT_TRANSFORM_GROUP,
        Keyword::CURRENT_PATH,
        Keyword::CURRENT_ROLE,
        Keyword::CURRENT_TIME,
        Keyword::CURRENT_TIMESTAMP,
        Keyword::CURRENT_TRANSFORM_GROUP_FOR_TYPE,
        Keyword::CURRENT_USER,
        Keyword::CURSOR,
        Keyword::CYCLE,
        Keyword::DATE,
        Keyword::DAY,
        Keyword::DEALLOCATE,
        Keyword::DEC,
        Keyword::DECIMAL,
        Keyword::DECLARE,
        Keyword::DEFAULT,
        Keyword::DELETE,
        Keyword::DENSE_RANK,
        Keyword::DEREF,
        Keyword::DESCRIBE,
        Keyword::DETERMINISTIC,
        Keyword::DISCONNECT,
        Keyword::DISTINCT,
        Keyword::DOUBLE,
        Keyword::DROP,
        Keyword::DYNAMIC,
        Keyword::EACH,
        Keyword::ELEMENT,
        Keyword::ELSE,
        Keyword::END,
        Keyword::END_EXEC,
        Keyword::ESCAPE,
        Keyword::EVERY,
        Keyword::EXCEPT,
        Keyword::EXEC,
        Keyword::EXECUTE,
        Keyword::EXISTS,
        Keyword::EXP,
        Keyword::EXTERNAL,
        Keyword::EXTRACT,
        Keyword::FALSE,
        Keyword::FETCH,
        Keyword::FILTER,
        Keyword::FLOAT,
        Keyword::FLOOR,
        Keyword::FOR,
        Keyword::FOREIGN,
        Keyword::FREE,
        Keyword::FROM,
        Keyword::FULL,
        Keyword::FUNCTION,
        Keyword::FUSION,
        Keyword::GET,
        Keyword::GLOBAL,
        Keyword::GRANT,
        Keyword::GROUP,
        Keyword::GROUPING,
        Keyword::HAVING,
        Keyword::HOLD,
        Keyword::HOUR,
        Keyword::IDENTITY,
        Keyword::IN,
        Keyword::INDICATOR,
        Keyword::INNER,
        Keyword::INOUT,
        Keyword::INSENSITIVE,
        Keyword::INSERT,
        Keyword::INT,
        Keyword::INTEGER,
        Keyword::INTERSECT,
        Keyword::INTERSECTION,
        Keyword::INTERVAL,
        Keyword::INTO,
        Keyword::IS,
        Keyword::JOIN,
        Keyword::LANGUAGE,
        Keyword::LARGE,
        Keyword::LATERAL,
        Keyword::LEADING,
        Keyword::LEFT,
        Keyword::LIKE,
        Keyword::LN,
        Keyword::LOCAL,
        Keyword::LOCALTIME,
        Keyword::LOCALTIMESTAMP,
        Keyword::LOWER,
        Keyword::MATCH,
        Keyword::MAX,
        Keyword::MEMBER,
        Keyword::MERGE,
        Keyword::METHOD,
        Keyword::MIN,
        Keyword::MINUTE,
        Keyword::MOD,
        Keyword::MODIFIES,
        Keyword::MODULE,
        Keyword::MONTH,
        Keyword::MULTISET,
        Keyword::NATIONAL,
        Keyword::NATURAL,
        Keyword::NCHAR,
        Keyword::NCLOB,
        Keyword::NEW,
        Keyword::NO,
        Keyword::NONE,
        Keyword::NORMALIZE,
        Keyword::NOT,
        Keyword::NULL,
        Keyword::NULLIF,
        Keyword::NUMERIC,
        Keyword::OCTET_LENGTH,
        Keyword::OF,
        Keyword::OLD,
        Keyword::ON,
        Keyword::ONLY,
        Keyword::OPEN,
        Keyword::OR,
        Keyword::ORDER,
        Keyword::OUT,
        Keyword::OUTER,
        Keyword::OVER,
        Keyword::OVERLAPS,
        Keyword::OVERLAY,
        Keyword::PARAMETER,
        Keyword::PARTITION,
        Keyword::PERCENT_RANK,
        Keyword::PERCENTILE_CONT,
        Keyword::PERCENTILE_DISC,
        Keyword::POSITION,
        Keyword::POWER,
        Keyword::PRECISION,
        Keyword::PREPARE,
        Keyword::PRIMARY,
        Keyword::PROCEDURE,
        Keyword::RANGE,
        Keyword::RANK,
        Keyword::READS,
        Keyword::REAL,
        Keyword::RECURSIVE,
        Keyword::REF,
        Keyword::REFERENCES,
        Keyword::REFERENCING,
        Keyword::REGR_AVGX,
        Keyword::REGR_AVGY,
        Keyword::REGR_COUNT,
        Keyword::REGR_INTERCEPT,
        Keyword::REGR_R2,
        Keyword::REGR_SLOPE,
        Keyword::REGR_SXX,
        Keyword::REGR_SXY,
        Keyword::REGR_SYY,
        Keyword::RELEASE,
        Keyword::RESULT,
        Keyword::RETURN,
        Keyword::RETURNS,
        Keyword::REVOKE,
        Keyword::RIGHT,
        Keyword::ROLLBACK,
        Keyword::ROLLUP,
        Keyword::ROW,
        Keyword::ROW_NUMBER,
        Keyword::ROWS,
        Keyword::SAVEPOINT,
        Keyword::SCOPE,
        Keyword::SCROLL,
        Keyword::SEARCH,
        Keyword::SECOND,
        Keyword::SELECT,
        Keyword::SENSITIVE,
        Keyword::SESSION_USER,
        Keyword::SET,
        Keyword::SIMILAR,
        Keyword::SMALLINT,
        Keyword::SOME,
        Keyword::SPECIFIC,
        Keyword::SPECIFICTYPE,
        Keyword::SQL,
        Keyword::SQLEXCEPTION,
        Keyword::SQLSTATE,
        Keyword::SQLWARNING,
        Keyword::SQRT,
        Keyword::START,
        Keyword::STATIC,
        Keyword::STDDEV_POP,
        Keyword::STDDEV_SAMP,
        Keyword::SUBMULTISET,
        Keyword::SUBSTRING,
        Keyword::SUM,
        Keyword::SYMMETRIC,
        Keyword::SYSTEM,
        Keyword::SYSTEM_USER,
        Keyword::TABLE,
        Keyword::TABLESAMPLE,
        Keyword::THEN,
        Keyword::TIME,
        Keyword::TIMESTAMP,
        Keyword::TIMEZONE_HOUR,
        Keyword::TIMEZONE_MINUTE,
        Keyword::TO,
        Keyword::TRAILING,
        Keyword::TRANSLATE,
        Keyword::TRANSLATION,
        Keyword::TREAT,
        Keyword::TRIGGER,
        Keyword::TRIM,
        Keyword::TRUE,
        Keyword::UESCAPE,
        Keyword::UNION,
        Keyword::UNIQUE,
        Keyword::UNKNOWN,
        Keyword::UNNEST,
        Keyword::UPDATE,
        Keyword::UPPER,
        Keyword::USER,
        Keyword::USING,
        Keyword::VALUE,
        Keyword::VALUES,
        Keyword::VAR_POP,
        Keyword::VAR_SAMP,
        Keyword::VARCHAR,
        Keyword::VARYING,
        Keyword::WHEN,
        Keyword::WHENEVER,
        Keyword::WHERE,
        Keyword::WIDTH_BUCKET,
        Keyword::WINDOW,
        Keyword::WITH,
        Keyword::WITHIN,
        Keyword::WITHOUT,
        Keyword::YEAR,
    ];

    public const NON_RESERVED_WORDS = [
        Keyword::A,
        Keyword::ABSOLUTE,
        Keyword::ACTION,
        Keyword::ADA,
        Keyword::ADD,
        Keyword::ADMIN,
        Keyword::AFTER,
        Keyword::ALWAYS,
        Keyword::ASC,
        Keyword::ASSERTION,
        Keyword::ASSIGNMENT,
        Keyword::ATTRIBUTE,
        Keyword::ATTRIBUTES,
        Keyword::BEFORE,
        Keyword::BERNOULLI,
        Keyword::BREADTH,
        Keyword::C,
        Keyword::CASCADE,
        Keyword::CATALOG,
        Keyword::CATALOG_NAME,
        Keyword::CHAIN,
        Keyword::CHARACTER_SET_CATALOG,
        Keyword::CHARACTER_SET_NAME,
        Keyword::CHARACTER_SET_SCHEMA,
        Keyword::CHARACTERISTICS,
        Keyword::CHARACTERS,
        Keyword::CLASS_ORIGIN,
        Keyword::COBOL,
        Keyword::COLLATION,
        Keyword::COLLATION_CATALOG,
        Keyword::COLLATION_NAME,
        Keyword::COLLATION_SCHEMA,
        Keyword::COLUMN_NAME,
        Keyword::COMMAND_FUNCTION,
        Keyword::COMMAND_FUNCTION_CODE,
        Keyword::COMMITTED,
        Keyword::CONDITION_NUMBER,
        Keyword::CONNECTION,
        Keyword::CONNECTION_NAME,
        Keyword::CONSTRAINT_CATALOG,
        Keyword::CONSTRAINT_NAME,
        Keyword::CONSTRAINT_SCHEMA,
        Keyword::CONSTRAINTS,
        Keyword::CONSTRUCTOR,
        Keyword::CONTAINS,
        Keyword::CONTINUE,
        Keyword::CURSOR_NAME,
        Keyword::DATA,
        Keyword::DATETIME_INTERVAL_CODE,
        Keyword::DATETIME_INTERVAL_PRECISION,
        Keyword::DEFAULTS,
        Keyword::DEFERRABLE,
        Keyword::DEFERRED,
        Keyword::DEFINED,
        Keyword::DEFINER,
        Keyword::DEGREE,
        Keyword::DEPTH,
        Keyword::DERIVED,
        Keyword::DESC,
        Keyword::DESCRIPTOR,
        Keyword::DIAGNOSTICS,
        Keyword::DISPATCH,
        Keyword::DOMAIN,
        Keyword::DYNAMIC_FUNCTION,
        Keyword::DYNAMIC_FUNCTION_CODE,
        Keyword::EQUALS,
        Keyword::EXCEPTION,
        Keyword::EXCLUDE,
        Keyword::EXCLUDING,
        Keyword::FINAL,
        Keyword::FIRST,
        Keyword::FOLLOWING,
        Keyword::FORTRAN,
        Keyword::FOUND,
        Keyword::G,
        Keyword::GENERAL,
        Keyword::GENERATED,
        Keyword::GO,
        Keyword::GOTO,
        Keyword::GRANTED,
        Keyword::HIERARCHY,
        Keyword::IMMEDIATE,
        Keyword::IMPLEMENTATION,
        Keyword::INCLUDING,
        Keyword::INCREMENT,
        Keyword::INITIALLY,
        Keyword::INPUT,
        Keyword::INSTANCE,
        Keyword::INSTANTIABLE,
        Keyword::INVOKER,
        Keyword::ISOLATION,
        Keyword::K,
        Keyword::KEY,
        Keyword::KEY_MEMBER,
        Keyword::KEY_TYPE,
        Keyword::LAST,
        Keyword::LENGTH,
        Keyword::LEVEL,
        Keyword::LOCATOR,
        Keyword::M,
        Keyword::MAP,
        Keyword::MATCHED,
        Keyword::MAXVALUE,
        Keyword::MESSAGE_LENGTH,
        Keyword::MESSAGE_OCTET_LENGTH,
        Keyword::MESSAGE_TEXT,
        Keyword::MINVALUE,
        Keyword::MORE,
        Keyword::MUMPS,
        Keyword::NAME,
        Keyword::NAMES,
        Keyword::NESTING,
        Keyword::NEXT,
        Keyword::NORMALIZED,
        Keyword::NULLABLE,
        Keyword::NULLS,
        Keyword::NUMBER,
        Keyword::OBJECT,
        Keyword::OCTETS,
        Keyword::OPTION,
        Keyword::OPTIONS,
        Keyword::ORDERING,
        Keyword::ORDINALITY,
        Keyword::OTHERS,
        Keyword::OUTPUT,
        Keyword::OVERRIDING,
        Keyword::PAD,
        Keyword::PARAMETER_MODE,
        Keyword::PARAMETER_NAME,
        Keyword::PARAMETER_ORDINAL_POSITION,
        Keyword::PARAMETER_SPECIFIC_CATALOG,
        Keyword::PARAMETER_SPECIFIC_NAME,
        Keyword::PARAMETER_SPECIFIC_SCHEMA,
        Keyword::PARTIAL,
        Keyword::PASCAL,
        Keyword::PATH,
        Keyword::PLACING,
        Keyword::PLI,
        Keyword::PRECEDING,
        Keyword::PRESERVE,
        Keyword::PRIOR,
        Keyword::PRIVILEGES,
        Keyword::PUBLIC,
        Keyword::READ,
        Keyword::RELATIVE,
        Keyword::REPEATABLE,
        Keyword::RESTART,
        Keyword::RESTRICT,
        Keyword::RETURNED_CARDINALITY,
        Keyword::RETURNED_LENGTH,
        Keyword::RETURNED_OCTET_LENGTH,
        Keyword::RETURNED_SQLSTATE,
        Keyword::ROLE,
        Keyword::ROUTINE,
        Keyword::ROUTINE_CATALOG,
        Keyword::ROUTINE_NAME,
        Keyword::ROUTINE_SCHEMA,
        Keyword::ROW_COUNT,
        Keyword::SCALE,
        Keyword::SCHEMA,
        Keyword::SCHEMA_NAME,
        Keyword::SCOPE_CATALOG,
        Keyword::SCOPE_NAME,
        Keyword::SCOPE_SCHEMA,
        Keyword::SECTION,
        Keyword::SECURITY,
        Keyword::SELF,
        Keyword::SEQUENCE,
        Keyword::SERIALIZABLE,
        Keyword::SERVER_NAME,
        Keyword::SESSION,
        Keyword::SETS,
        Keyword::SIMPLE,
        Keyword::SIZE,
        Keyword::SOURCE,
        Keyword::SPACE,
        Keyword::SPECIFIC_NAME,
        Keyword::STATE,
        Keyword::STATEMENT,
        Keyword::STRUCTURE,
        Keyword::STYLE,
        Keyword::SUBCLASS_ORIGIN,
        Keyword::TABLE_NAME,
        Keyword::TEMPORARY,
        Keyword::TIES,
        Keyword::TOP_LEVEL_COUNT,
        Keyword::TRANSACTION,
        Keyword::TRANSACTION_ACTIVE,
        Keyword::TRANSACTIONS_COMMITTED,
        Keyword::TRANSACTIONS_ROLLED_BACK,
        Keyword::TRANSFORM,
        Keyword::TRANSFORMS,
        Keyword::TRIGGER_CATALOG,
        Keyword::TRIGGER_NAME,
        Keyword::TRIGGER_SCHEMA,
        Keyword::TYPE,
        Keyword::UNBOUNDED,
        Keyword::UNCOMMITTED,
        Keyword::UNDER,
        Keyword::UNNAMED,
        Keyword::USAGE,
        Keyword::USER_DEFINED_TYPE_CATALOG,
        Keyword::USER_DEFINED_TYPE_CODE,
        Keyword::USER_DEFINED_TYPE_NAME,
        Keyword::USER_DEFINED_TYPE_SCHEMA,
        Keyword::VIEW,
        Keyword::WORK,
        Keyword::WRITE,
        Keyword::ZONE,
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

}
