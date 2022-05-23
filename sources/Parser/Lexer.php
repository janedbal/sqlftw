<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
// phpcs:disable SlevomatCodingStandard.ControlStructures.JumpStatementsSpacing
// phpcs:disable SlevomatCodingStandard.ControlStructures.AssignmentInCondition
// spell-check-ignore: recvresult

namespace SqlFtw\Parser;

use Dogma\Re;
use Dogma\Str;
use Dogma\StrictBehaviorMixin;
use Generator;
use SqlFtw\Parser\TokenType as T;
use SqlFtw\Platform\Mode;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Keyword;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_values;
use function explode;
use function implode;
use function in_array;
use function ltrim;
use function ord;
use function preg_match;
use function rtrim;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

/**
 * todo:
 * - prefix casts: timestamp'2001-01-01 00:00:00'
 * - quoted delimiters
 * - Date and Time Literals?
 * - Mysql string charset declaration (_utf* & N)
 * - \N is synonym for NULL (until 8.0)
 */
class Lexer
{
    use StrictBehaviorMixin;

    private const NUMBERS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const LETTERS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    private const OPERATOR_SYMBOLS = ['!', '%', '&', '*', '+', '-', '/', ':', '<', '=', '>', '\\', '^', '|', '~'];

    public const UUID_REGEXP = '~^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$~i';
    public const IP_V4_REGEXP = '~^((?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9]))~';

    /** @var array<string, int> (this is in fact array<int, int>, but PHPStan is unable to cope with the auto-casting of numeric string keys) */
    private static $numbersKey;

    /** @var array<string, int> */
    private static $hexadecKey;

    /** @var array<string, int> */
    private static $nameCharsKey;

    /** @var array<string, int> */
    private static $userVariableNameCharsKey;

    /** @var array<string, int> */
    private static $operatorSymbolsKey;

    /** @var PlatformSettings */
    private $settings;

    /** @var Platform */
    private $platform;

    /** @var bool */
    private $withComments;

    /** @var bool */
    private $withWhitespace;

    /** @var array<string, int> */
    private $reservedKey;

    /** @var array<string, int> */
    private $keywordsKey;

    /** @var array<string, int> */
    private $operatorsKey;

    /** @var array<string, int> */
    private $operatorKeywordsKey;

    /** @var array<string, int> */
    private $functionsKey;

    public function __construct(
        PlatformSettings $settings,
        bool $withComments = true,
        bool $withWhitespace = false
    ) {
        if (self::$numbersKey === null) {
            self::$numbersKey = array_flip(self::NUMBERS); // @phpstan-ignore-line
            self::$hexadecKey = array_flip(array_merge(self::NUMBERS, ['A', 'a', 'B', 'b', 'C', 'c', 'D', 'd', 'E', 'e', 'F', 'f']));
            self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_']));
            self::$userVariableNameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['$', '_', '.']));
            self::$operatorSymbolsKey = array_flip(self::OPERATOR_SYMBOLS);
        }

        $this->settings = $settings;
        $this->platform = $settings->getPlatform();
        $this->withComments = $withComments;
        $this->withWhitespace = $withWhitespace;

        $features = $this->platform->getFeatures();
        $this->reservedKey = array_flip($features->getReservedWords());
        $this->keywordsKey = array_flip($features->getNonReservedWords());
        $this->operatorsKey = array_flip($features->getOperators());
        $this->operatorKeywordsKey = array_flip($features->getOperatorKeywords());
        $this->functionsKey = array_flip($features->getBuiltInFunctions());
    }

    /**
     * Tokenize SQL code. Expects line endings to be converted to "\n" and UTF-8 encoding.
     * @return Token[]
     */
    public function tokenizeAll(string $string): array
    {
        $tokens = [];
        foreach ($this->tokenize($string) as $token) {
            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * Tokenize SQL code. Expects line endings to be converted to "\n" and UTF-8 encoding.
     * @return Token[]|Generator
     */
    public function tokenize(string $string): Generator
    {
        $length = strlen($string);
        $position = 0;
        $row = 1;
        $column = 1;

        $delimiter = $this->settings->getDelimiter();
        // last significant token parsed (comments and whitespace are skipped here)
        $previous = new Token(TokenType::END, 0);
        $condition = null;

        while ($position < $length) {
            $uuidCheck = false;
            $char = $string[$position];
            $start = $position;
            $position++;
            $column++;

            if ($char === $delimiter[0]) {
                if (substr($string, $position - 1, strlen($delimiter)) === $delimiter) {
                    $position += strlen($delimiter) - 1;
                    yield new Token(T::SYMBOL | T::DELIMITER, $start, $delimiter, null, $condition);
                    continue;
                }
            }

            switch ($char) {
                case ' ':
                case "\t":
                case "\r":
                case "\n":
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if ($next === ' ' || $next === "\t" || $next === "\r") {
                            $value .= $next;
                            $position++;
                            $column++;
                        } elseif ($next === "\n") {
                            $value .= $next;
                            $position++;
                            $column = 1;
                            $row++;
                        } else {
                            break;
                        }
                    }

                    if ($this->withWhitespace) {
                        yield new Token(T::WHITESPACE, $start, $value, null, $condition);
                    }
                    break;
                case '(':
                case ')':
                case '[':
                case ']':
                case '{':
                case '}':
                case ',':
                case ';':
                    yield $previous = new Token(T::SYMBOL, $start, $char, null, $condition);
                    break;
                case ':':
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    if ($value !== ':') {
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    } else {
                        yield $previous = new Token(T::SYMBOL | T::DOUBLE_COLON, $start, $char, null, $condition);
                    }
                    break;
                case '*':
                    // /*!12345 ... */
                    if ($position < $length && $condition !== null && $string[$position] === '/') {
                        $condition = null;
                        $position++;
                        $column++;
                        break;
                    }
                    // continue
                case '!':
                case '%':
                case '&':
                case '<':
                case '=':
                case '>':
                case '\\':
                case '^':
                case '|':
                case '~':
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (!isset($this->operatorsKey[$value . $next])) {
                            yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                            break 2;
                        }
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    break;
                case '?':
                    yield $previous = new Token(T::VALUE | T::PLACEHOLDER, $start, $char, null, $condition);
                    break;
                case '@':
                    $value = $char;
                    $second = $string[$position];
                    if ($second === '@') {
                        // @@variable
                        $value .= $second;
                        $position++;
                        $column++;
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '@' || isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                                $value .= $next;
                                $position++;
                                $column++;
                            } else {
                                break;
                            }
                        }
                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, $value, null, $condition);
                    } elseif ($second === '`') {
                        $position++;
                        $column++;
                        [$name, $orig] = $this->parseString($string, $position, $column, $row, $second);
                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, '@' . $name, '@' . $orig, $condition);
                    } elseif ($second === "'") {
                        $position++;
                        $column++;
                        [$name, $orig] = $this->parseString($string, $position, $column, $row, $second);
                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, '@' . $name, '@' . $orig, $condition);
                    } elseif ($second === '"') {
                        $position++;
                        $column++;
                        [$name, $orig] = $this->parseString($string, $position, $column, $row, $second);
                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, '@' . $name, '@' . $orig, $condition);
                    } elseif (isset(self::$userVariableNameCharsKey[$second]) || ord($second) > 127) {
                        // @variable
                        $value .= $second;
                        $position++;
                        $column++;
                        while ($position < $length) {
                            $next = $string[$position];
                            if (isset(self::$userVariableNameCharsKey[$next]) || ord($next) > 127) {
                                $value .= $next;
                                $position++;
                                $column++;
                            } else {
                                break;
                            }
                        }
                        yield $previous = new Token(T::NAME | T::AT_VARIABLE, $start, $value, null, $condition);
                    } else {
                        $exception = new LexerException('Invalid @ variable name', $position, $string);

                        yield $previous = new Token(T::NAME | T::AT_VARIABLE | T::INVALID, $start, null, $value, $condition, $exception);
                        break;
                    }
                    break;
                case '#':
                    // # comment
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if ($next === "\n") {
                            break;
                        } else {
                            $value .= $next;
                            $position++;
                            $column++;
                        }
                    }
                    if ($this->withComments) {
                        yield $previous = new Token(T::COMMENT | T::HASH_COMMENT, $start, $value, null, $condition);
                    }
                    break;
                case '/':
                    $next = $position < $length ? $string[$position] : '';
                    if ($next === '/') {
                        // // comment
                        $position++;
                        $column++;
                        $value = $char . $next;
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === "\n") {
                                break;
                            } else {
                                $value .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if ($this->withComments) {
                            yield $previous = new Token(T::COMMENT | T::DOUBLE_SLASH_COMMENT, $start, $value, null, $condition);
                        }
                    } elseif ($next === '*') {
                        $position++;
                        $column++;
                        if ($condition !== null) {
                            throw new LexerException('Comment inside conditional comment', $position, $string);
                        }
                        if (preg_match('~^[Mm]?!(?:[0-9]{5,6})?~', $string, $m, 0, $position) === 1) {
                            $versionId = strtoupper(str_replace('!', '', $m[0]));
                            if ($this->platform->interpretOptionalComment($versionId)) {
                                $condition = $versionId;
                                $position += strlen($versionId) + 1;
                                $column += strlen($versionId) + 1;
                                // continue parsing as conditional code
                                break;
                            }
                        }

                        // parse as a regular comment
                        $value = $char . $next;
                        $ok = false;
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '*' && ($position + 1 < $length) && $string[$position + 1] === '/') {
                                $value .= $next . $string[$position + 1];
                                $position += 2;
                                $column += 2;
                                $ok = true;
                                break;
                            } elseif ($next === "\n") {
                                $value .= $next;
                                $position++;
                                $column = 0;
                                $row++;
                            } else {
                                $value .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if (!$ok) {
                            throw new LexerException('End of comment not found', $position, $string);
                        }

                        if ($this->withComments) {
                            if ($value[2] === '!' || ($value[3] === '!' && ($value[2] === 'm' || $value[2] === 'M'))) {
                                // /*!12345 comment (when not interpreted as code) */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::OPTIONAL_COMMENT, $start, $value);
                            } elseif ($value[2] === '+') {
                                // /*+ comment */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT | T::HINT_COMMENT, $start, $value);
                            } else {
                                // /* comment */
                                yield new Token(T::COMMENT | T::BLOCK_COMMENT, $start, $value);
                            }
                        }
                    } else {
                        yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $char, null, $condition);
                    }
                    break;
                case '"':
                    [$value, $orig] = $this->parseString($string, $position, $column, $row, $char);
                    if ($this->settings->getMode()->containsAny(Mode::ANSI_QUOTES)) {
                        yield $previous = new Token(T::NAME | T::DOUBLE_QUOTED_STRING, $start, $value, $orig, $condition);
                    } else {
                        yield $previous = new Token(T::VALUE | T::STRING | T::DOUBLE_QUOTED_STRING, $start, $value, $orig, $condition);
                    }
                    break;
                case "'":
                    [$value, $orig] = $this->parseString($string, $position, $column, $row, $char);
                    yield $previous = new Token(T::VALUE | T::STRING | T::SINGLE_QUOTED_STRING, $start, $value, $orig, $condition);
                    break;
                case '`':
                    [$value, $orig] = $this->parseString($string, $position, $column, $row, $char);
                    yield $previous = new Token(T::NAME | T::BACKTICK_QUOTED_STRING, $start, $value, $orig, $condition);
                    break;
                case '.':
                    $next = $position < $length ? $string[$position] : '';
                    if (isset(self::$numbersKey[$next])) {
                        [$type, $value, $orig, $exception] = $this->parseNumber($string, $position, $column, $row, '.');
                        if ($type !== null) {
                            yield $previous = new Token($type, $start, $value, $orig, $condition, $exception);
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL, $start, $char, null, $condition);
                    break;
                case '-':
                    $next = $position < $length ? $string[$position] : '';
                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')')
                        || (($previous->type & T::KEYWORD) !== 0 && $previous->value === Keyword::DEFAULT);
                    if ($numberCanFollow) {
                        [$type, $value, $orig, $exception] = $this->parseNumber($string, $position, $column, $row, '-');
                        if ($type !== null) {
                            yield $previous = new Token($type, $start, $value, $orig, $condition, $exception);
                            break;
                        }
                    }

                    if ($next === '-') {
                        $position++;
                        $column++;
                        $value = $char . $next;
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === "\n") {
                                break;
                            } else {
                                $value .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if ($value === '--perl' && $this->settings->mysqlTestMode) {
                            // Perl code blocks from MySQL tests
                            $end = strpos($string, "\nEOF\n", $position);
                            if ($end === false) {
                                throw new LexerException('End of code block not found.', $position, $string);
                            } else {
                                $block = substr($string, $position - 6, $end - $position + 10);
                            }
                            $position += strlen($block) - 6;
                            $row += Str::count($block, "\n");

                            yield new Token(T::TEST_CODE, $start, $block, null, $condition);
                        } elseif ($this->settings->mysqlTestMode && Str::startsWith(strtolower($value), '--delimiter')) {
                            // change delimiter outside SQL
                            [, $del] = explode(' ', $value);
                            $delimiter = $del;
                            yield new Token(T::COMMENT | T::DOUBLE_HYPHEN_COMMENT, $start, $value, null, $condition);
                            yield new Token(T::KEYWORD, $start, Keyword::DELIMITER, $value, $condition);
                            if ($this->withWhitespace) {
                                yield new Token(T::WHITESPACE, $start, ' ', null, $condition);
                            }
                            yield $previous = new Token(T::DELIMITER_DEFINITION, $start, $del, null, $condition);
                        } else {
                            yield $previous = new Token(T::COMMENT | T::DOUBLE_HYPHEN_COMMENT, $start, $value, null, $condition);
                        }
                        break;
                    }
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    break;
                case '+':
                    $next = $position < $length ? $string[$position] : '';
                    $numberCanFollow = ($previous->type & T::END) !== 0
                        || (($previous->type & T::SYMBOL) !== 0 && $previous->value !== ')')
                        || (($previous->type & T::KEYWORD) !== 0 && $previous->value === Keyword::DEFAULT);
                    if ($numberCanFollow && isset(self::$numbersKey[$next])) {
                        [$type, $value, $orig, $exception] = $this->parseNumber($string, $position, $column, $row, '+');
                        if ($type !== null) {
                            yield $previous = new Token($type, $start, $value, $orig, $condition, $exception);
                            break;
                        }
                    }
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$operatorSymbolsKey[$next])) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::SYMBOL | T::OPERATOR, $start, $value, null, $condition);
                    break;
                case '0':
                    $next = $position < $length ? $string[$position] : '';
                    if ($next === 'b') {
                        // 0b00100011
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '0' || $next === '1') {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } else {
                                $orig = $char . 'b' . $bits;
                                yield $previous = new Token(T::VALUE | T::BINARY_LITERAL, $start, $bits, $orig, $condition);
                                break 2;
                            }
                        }
                    } elseif ($next === 'x') {
                        // 0x001f
                        $position++;
                        $column++;
                        $bits = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if (isset(self::$hexadecKey[$next])) {
                                $bits .= $next;
                                $position++;
                                $column++;
                            } else {
                                $orig = $char . 'x' . $bits;
                                yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL, $start, strtolower($bits), $orig, $condition);
                                break 2;
                            }
                        }
                    }
                    // continue
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                    $uuidCheck = true;
                    $value = substr($string, $position - 1, 36);
                    // UUID
                    if (strlen($value) === 36 && Re::match($value, self::UUID_REGEXP) !== null) {
                        $position += 35;
                        $column += 35;
                        yield $previous = new Token(T::VALUE | T::UUID, $start, $value, null, $condition);
                        break;
                    }
                    // IPv4
                    $ip = Re::submatch($value, self::IP_V4_REGEXP);
                    if ($ip !== null) {
                        $position += strlen($ip) - 1;
                        $column += strlen($ip) - 1;
                        yield $previous = new Token(T::VALUE | T::STRING, $start, $ip, null, $condition);
                        break;
                    }
                    [$type, $value, $orig, $exception] = $this->parseNumber($string, $position, $column, $row, $char);
                    if ($type !== null) {
                        yield $previous = new Token($type, $start, $value, $orig, $condition, $exception);
                        break;
                    }
                    // continue
                case 'B':
                case 'b':
                    // b'01'
                    // B'01'
                    if (($char === 'B' || $char === 'b') && $position < $length && $string[$position] === '\'') {
                        $position++;
                        $column++;
                        $bits = $next = '';
                        while ($position < $length) {
                            /** @var string $next */
                            $next = $string[$position];
                            if ($next === '\'') {
                                $position++;
                                $column++;
                                break;
                            } else {
                                $bits .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if (ltrim($bits, '01') === '') {
                            $orig = $char . '\'' . $bits . '\'';

                            yield $previous = new Token(T::VALUE | T::BINARY_LITERAL, $start, $bits, $orig, $condition);
                        } else {
                            $exception = new LexerException('Invalid binary literal', $position, $string);
                            $orig = $char . '\'' . $bits . $next;

                            yield $previous = new Token(T::VALUE | T::BINARY_LITERAL | T::INVALID, $start, null, $orig, $condition, $exception);
                            break;
                        }
                        break;
                    }
                    // continue
                case 'A':
                case 'a':
                case 'C':
                case 'c':
                case 'D':
                case 'd':
                case 'E':
                case 'e':
                case 'F':
                case 'f':
                    if (!$uuidCheck) {
                        $value = substr($string, $position - 1, 36);
                        // UUID
                        if (strlen($value) === 36 && Re::match($value, self::UUID_REGEXP) !== null) {
                            $position += 35;
                            $column += 35;
                            yield $previous = new Token(T::VALUE | T::UUID, $start, $value, null, $condition);
                            break;
                        }
                    }
                    // continue
                case 'X':
                case 'x':
                    if (($char === 'X' || $char === 'x') && $position < $length && $string[$position] === '\'') {
                        $position++;
                        $column++;
                        $bits = $next = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === '\'') {
                                $position++;
                                $column++;
                                break;
                            } else {
                                $bits .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        $bits = strtolower($bits);
                        if (ltrim($bits, '0123456789abcdef') === '') {
                            $orig = $char . '\'' . $bits . '\'';

                            yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL, $start, $bits, $orig, $condition);
                        } else {
                            $exception = new LexerException('Invalid hexadecimal literal', $position, $string);
                            $orig = $char . '\'' . $bits . $next;

                            yield $previous = new Token(T::VALUE | T::HEXADECIMAL_LITERAL | T::INVALID, $start, null, $orig, $condition, $exception);
                            break;
                        }
                        break;
                    }
                    // continue
                case 'G':
                case 'g':
                case 'H':
                case 'h':
                case 'I':
                case 'i':
                case 'J':
                case 'j':
                case 'K':
                case 'k':
                case 'L':
                case 'l':
                case 'M':
                case 'm':
                case 'N':
                    // todo: charset declaration
                case 'n':
                case 'O':
                case 'o':
                case 'P':
                case 'p':
                case 'Q':
                case 'q':
                case 'R':
                case 'r':
                case 'S':
                case 's':
                case 'T':
                case 't':
                case 'U':
                case 'u':
                case 'V':
                case 'v':
                case 'W':
                case 'w':
                case 'Y':
                case 'y':
                case 'Z':
                case 'z':
                case '_':
                case '$':
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    $yieldDelimiter = false;
                    if (Str::endsWith($value, $delimiter)) {
                        // fucking name-like delimiter after name without whitespace
                        $value = substr($value, 0, -strlen($delimiter));
                        $yieldDelimiter = true;
                    }

                    $upper = strtoupper($value);
                    static $types = [Keyword::TIMESTAMP, Keyword::DATE, Keyword::TIME];
                    if ($upper === Keyword::NULL) {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, Keyword::NULL, $value, $condition);
                    } elseif ($upper === Keyword::TRUE) {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, Keyword::TRUE, $value, $condition);
                    } elseif ($upper === Keyword::FALSE) {
                        yield $previous = new Token(T::KEYWORD | T::VALUE, $start, Keyword::FALSE, $value, $condition);
                    } elseif (in_array($upper, $types, true) && ($string[$position] === "'")) {
                        // timestamp'2001-01-01 00:00:00'
                        yield $previous = new Token(T::NAME | T::STRING_INTRODUCER, $start, $upper, $value, $condition);
                    } elseif (isset($this->reservedKey[$upper])) {
                        if (isset($this->operatorKeywordsKey[$upper])) {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED | T::OPERATOR, $start, $upper, $value, $condition);
                        } elseif (isset($this->functionsKey[$upper])) {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED | T::NAME | T::UNQUOTED_NAME, $start, $upper, $value, $condition);
                        } else {
                            yield $previous = new Token(T::KEYWORD | T::RESERVED, $start, $upper, $value, $condition);
                        }
                    } elseif (isset($this->keywordsKey[$upper])) {
                        yield $previous = new Token(T::KEYWORD | T::NAME | T::UNQUOTED_NAME, $start, $upper, $value, $condition);
                    } elseif ($value[0] === '_' && ($charset = substr($value, 1)) !== '' && Charset::validateValue($charset)) {
                        // _utf8'foo'
                        yield $previous = new Token(T::NAME | T::STRING_INTRODUCER, $start, $charset, $value, $condition);
                    } elseif ($upper === Keyword::DELIMITER && $this->platform->userDelimiter()) {
                        yield new Token(T::KEYWORD, $start, $upper, $value, $condition);
                        $start = $position;
                        $whitespace = $this->parseWhitespace($string, $position, $column, $row);
                        $whitespace = new Token(T::WHITESPACE, $start, $whitespace, null, $condition);
                        if ($this->withWhitespace) {
                            yield $whitespace;
                        }
                        $start = $position;
                        $del = '';
                        while ($position < $length) {
                            $next = $string[$position];
                            if ($next === "\n") {
                                break;
                            } else {
                                $del .= $next;
                                $position++;
                                $column++;
                            }
                        }
                        if ($del === '') {
                            $exception = new LexerException('Delimiter not found', $position, $string);

                            yield $previous = new Token(T::INVALID, $start, null, $del, $condition, $exception);
                            break;
                        }
                        if (Str::endsWith($del, $delimiter)) {
                            $trimmed = substr($del, 0, -strlen($delimiter));
                            if ($trimmed !== $delimiter) {
                                // do not trim delimiter when would not change the current one (;; vs ;)
                                $del = $trimmed;
                            }
                        }
                        if ($this->settings->getPlatform()->getFeatures()->isReserved(strtoupper($del))) {
                            $exception = new LexerException('Delimiter can not be a reserved word', $position, $string);

                            yield $previous = new Token(T::INVALID, $start, null, $del, $condition, $exception);
                            break;
                        }
                        // todo: quoted delimiters :E
                        /*
                         * The delimiter string can be specified as an unquoted or quoted argument on the delimiter command line.
                         * Quoting can be done with either single quote ('), double quote ("), or backtick (`) characters.
                         * To include a quote within a quoted string, either quote the string with a different quote character
                         * or escape the quote with a backslash (\) character. Backslash should be avoided outside of quoted
                         * strings because it is the escape character for MySQL. For an unquoted argument, the delimiter is read
                         * up to the first space or end of line. For a quoted argument, the delimiter is read up to the matching quote on the line.
                         */
                        $delimiter = $del;
                        $this->settings->setDelimiter($delimiter);
                        yield $previous = new Token(T::SYMBOL | T::DELIMITER_DEFINITION, $start, $delimiter, $condition);
                    } elseif ($value === 'EOF' && $this->settings->mysqlTestMode && $string[$position - 4] === "\n" && $string[$position] === "\n") {
                        yield new Token(T::TEST_CODE, $start, 'EOF');
                    } elseif ($value === 'perl' && $this->settings->mysqlTestMode && $string[$position - 5] === "\n" && $string[$position] === ';') {
                        // Perl code blocks from MySQL tests
                        $end = strpos($string, "\nEOF\n", $position);
                        if ($end === false) {
                            throw new LexerException('End of test code block not found.', $position, $string);
                        } else {
                            $block = substr($string, $position - 4, $end - $position + 8);
                        }
                        $position += strlen($block) - 4;
                        $row += Str::count($block, "\n");

                        yield new Token(T::TEST_CODE, $start, $block);
                    } elseif ($value === 'Mysqlx' && $this->settings->mysqlTestMode && substr($string, $position, 5) === '.Crud') {
                        // some other testing thing...
                        $end = strpos($string, "\n-->recvresult\n", $position);
                        if ($end === false) {
                            throw new LexerException('End of test code block not found.', $position, $string);
                        } else {
                            $block = substr($string, $position - 6, $end - $position + 20);
                        }
                        $position += strlen($block) - 6;
                        $row += Str::count($block, "\n");

                        yield new Token(T::TEST_CODE, $start, $block);
                    } else {
                        yield $previous = new Token(T::NAME | T::UNQUOTED_NAME, $start, $value, $value, $condition);
                    }

                    if ($yieldDelimiter) {
                        yield new Token(T::SYMBOL | T::DELIMITER, $start, $delimiter, null, $condition);
                    }
                    break;
                default:
                    if (ord($char) < 32) {
                        $exception = new LexerException('Invalid ASCII control character', $position, $string);

                        yield $previous = new Token(T::INVALID, $start, null, $char, $condition, $exception);
                        break;
                    }
                    $value = $char;
                    while ($position < $length) {
                        $next = $string[$position];
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $position++;
                            $column++;
                        } else {
                            break;
                        }
                    }
                    yield $previous = new Token(T::NAME | T::UNQUOTED_NAME, $start, $value, $value, $condition);
            }
        }
    }

    private function parseWhitespace(string &$string, int &$position, int &$column, int &$row): string
    {
        $length = strlen($string);
        $whitespace = '';
        while ($position < $length) {
            $next = $string[$position];
            if ($next === ' ' || $next === "\t" || $next === "\r") {
                $whitespace .= $next;
                $position++;
                $column++;
            } elseif ($next === "\n") {
                $whitespace .= $next;
                $position++;
                $column = 1;
                $row++;
            } else {
                break;
            }
        }

        return $whitespace;
    }

    /**
     * @return string[] ($value, $orig)
     */
    private function parseString(string &$string, int &$position, int &$column, int &$row, string $quote): array
    {
        $length = strlen($string);
        $backslashes = !$this->settings->getMode()->containsAny(Mode::NO_BACKSLASH_ESCAPES);

        $orig = [$quote];
        $escaped = false;
        $finished = false;
        while ($position < $length) {
            $next = $string[$position];
            if ($next === $quote) {
                $orig[] = $next;
                $position++;
                $column++;
                if ($escaped) {
                    $escaped = false;
                } elseif ($position < $length && $string[$position] === $quote) {
                    $escaped = true;
                } else {
                    $finished = true;
                    break;
                }
            } elseif ($next === "\n") {
                $orig[] = $next;
                $position++;
                $column = 1;
                $row++;
            } elseif ($backslashes && $next === '\\') {
                $escaped = !$escaped;
                $orig[] = $next;
                $position++;
                $column++;
            } elseif ($escaped && $next !== '\\' && $next !== $quote) {
                $escaped = false;
                $orig[] = $next;
                $position++;
                $column++;
            } else {
                $orig[] = $next;
                $position++;
                $column++;
            }
        }
        if (!$finished) {
            throw new LexerException('End of string not found', $position, $string);
        }
        $orig = implode('', $orig);
        $value = $this->unescapeString($orig, $quote);

        return [$value, $orig];
    }

    /**
     * NO_BACKSLASH_ESCAPES mode:
     * Disable the use of the backslash character (\) as an escape character within strings.
     * With this mode enabled, backslash becomes an ordinary character like any other.
     *
     * \0   An ASCII NUL (X'00') character
     * \'   A single quote (') character
     * \"   A double quote (") character
     * \b   A backspace character
     * \n   A newline (linefeed) character
     * \r   A carriage return character
     * \t   A tab character
     * \Z   ASCII 26 (Control+Z)
     * \\   A backslash (\) character
     *
     * (do not unescape. keep original for LIKE)
     * \%   A % character
     * \_   A _ character
     *
     * A ' inside a string quoted with ' may be written as ''.
     * A " inside a string quoted with " may be written as "".
     */
    private function unescapeString(string $string, string $quote): string
    {
        $translations = [
            '\\0' => "\x00",
            '\\\'' => '\'',
            '\\""' => '""',
            '\\b' => "\x08",
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\Z' => "\x1A",
            '\\\\' => '\\',
        ];

        $string = substr($string, 1, -1);

        $string = str_replace($quote . $quote, $quote, $string);
        if (!$this->settings->getMode()->containsAny(Mode::NO_BACKSLASH_ESCAPES)) {
            $string = str_replace(array_keys($translations), array_values($translations), $string);

            // todo: ???
        }

        return $string;
    }

    /**
     * @return array{int|null, int|float|string|null, string|null, LexerException|null} ($value, $original, $tokenType, $exception)
     */
    private function parseNumber(string &$string, int &$position, int &$column, int &$row, string $start): array
    {
        $type = T::VALUE | T::NUMBER;
        $length = strlen($string);
        $offset = 0;
        $isNumeric = isset(self::$numbersKey[$start]);
        $base = $start;
        $minusAllowed = $start === '-';
        $exp = '';
        do {
            // integer (prefixed by any number of "-")
            $next = '';
            while ($position + $offset < $length) {
                $next = $string[$position + $offset];
                if (isset(self::$numbersKey[$next]) || ($minusAllowed && $next === '-')) {
                    $base .= $next;
                    $offset++;
                    if ($next !== '-') {
                        $isNumeric = true;
                        $minusAllowed = false;
                    }
                } else {
                    break;
                }
            }
            if ($position + $offset >= $length) {
                break;
            }

            // decimal part
            if ($next === '.') {
                if ($start !== '.') {
                    $base .= $next;
                    $offset++;
                    while ($position + $offset < $length) {
                        $next = $string[$position + $offset];
                        if (isset(self::$numbersKey[$next])) {
                            $base .= $next;
                            $offset++;
                            $isNumeric = true;
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            }
            if (!$isNumeric) {
                return [null, null, null, null];
            }
            if ($position + $offset >= $length) {
                break;
            }

            // exponent
            $next = $string[$position + $offset];
            do {
                if ($next === 'e' || $next === 'E') {
                    $exp = $next;
                    $offset++;
                    $next = $position + $offset < $length ? $string[$position + $offset] : '';
                    $expComplete = false;
                    if ($next === '+' || $next === '-' || isset(self::$numbersKey[$next])) {
                        $exp .= $next;
                        $offset++;
                        if (isset(self::$numbersKey[$next])) {
                            $expComplete = true;
                        }
                    }
                    while ($position + $offset < $length) {
                        $next = $string[$position + $offset];
                        if (isset(self::$numbersKey[$next])) {
                            $exp .= $next;
                            $offset++;
                            $expComplete = true;
                        } else {
                            if (trim($exp, 'e+-') === '' && strpos($base, '.') !== false) {
                                return [$type | T::INVALID, null, $base . $exp, new LexerException('Invalid number exponent ' . $exp, $position, $string)];
                            }
                            break;
                        }
                    }
                    if (!$expComplete) {
                        return [$type | T::INVALID, null, $base . $exp, new LexerException('Invalid number exponent ' . $exp, $position, $string)];
                    }
                } elseif (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                    $isNumeric = false;
                    break 2;
                }
            } while (false); // @phpstan-ignore-line
        } while (false); // @phpstan-ignore-line

        if (!$isNumeric) {
            return [null, null, null, null];
        }

        $orig = $base . $exp;
        $value = $base . str_replace('e+', 'e', strtolower($exp));

        $len = strlen($orig) - 1;
        $position += $len;
        $column += $len;

        if ($value === (string) (int) $value) {
            $value = (int) $value;
            $type |= T::INT;
            if ($value >= 0) {
                $type |= T::UINT;
            }

            return [$type, $value, $orig, null];
        }

        // value clean-up: --+.123E+2 => 0.123e2
        while ($value[0] === '-' && $value[1] === '-') {
            $value = substr($value, 2);
        }
        $value = ltrim($value, '+');
        $value = rtrim($value, '.');
        if ($value[0] === '.') {
            $value = '0' . $value;
        }
        $value = str_replace('.e', '.0e', $value);

        if ($value === (string) (int) $value && $exp === '' && strpos($base, '.') === false) {
            $value = (int) $value;
            $type |= TokenType::INT;
        } elseif ($value === (string) (float) $value) {
            $value = (float) $value;
        }

        return [$type, $value, $orig, null];
    }

}
