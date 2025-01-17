# SQLFTW

(My)SQL lexer, parser and language model written in PHP

it is a validating parser which produces an object implementing SqlFtw\Sql\Command interface
for each of approximately 140 supported SQL commands. Commands do model the syntactic aspect of SQL code,
not domain aspect (models exactly how queries are written), however does not track white space and currently 
ignores all comments

this parser is intended as a basis for two other projects:
- one is doing static analysis of SQL code, especially safety and performance of migrations (currently using very basic SQL parser from phpMyAdmin project)
- another will hopefully help PHPStan (static analysis tool for PHP) better understand SQL queries and their results

on its own it can be used to validate syntax of (My)SQL code (e.g. migrations)


SQL syntax support:
-------------------

supports all SQL commands from MySQL 5.x to MySQL 8.0.29 and almost all language features

not yet supported features:
- support for ascii-incompatible multibyte encodings like `shift-jis` or `gb18030`
- resolving operator precedence in expressions
- regular comments (conditional comments are parsed)
- optimizer hint comments
- quoted delimiters
- implicit string concatenation of double-quoted strings in ANSI mode (`"foo" "bar"`)
- nested comments (throws parse error; deprecated in MySQL 8)
- HeatWave plugin features (SECONDARY_ENGINE)

parsed, but ignored features:
- `SELECT ... PROCEDURE ANALYSE (...)` - removed
- `WEIGHT_STRING(... LEVEL ...)` - removed


Architecture:
-------------

main layers:
- Lexer - tokenizes SQL, returns a Generator of parser Tokens
- Parser(s) - validates syntax and returns a Generator of parsed Command objects
- Command(s) - SQL commands parsed from plaintext to immutable object representation. can be serialized back to plaintext
- Reflection - database structure representation independent of actual SQL syntax (work in progress)
- Platform - lists of features supported by particular platform
- Formatter - helper for configuring SQL serialisation


Basic usage:
------------

```
<?php

use ...

$platform = new Platform(Platform::MYSQL, '8.0');
$settings = new ParserSettings($platform);
$parser = new Parser($settings);
try {
    $commands = $parser->parse('SELECT foo FROM ...');
    foreach ($commands as $command) {
        // ...
    }
} catch (ParserException $e) {
    // ...
}
```


Current state of development:
-----------------------------

where we are now:
- [x] ~99.9% MySQL language features implemented
- [x] basic unit tests with serialisation
- [x] tested against several thousands of tables and migrations
- [x] parses everything from MySQL test suite (no false negatives)
- [ ] fails on all error tests from MySQL test suite (no false positives)
- [ ] serialisation testing on MySQL test suite (all features kept as expected)
- [ ] fuzzy testing (parser handles mutated SQL strings exactly like a real DB)
- [ ] porting my static analysis tools on the new parser (probably many API changes)
- [ ] distinguishing server version (parsing for exact version of the DB server)
- [ ] 100% MySQL language features implemented
- [ ] release of first stable version?
- [ ] other platforms? (MariaDB, SQLite, PostgreSQL, ...)


Author:
-------

Vlasta Neubauer, @paranoiq, https://github.com/paranoiq
