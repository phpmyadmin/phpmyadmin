<?php

namespace SqlParser;

/**
 * Default MySQL context (based on MySQL 5.7).
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
     * The maximum length of an operator.
     *
     * @see static::$TOKEN_OPERATOR
     *
     * @var int
     */
    const OPERATOR_MAX_LENGTH = 4;


    // -------------------------------------------------------------------------
    // Keywords.

    /**
     * List of official keywords.
     *
     * Because, PHP's associative arrays are basically hash tables, it is more
     * efficient to store keywords as keys instead of values.
     *
     * @var array
     */
    public static $KEYWORDS = array(
        'AS' => 1, 'BY' => 1, 'IF' => 1, 'IN' => 1, 'IS' => 1, 'ON' => 1,
        'OR' => 1, 'TO' => 1,
        'ADD' => 1, 'ALL' => 1, 'AND' => 1, 'ASC' => 1, 'DEC' => 1, 'DIV' => 1,
        'FOR' => 1, 'GET' => 1, 'INT' => 1, 'KEY' => 1, 'MOD' => 1, 'NOT' => 1,
        'OUT' => 1, 'SET' => 1, 'SQL' => 1, 'SSL' => 1, 'USE' => 1, 'XOR' => 1,
        'BLOB' => 1, 'BOTH' => 1, 'CALL' => 1, 'CASE' => 1, 'CHAR' => 1,
        'DESC' => 1, 'DROP' => 1, 'DUAL' => 1, 'EACH' => 1, 'ELSE' => 1,
        'EXIT' => 1, 'FROM' => 1, 'GOTO' => 1, 'INT1' => 1, 'INT2' => 1,
        'INT3' => 1, 'INT4' => 1, 'INT8' => 1, 'INTO' => 1, 'JOIN' => 1,
        'KEYS' => 1, 'KILL' => 1, 'LEFT' => 1, 'LIKE' => 1, 'LOAD' => 1,
        'LOCK' => 1, 'LONG' => 1, 'LOOP' => 1, 'NULL' => 1, 'READ' => 1,
        'REAL' => 1, 'SHOW' => 1, 'THEN' => 1, 'TRUE' => 1, 'UNDO' => 1,
        'WHEN' => 1, 'WITH' => 1,
        'ALTER' => 1, 'CHECK' => 1, 'CROSS' => 1, 'FALSE' => 1, 'FETCH' => 1,
        'FLOAT' => 1, 'FORCE' => 1, 'GRANT' => 1, 'GROUP' => 1, 'INDEX' => 1,
        'INNER' => 1, 'INOUT' => 1, 'LABEL' => 1, 'LEAVE' => 1, 'LIMIT' => 1,
        'LINES' => 1, 'MATCH' => 1, 'ORDER' => 1, 'OUTER' => 1, 'PURGE' => 1,
        'RANGE' => 1, 'READS' => 1, 'RIGHT' => 1, 'RLIKE' => 1, 'TABLE' => 1,
        'UNION' => 1, 'USAGE' => 1, 'USING' => 1, 'WHERE' => 1, 'WHILE' => 1,
        'WRITE' => 1,
        'BEFORE' => 1, 'BIGINT' => 1, 'BINARY' => 1, 'CHANGE' => 1,
        'COLUMN' => 1, 'CREATE' => 1, 'CURSOR' => 1, 'DELETE' => 1,
        'DOUBLE' => 1, 'ELSEIF' => 1, 'EXISTS' => 1, 'FLOAT4' => 1,
        'FLOAT8' => 1, 'HAVING' => 1, 'IGNORE' => 1, 'INFILE' => 1,
        'INSERT' => 1, 'LINEAR' => 1, 'OPTION' => 1, 'REGEXP' => 1,
        'RENAME' => 1, 'REPEAT' => 1, 'RETURN' => 1, 'REVOKE' => 1,
        'SCHEMA' => 1, 'SELECT' => 1, 'SIGNAL' => 1, 'SONAME' => 1,
        'UNIQUE' => 1, 'UNLOCK' => 1, 'UPDATE' => 1, 'VALUES' => 1,
        'ANALYZE' => 1, 'BETWEEN' => 1, 'CASCADE' => 1, 'COLLATE' => 1,
        'CONVERT' => 1, 'DECIMAL' => 1, 'DECLARE' => 1, 'DEFAULT' => 1,
        'DELAYED' => 1, 'ESCAPED' => 1, 'EXPLAIN' => 1, 'FOREIGN' => 1,
        'INTEGER' => 1, 'ITERATE' => 1, 'LEADING' => 1, 'NATURAL' => 1,
        'NUMERIC' => 1, 'OUTFILE' => 1, 'PRIMARY' => 1, 'RELEASE' => 1,
        'REPLACE' => 1, 'REQUIRE' => 1, 'SCHEMAS' => 1, 'SPATIAL' => 1,
        'TINYINT' => 1, 'TRIGGER' => 1, 'UPGRADE' => 1, 'VARCHAR' => 1,
        'VARYING' => 1,
        'CONTINUE' => 1, 'DATABASE' => 1, 'DAY_HOUR' => 1, 'DESCRIBE' => 1,
        'DISTINCT' => 1, 'ENCLOSED' => 1, 'FULLTEXT' => 1, 'INTERVAL' => 1,
        'LONGBLOB' => 1, 'LONGTEXT' => 1, 'MAXVALUE' => 1, 'MODIFIES' => 1,
        'OPTIMIZE' => 1, 'RESIGNAL' => 1, 'RESTRICT' => 1, 'SMALLINT' => 1,
        'SPECIFIC' => 1, 'SQLSTATE' => 1, 'STARTING' => 1, 'TINYBLOB' => 1,
        'TINYTEXT' => 1, 'TRAILING' => 1, 'UNSIGNED' => 1, 'UTC_DATE' => 1,
        'UTC_TIME' => 1, 'ZEROFILL' => 1,
        'CHARACTER' => 1, 'CONDITION' => 1, 'DATABASES' => 1, 'LOCALTIME' => 1,
        'MEDIUMINT' => 1, 'MIDDLEINT' => 1, 'PARTITION' => 1, 'PRECISION' => 1,
        'PROCEDURE' => 1, 'SENSITIVE' => 1, 'SEPARATOR' => 1, 'VARBINARY' => 1,
        'ACCESSIBLE' => 1, 'ASENSITIVE' => 1, 'CONNECTION' => 1,
        'CONSTRAINT' => 1, 'DAY_MINUTE' => 1, 'DAY_SECOND' => 1,
        'MEDIUMBLOB' => 1, 'MEDIUMTEXT' => 1, 'OPTIONALLY' => 1,
        'READ_WRITE' => 1, 'REFERENCES' => 1, 'SQLWARNING' => 1,
        'TERMINATED' => 1, 'YEAR_MONTH' => 1,
        'DISTINCTROW' => 1, 'HOUR_MINUTE' => 1, 'HOUR_SECOND' => 1,
        'INSENSITIVE' => 1, 'MASTER_BIND' => 1,
        'CURRENT_DATE' => 1, 'CURRENT_TIME' => 1, 'CURRENT_USER' => 1,
        'LOW_PRIORITY' => 1, 'SQLEXCEPTION' => 1, 'VARCHARACTER' => 1,
        'DETERMINISTIC' => 1, 'HIGH_PRIORITY' => 1, 'MINUTE_SECOND' => 1,
        'STRAIGHT_JOIN' => 1, 'UTC_TIMESTAMP' => 1,
        'IO_AFTER_GTIDS' => 1, 'LOCALTIMESTAMP' => 1, 'SQL_BIG_RESULT' => 1,
        'DAY_MICROSECOND' => 1, 'IO_BEFORE_GTIDS' => 1, 'OPTIMIZER_COSTS' => 1,
        'HOUR_MICROSECOND' => 1, 'SQL_SMALL_RESULT' => 1,
        'CURRENT_TIMESTAMP' => 1,
        'MINUTE_MICROSECOND' => 1, 'NO_WRITE_TO_BINLOG' => 1,
        'SECOND_MICROSECOND' => 1,
        'SQL_CALC_FOUND_ROWS' => 1,
        'MASTER_SSL_VERIFY_SERVER_CERT' => 1,

        /*
         * Secondary group of keywords.
         *
         * Keywords below are either words that are used as keywords, but not
         * defined as proper keywords or are group of keywords which are parsed
         * easier when found grouped.
         */

        'END' => 2,
        'INDEX' => 2, 'VALUE' => 2,
        'ENGINE' => 2,
        'CHARSET' => 2, 'COMMENT' => 2, 'RETURNS' => 2, 'STORAGE' => 2,
        'CHECKSUM' => 2, 'MAX_ROWS' => 2, 'MIN_ROWS' => 2, 'NOT NULL' => 2,
        'PASSWORD' => 2,
        'INDEX KEY' => 2, 'PACK_KEYS' => 2,
        'ROW_FORMAT' => 2, 'TABLESPACE' => 2,
        'UNIQUE KEY' => 2,
        'FOREIGN KEY' => 2, 'PRIMARY KEY' => 2, 'PRIMARY KEY' => 2,
        'SPATIAL KEY' => 2,
        'FULLTEXT KEY' => 2, 'UNIQUE INDEX' => 2,
        'CHARACTER SET' => 2, 'IF NOT EXISTS' => 2, 'INSERT_METHOD' => 2,
        'SPATIAL INDEX' => 2,
        'AUTO_INCREMENT' => 2, 'AVG_ROW_LENGTH' => 2, 'DATA DIRECTORY' => 2,
        'FULLTEXT INDEX' => 2, 'KEY_BLOCK_SIZE' => 2,
        'DEFAULT COLLATE' => 2,
        'DELAY_KEY_WRITE' => 2, 'INDEX DIRECTORY' => 2,
        'DEFAULT CHARACTER SET' => 2,
    );

    // -------------------------------------------------------------------------
    // Keys and Data Types.

    /**
     * Types of keys.
     *
     * @var array
     */
    public static $KEY_TYPES = array(
        'FOREIGN KEY' => 1, 'FULLTEXT INDEX' => 1, 'FULLTEXT KEY' => 1,
        'INDEX KEY' => 1, 'INDEX' => 1, 'KEY' => 1, 'PRIMARY KEY' => 1,
        'SPATIAL INDEX' => 1, 'SPATIAL KEY' => 1, 'UNIQUE INDEX' => 1,
        'UNIQUE KEY' => 1, 'UNIQUE' => 1,
    );

    /**
     * All data types.
     *
     * @var array
     */
    public static $DATA_TYPES = array(
        'ARRAY' => 1, 'BIGINT' => 1, 'BINARY VARYING' => 1, 'BINARY' => 1,
        'BOOLEAN' => 1, 'CHARACTER' => 1, 'CHARACTER' => 1, 'DATE' => 1,
        'DECIMAL' => 1, 'DOUBLE' => 1, 'FLOAT' => 1, 'FLOAT' => 1, 'INT' => 1,
        'INTEGER' => 1, 'INTERVAL' => 1, 'MULTISET' => 1, 'NUMERIC' => 1,
        'REAL' => 1, 'SMALLINT' => 1, 'TIME' => 1, 'TIMESTAMP' => 1,
        'VARBINARY' => 1, 'VARCHAR' => 1, 'XML' => 1,
    );

    // -------------------------------------------------------------------------
    // Operators.

    /**
     * List of operators and their flags.
     *
     * @var array
     */
    public static $OPERATORS = array(

        // Some operators (*, =) may have ambigous flags, because they depend on
        // the context they are being used in.
        // For example: 1. SELECT * FROM table; # SQL specific (wildcard)
        //                 SELECT 2 * 3;        # arithmetic
        //              2. SELECT * FROM table WHERE foo = 'bar';
        //                 SET @i = 0;

        // @see Token::FLAG_OPERATOR_ARITHMETIC
        '%'   =>  1, '*'   =>  1, '+'    =>  1, '-'   =>  1, '/'   =>  1,

        // @see Token::FLAG_OPERATOR_LOGICAL
        '!'   =>  2, '!=='  =>  2, '&&'  =>  2, '<'   =>  2, '<='  =>  2,
        '<=>' =>  2, '<>'   =>  2, '='   =>  2, '>'   =>  2, '>='  =>  2,
        '||'  =>  2,

        // @see Token::FLAG_OPERATOR_BITWISE
        '&'   =>  4, '<<'   =>  4, '>>'  =>  4, '^'   =>  4, '|'   =>  4,
        '~'   =>  4,

        // @see Token::FLAG_OPERATOR_ASSIGNMENT
        ':='  =>  8,

        // @see Token::FLAG_OPERATOR_SQL
        '('   => 16, ')'    => 16, '.'   => 16,  ','  => 16,
    );

    // -------------------------------------------------------------------------
    // SQL Modes.

    /*
     * Server SQL Modes
     * https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html
     */

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_allow_invalid_dates
    const ALLOW_INVALID_DATES           =       1;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_ansi_quotes
    const ANSI_QUOTES                   =       2;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_error_for_division_by_zero
    const ERROR_FOR_DIVISION_BY_ZERO    =       4;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_high_not_precedence
    const HIGH_NOT_PRECEDENCE           =       8;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_ignore_space
    const IGNORE_SPACE                  =      16;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_auto_create_user
    const NO_AUTO_CREATE_USER           =      32;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_auto_value_on_zero
    const NO_AUTO_VALUE_ON_ZERO         =      64;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_backslash_escapes
    const NO_BACKSLASH_ESCAPES          =     128;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_dir_in_create
    const NO_DIR_IN_CREATE              =     256;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_dir_in_create
    const NO_ENGINE_SUBSTITUTION        =     512;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_field_options
    const NO_FIELD_OPTIONS              =    1024;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_key_options
    const NO_KEY_OPTIONS                =    2048;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_table_options
    const NO_TABLE_OPTIONS              =    4096;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_unsigned_subtraction
    const NO_UNSIGNED_SUBTRACTION       =    8192;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_zero_date
    const NO_ZERO_DATE                  =   16384;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_no_zero_in_date
    const NO_ZERO_IN_DATE               =   32768;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_only_full_group_by
    const ONLY_FULL_GROUP_BY            =   65536;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_pipes_as_concat
    const PIPES_AS_CONCAT               =  131072;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_real_as_float
    const REAL_AS_FLOAT                 =  262144;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_strict_all_tables
    const STRICT_ALL_TABLES             =  524288;

    // https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sqlmode_strict_trans_tables
    const STRICT_TRANS_TABLES           = 1048576;

    /*
     * Combination SQL Modes
     * https://dev.mysql.com/doc/refman/5.0/en/sql-mode.html#sql-mode-combo
     */

    // REAL_AS_FLOAT, PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE
    const SQL_MODE_ANSI                 = 393234;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS,
    const SQL_MODE_DB2                  = 138258;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS, NO_AUTO_CREATE_USER
    const SQL_MODE_MAXDB                = 138290;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS
    const SQL_MODE_MSSQL                = 138258;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS, NO_AUTO_CREATE_USER
    const SQL_MODE_ORACLE               = 138290;

    // PIPES_AS_CONCAT, ANSI_QUOTES, IGNORE_SPACE, NO_KEY_OPTIONS,
    // NO_TABLE_OPTIONS, NO_FIELD_OPTIONS
    const SQL_MODE_POSTGRESQL           = 138258;

    // STRICT_TRANS_TABLES, STRICT_ALL_TABLES, NO_ZERO_IN_DATE, NO_ZERO_DATE,
    // ERROR_FOR_DIVISION_BY_ZERO, NO_AUTO_CREATE_USER
    const SQL_MODE_TRADITIONAL          = 1622052;

    // -------------------------------------------------------------------------
    // Keyword.

    /**
     * Checks if the given string is a keyword.
     *
     * @param string $str
     * @param bool $strict
     *
     * @return bool
     */
    public static function isKeyword($str, $strict = false)
    {
        $str = strtoupper($str);

        if (isset(static::$KEYWORDS[$str])) {
            return $strict ? static::$KEYWORDS[$str] === 1 : true;
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Operator.

    /**
     * Checks if the given string is an operator.
     *
     * @param string $str
     *
     * @return int The appropriate flag for the operator.
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
     * @param string $ch
     *
     * @return bool
     */
    public static function isWhitespace($ch)
    {
        return ($ch === ' ') || ($ch === "\r") || ($ch === "\n") || ($ch === "\t");
    }

    // -------------------------------------------------------------------------
    // Comment.

    /**
     * Checks if the given string is the beginning of a whitespace.
     *
     * @param string $str
     *
     * @return int The appropriate flag for the comment type.
     */
    public static function isComment($str)
    {
        $len = strlen($str);
        if ($str[0] === '#') {
            return Token::FLAG_COMMENT_BASH;
        } elseif (($len > 1) && ((($str[0] === '/') && ($str[1] === '*')) ||
                                  (($str[0] === '*') && ($str[1] === '/')))) {
            return Token::FLAG_COMMENT_C;
        } elseif (($len > 2) && ($str[0] === '-') &&
            ($str[1] === '-') && ($str[2] !== "\n") &&
            (static::isWhitespace($str[2]))) {
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
     * @param string $ch
     *
     * @return bool
     */
    public static function isBool($ch)
    {
        $ch = strtoupper($ch);
        return ($ch === 'TRUE') || ($ch === 'FALSE');
    }

    // -------------------------------------------------------------------------
    // Number.

    /**
     * Checks if the given character can be a part of a number.
     *
     * @param string $ch
     *
     * @return bool
     */
    public static function isNumber($ch)
    {
        return (($ch >= '0') && ($ch <= '9')) || ($ch === '.') ||
            ($ch === '-') || ($ch === '+') || ($ch === 'e') || ($ch === 'E');
    }

    // -------------------------------------------------------------------------
    // Symbol.

    /**
     * Checks if the given character is the beginning of a symbol. A symbol
     * can be either a variable or a field name.
     *
     * @param string $ch
     *
     * @return int The appropriate flag for the symbol type.
     */
    public static function isSymbol($ch)
    {
        if ($ch[0] === '@') {
            return Token::FLAG_SYMBOL_VARIABLE;
        } elseif ($ch[0] === '`') {
            return Token::FLAG_SYMBOL_BACKTICK;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // String.

    /**
     * Checks if the given character is the beginning of a string.
     *
     * @param string $str
     *
     * @return int The appropriate flag for the string type.
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
     * Checks if the given character can be a separator for two lexems.
     *
     * @param string $ch
     *
     * @return bool
     */
    public static function isSeparator($ch)
    {
        return !ctype_alnum($ch) && $ch !== '_';
    }
}
