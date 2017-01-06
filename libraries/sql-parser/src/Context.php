<?php

/**
 * Defines a context class that is later extended to define other contexts.
 *
 * A context is a collection of keywords, operators and functions used for
 * parsing.
 */

namespace SqlParser;

/**
 * Holds the configuration of the context that is currently used.
 *
 * @category Contexts
 *
 * @license  https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
abstract class Context
{
    /**
     * The maximum length of a keyword.
     *
     * @see static::$TOKEN_KEYWORD
     *
     * @var int
     */
    const KEYWORD_MAX_LENGTH = 30;

    /**
     * The maximum length of a label.
     *
     * @see static::$TOKEN_LABEL
     * Ref: https://dev.mysql.com/doc/refman/5.7/en/statement-labels.html
     *
     * @var int
     */
    const LABEL_MAX_LENGTH = 16;

    /**
     * The maximum length of an operator.
     *
     * @see static::$TOKEN_OPERATOR
     *
     * @var int
     */
    const OPERATOR_MAX_LENGTH = 4;

    /**
     * The name of the default content.
     *
     * @var string
     */
    public static $defaultContext = '\\SqlParser\\Contexts\\ContextMySql50700';

    /**
     * The name of the loaded context.
     *
     * @var string
     */
    public static $loadedContext = '\\SqlParser\\Contexts\\ContextMySql50700';

    /**
     * The prefix concatenated to the context name when an incomplete class name
     * is specified.
     *
     * @var string
     */
    public static $contextPrefix = '\\SqlParser\\Contexts\\Context';

    /**
     * List of keywords.
     *
     * Because, PHP's associative arrays are basically hash tables, it is more
     * efficient to store keywords as keys instead of values.
     *
     * The value associated to each keyword represents its flags.
     *
     * @see Token::FLAG_KEYWORD_*
     *
     * Elements are sorted by flags, length and keyword.
     *
     * @var array
     */
    public static $KEYWORDS = array();

    /**
     * List of operators and their flags.
     *
     * @var array
     */
    public static $OPERATORS = array(
        // Some operators (*, =) may have ambiguous flags, because they depend on
        // the context they are being used in.
        // For example: 1. SELECT * FROM table; # SQL specific (wildcard)
        //                 SELECT 2 * 3;        # arithmetic
        //              2. SELECT * FROM table WHERE foo = 'bar';
        //                 SET @i = 0;

        // @see Token::FLAG_OPERATOR_ARITHMETIC
        '%' => 1, '*' => 1, '+' => 1, '-' => 1, '/' => 1,

        // @see Token::FLAG_OPERATOR_LOGICAL
        '!' => 2, '!=' => 2, '&&' => 2, '<' => 2, '<=' => 2,
        '<=>' => 2, '<>' => 2, '=' => 2, '>' => 2, '>=' => 2,
        '||' => 2,

        // @see Token::FLAG_OPERATOR_BITWISE
        '&' => 4, '<<' => 4, '>>' => 4, '^' => 4, '|' => 4,
        '~' => 4,

        // @see Token::FLAG_OPERATOR_ASSIGNMENT
        ':=' => 8,

        // @see Token::FLAG_OPERATOR_SQL
        '(' => 16, ')' => 16, '.' => 16,  ',' => 16, ';' => 16,
    );

    /**
     * The mode of the MySQL server that will be used in lexing, parsing and
     * building the statements.
     *
     * @var int
     */
    public static $MODE = 0;

    /*
     * Server SQL Modes
     * https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html
     */

    // Compatibility mode for Microsoft's SQL server.
    // This is the equivalent of ANSI_QUOTES.
    const COMPAT_MYSQL = 2;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_allow_invalid_dates
    const ALLOW_INVALID_DATES = 1;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_ansi_quotes
    const ANSI_QUOTES = 2;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_error_for_division_by_zero
    const ERROR_FOR_DIVISION_BY_ZERO = 4;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_high_not_precedence
    const HIGH_NOT_PRECEDENCE = 8;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_ignore_space
    const IGNORE_SPACE = 16;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_auto_create_user
    const NO_AUTO_CREATE_USER = 32;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_auto_value_on_zero
    const NO_AUTO_VALUE_ON_ZERO = 64;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_backslash_escapes
    const NO_BACKSLASH_ESCAPES = 128;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_dir_in_create
    const NO_DIR_IN_CREATE = 256;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_dir_in_create
    const NO_ENGINE_SUBSTITUTION = 512;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_field_options
    const NO_FIELD_OPTIONS = 1024;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_key_options
    const NO_KEY_OPTIONS = 2048;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_table_options
    const NO_TABLE_OPTIONS = 4096;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_unsigned_subtraction
    const NO_UNSIGNED_SUBTRACTION = 8192;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_zero_date
    const NO_ZERO_DATE = 16384;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_zero_in_date
    const NO_ZERO_IN_DATE = 32768;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_only_full_group_by
    const ONLY_FULL_GROUP_BY = 65536;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_pipes_as_concat
    const PIPES_AS_CONCAT = 131072;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_real_as_float
    const REAL_AS_FLOAT = 262144;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_strict_all_tables
    const STRICT_ALL_TABLES = 524288;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_strict_trans_tables
    const STRICT_TRANS_TABLES = 1048576;

    // Custom modes.

    // The table and column names and any other field that must be escaped will
    // not be.
    // Reserved keywords are being escaped regardless this mode is used or not.
    const NO_ENCLOSING_QUOTES = 1073741824;

    /*
     * Combination SQL Modes
     * https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sql-mode-combo
     */

    // REAL_AS_FLOAT, PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE
    const SQL_MODE_ANSI = 393234;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS,
    const SQL_MODE_DB2 = 138258;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS, NO_AUTO_CREATE_USER
    const SQL_MODE_MAXDB = 138290;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS
    const SQL_MODE_MSSQL = 138258;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS, NO_AUTO_CREATE_USER
    const SQL_MODE_ORACLE = 138290;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS
    const SQL_MODE_POSTGRESQL = 138258;

    // STRICT_TRANS_TABLES, STRICT_ALL_TABLES, NO_ZERO_IN_DATE, NO_ZERO_DATE,
    // ERROR_FOR_DIVISION_BY_ZERO, NO_AUTO_CREATE_USER
    const SQL_MODE_TRADITIONAL = 1622052;

    // -------------------------------------------------------------------------
    // Keyword.

    /**
     * Checks if the given string is a keyword.
     *
     * @param string $str        string to be checked
     * @param bool   $isReserved checks if the keyword is reserved
     *
     * @return int
     */
    public static function isKeyword($str, $isReserved = false)
    {
        $str = strtoupper($str);

        if (isset(static::$KEYWORDS[$str])) {
            if ($isReserved) {
                if (!(static::$KEYWORDS[$str] & Token::FLAG_KEYWORD_RESERVED)) {
                    return null;
                }
            }

            return static::$KEYWORDS[$str];
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Operator.

    /**
     * Checks if the given string is an operator.
     *
     * @param string $str string to be checked
     *
     * @return int the appropriate flag for the operator
     */
    public static function isOperator($str)
    {
        if (!isset(static::$OPERATORS[$str])) {
            return null;
        }

        return static::$OPERATORS[$str];
    }

    // -------------------------------------------------------------------------
    // Whitespace.

    /**
     * Checks if the given character is a whitespace.
     *
     * @param string $str string to be checked
     *
     * @return bool
     */
    public static function isWhitespace($str)
    {
        return ($str === ' ') || ($str === "\r") || ($str === "\n") || ($str === "\t");
    }

    // -------------------------------------------------------------------------
    // Comment.

    /**
     * Checks if the given string is the beginning of a whitespace.
     *
     * @param string $str string to be checked
     *
     * @return int the appropriate flag for the comment type
     */
    public static function isComment($str)
    {
        $len = strlen($str);
        if ($str[0] === '#') {
            return Token::FLAG_COMMENT_BASH;
        } elseif (($len > 1) && ($str[0] === '/') && ($str[1] === '*')) {
            return (($len > 2) && ($str[2] == '!')) ?
                Token::FLAG_COMMENT_MYSQL_CMD : Token::FLAG_COMMENT_C;
        } elseif (($len > 1) && ($str[0] === '*') && ($str[1] === '/')) {
            return Token::FLAG_COMMENT_C;
        } elseif (($len > 2) && ($str[0] === '-')
            && ($str[1] === '-') && (static::isWhitespace($str[2]))
        ) {
            return Token::FLAG_COMMENT_SQL;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Bool.

    /**
     * Checks if the given string is a boolean value.
     * This actually check only for `TRUE` and `FALSE` because `1` or `0` are
     * actually numbers and are parsed by specific methods.
     *
     * @param string $str string to be checked
     *
     * @return bool
     */
    public static function isBool($str)
    {
        $str = strtoupper($str);

        return ($str === 'TRUE') || ($str === 'FALSE');
    }

    // -------------------------------------------------------------------------
    // Number.

    /**
     * Checks if the given character can be a part of a number.
     *
     * @param string $str string to be checked
     *
     * @return bool
     */
    public static function isNumber($str)
    {
        return (($str >= '0') && ($str <= '9')) || ($str === '.')
            || ($str === '-') || ($str === '+') || ($str === 'e') || ($str === 'E');
    }

    // -------------------------------------------------------------------------
    // Symbol.

    /**
     * Checks if the given character is the beginning of a symbol. A symbol
     * can be either a variable or a field name.
     *
     * @param string $str string to be checked
     *
     * @return int the appropriate flag for the symbol type
     */
    public static function isSymbol($str)
    {
        if ($str[0] === '@') {
            return Token::FLAG_SYMBOL_VARIABLE;
        } elseif ($str[0] === '`') {
            return Token::FLAG_SYMBOL_BACKTICK;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // String.

    /**
     * Checks if the given character is the beginning of a string.
     *
     * @param string $str string to be checked
     *
     * @return int the appropriate flag for the string type
     */
    public static function isString($str)
    {
        if ($str[0] === '\'') {
            return Token::FLAG_STRING_SINGLE_QUOTES;
        } elseif ($str[0] === '"') {
            return Token::FLAG_STRING_DOUBLE_QUOTES;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Delimiter.

    /**
     * Checks if the given character can be a separator for two lexeme.
     *
     * @param string $str string to be checked
     *
     * @return bool
     */
    public static function isSeparator($str)
    {
        // NOTES:   Only non alphanumeric ASCII characters may be separators.
        //          `~` is the last printable ASCII character.
        return ($str <= '~') && ($str !== '_')
            && (($str < '0') || ($str > '9'))
            && (($str < 'a') || ($str > 'z'))
            && (($str < 'A') || ($str > 'Z'));
    }

    /**
     * Loads the specified context.
     *
     * Contexts may be used by accessing the context directly.
     *
     * @param string $context name of the context or full class name that
     *                        defines the context
     *
     * @throws \Exception if the specified context doesn't exist
     */
    public static function load($context = '')
    {
        if (empty($context)) {
            $context = self::$defaultContext;
        }
        if ($context[0] !== '\\') {
            // Short context name (must be formatted into class name).
            $context = self::$contextPrefix . $context;
        }
        if (!class_exists($context)) {
            throw new \Exception(
                'Specified context ("' . $context . '") does not exist.'
            );
        }
        self::$loadedContext = $context;
        self::$KEYWORDS = $context::$KEYWORDS;
    }

    /**
     * Loads the context with the closest version to the one specified.
     *
     * The closest context is found by replacing last digits with zero until one
     * is loaded successfully.
     *
     * @see Context::load()
     *
     * @param string $context name of the context or full class name that
     *                        defines the context
     *
     * @return string The loaded context. `null` if no context was loaded.
     */
    public static function loadClosest($context = '')
    {
        /**
         * The number of replaces done by `preg_replace`.
         * This actually represents whether a new context was generated or not.
         *
         * @var int
         */
        $count = 0;

        // As long as a new context can be generated, we try to load it.
        do {
            try {
                // Trying to load the new context.
                static::load($context);
            } catch (\Exception $e) {
                // If it didn't work, we are looking for a new one and skipping
                // over to the next generation that will try the new context.
                $context = preg_replace(
                    '/[1-9](0*)$/',
                    '0$1',
                    $context,
                    -1,
                    $count
                );
                continue;
            }

            // Last generated context was valid (did not throw any exceptions).
            // So we return it, to let the user know what context was loaded.
            return $context;
        } while ($count !== 0);

        return null;
    }

    /**
     * Sets the SQL mode.
     *
     * @param string $mode The list of modes. If empty, the mode is reset.
     */
    public static function setMode($mode = '')
    {
        static::$MODE = 0;
        if (empty($mode)) {
            return;
        }
        $mode = explode(',', $mode);
        foreach ($mode as $m) {
            static::$MODE |= constant('static::' . $m);
        }
    }

    /**
     * Escapes the symbol by adding surrounding backticks.
     *
     * @param array|string $str   the string to be escaped
     * @param string       $quote quote to be used when escaping
     *
     * @return string
     */
    public static function escape($str, $quote = '`')
    {
        if (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = static::escape($value);
            }

            return $str;
        }

        if ((static::$MODE & self::NO_ENCLOSING_QUOTES)
            && (!static::isKeyword($str, true))
        ) {
            return $str;
        }

        if (static::$MODE & self::ANSI_QUOTES) {
            $quote = '"';
        }

        return $quote . str_replace($quote, $quote . $quote, $str) . $quote;
    }
}

// Initializing the default context.
Context::load();
