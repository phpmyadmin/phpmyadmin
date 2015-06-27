<?php

/**
 * Defines the lexer of the library.
 *
 * This is one of the most important components, along with the parser.
 *
 * Depends on context to extract lexemes.
 *
 * @package SqlParser
 */
namespace SqlParser;

use SqlParser\Exceptions\LexerException;

/**
 * Performs lexical analysis over a SQL statement and splits it in multiple
 * tokens.
 *
 * The output of the lexer is affected by the context of the SQL statement.
 *
 * @category Lexer
 * @package  SqlParser
 * @author   Dan Ungureanu <udan1107@gmail.com>
 * @license  http://opensource.org/licenses/GPL-2.0 GNU Public License
 * @see      Context
 */
class Lexer
{

    /**
     * A list of methods that are used in lexing the SQL query.
     *
     * @var array
     */
    public static $PARSER_METHODS = array(

        // It is best to put the parsers in order of their complexity
        // (ascending) and their occurance rate (descending).
        //
        // Conflicts:
        //
        // 1. `parseDelimiter` and `parseUnknown`, `parseKeyword`, `parseNumber`
        // They fight over delimiter. The delimiter may be a keyword, a number
        // or almost any character which makes the delimiter one of the first
        // tokens that must be parsed.
        //
        // 1. `parseNumber` and `parseOperator`
        // They fight over `+` and `-`.
        //
        // 2. `parseComment` and `parseOperator`
        // They fight over `/` (as in ```/*comment*/``` or ```a / b```)
        //
        // 3. `parseBool` and `parseKeyword`
        // They fight over `TRUE` and `FALSE`.
        //
        // 4. `parseKeyword` and `parseUnknown`
        // They fight over words. `parseUnknown` does not know about keywords.

        'parseDelimiter', 'parseWhitespace', 'parseNumber', 'parseComment',
        'parseOperator', 'parseBool', 'parseString', 'parseSymbol',
        'parseKeyword', 'parseUnknown'
    );

    /**
     * Whether errors should throw exceptions or just be stored.
     *
     * @var bool
     *
     * @see static::$errors
     */
    public $strict = false;

    /**
     * The string to be parsed.
     *
     * @var string|UtfString
     */
    public $str = '';

    /**
     * The length of `$str`.
     *
     * By storing its length, a lot of time is saved, because parsing methods
     * would call `strlen` everytime.
     *
     * @var int
     */
    public $len = 0;

    /**
     * The index of the last parsed character.
     *
     * @var int
     */
    public $last = 0;

    /**
     * Tokens extracted from given strings.
     *
     * @var TokensList
     */
    public $list;

    /**
     * Statements delimiter.
     *
     * @var string
     */
    public $delimiter = ';';

    /**
     * The length of the delimiter.
     *
     * Because `parseDelimter` can be called a lot, it would perform a lot of
     * calls to `strlen`, which might affect performance when the delimiter is
     * big.
     *
     * @var int
     */
    public $delimiterLen = 1;

    /**
     * List of errors that occured during lexing.
     *
     * Usually, the lexing does not stop once an error occured because that
     * error might be misdetected or a partial result (even a bad one) might be
     * needed.
     *
     * @var LexerException[]
     *
     * @see Lexer::error()
     */
    public $errors = array();

    /**
     * Constructor.
     *
     * @param string|UtfString $str    The query to be lexed.
     * @param bool             $strict Whether strict mode should be enabled or not.
     */
    public function __construct($str, $strict = false)
    {
        $this->str = $str;
        $this->len = ($str instanceof UtfString) ?
            $str->length() : strlen($str);
        $this->strict = $strict;
        $this->lex();
    }

    /**
     * Parses the string and extracts lexems.
     *
     * @return void
     */
    public function lex()
    {
        // TODO: Sometimes, static::parse* functions make unnecessary calls to
        // is* functions. For a better performance, some rules can be deduced
        // from context.
        // For example, in `parseBool` there is no need to compare the token
        // every time with `true` and `false`. The first step would be to
        // compare with 'true' only and just after that add another letter from
        // context and compare again with `false`.
        // Another example is `parseComment`.

        $list = new TokensList();
        $lastToken = null;

        for ($this->last = 0, $lastIdx = 0; $this->last < $this->len; $lastIdx = ++$this->last) {

            /**
             * The new token.
             * @var Token
             */
            $token = null;

            foreach (static::$PARSER_METHODS as $method) {
                if (($token = $this->$method())) {
                    break;
                }
            }

            if ($token === null) {
                // @assert($this->last === $lastIdx);
                $token = new Token($this->str[$this->last]);
                $this->error('Unexpected character.', $this->str[$this->last], $this->last);
            } elseif (($token->type === Token::TYPE_SYMBOL)
                && ($token->flags & Token::FLAG_SYMBOL_VARIABLE)
                && ($lastToken !== null)
            ) {
                // Handles ```... FROM 'user'@'%' ...```.
                if ((($lastToken->type === Token::TYPE_SYMBOL)
                    && ($lastToken->flags & Token::FLAG_SYMBOL_BACKTICK))
                    || ($lastToken->type === Token::TYPE_STRING)
                ) {
                    $lastToken->token .= $token->token;
                    $lastToken->type = Token::TYPE_SYMBOL;
                    $lastToken->flags = Token::FLAG_SYMBOL_USER;
                    $lastToken->value .= '@' . $token->value;
                    continue;
                }
            }
            $token->position = $lastIdx;

            $list->tokens[$list->count++] = $token;

            // Handling delimiters.
            if (($token->type === Token::TYPE_NONE) && ($token->value === 'DELIMITER')) {
                if ($this->last + 1 >= $this->len) {
                    $this->error('Expected whitespace(s) before delimiter.', '', $this->last + 1);
                    continue;
                }

                // Skipping last R (from `delimiteR`) and whitespaces between
                // the keyword `DELIMITER` and the actual delimiter.
                $pos = ++$this->last;
                if (($token = $this->parseWhitespace()) !== null) {
                    $token->position = $pos;
                    $list->tokens[$list->count++] = $token;
                }

                // Preparing the token that holds the new delimiter.
                if ($this->last + 1 >= $this->len) {
                    $this->error('Expected delimiter.', '', $this->last + 1);
                    continue;
                }
                $pos = $this->last + 1;

                // Parsing the delimiter.
                $this->delimiter = '';
                while ((++$this->last < $this->len) && (!Context::isWhitespace($this->str[$this->last]))) {
                    $this->delimiter .= $this->str[$this->last];
                }
                --$this->last;

                // Saving the delimiter and its token.
                $this->delimiterLen = strlen($this->delimiter);
                $token = new Token($this->delimiter, Token::TYPE_DELIMITER);
                $token->position = $pos;
                $list->tokens[$list->count++] = $token;
            }

            $lastToken = $token;
        }

        // Adding a final delimite at the end to mark the ending.
        $list->tokens[$list->count++] = new Token(null, Token::TYPE_DELIMITER);

        // Saving the tokens list.
        $this->list = $list;
    }

    /**
     * Creates a new error log.
     *
     * @param string $msg  The error message.
     * @param string $str  The character that produced the error.
     * @param int    $pos  The position of the character.
     * @param int    $code The code of the error.
     *
     * @return void
     */
    public function error($msg = '', $str = '', $pos = 0, $code = 0)
    {
        $error = new LexerException($msg, $str, $pos, $code);
        if ($this->strict) {
            throw $error;
        }
        $this->errors[] = $error;
    }

    /**
     * Parses a keyword.
     *
     * @return Token
     */
    public function parseKeyword()
    {
        $token = '';

        /**
         * Value to be returned.
         * @var Token
         */
        $ret = null;

        /**
         * The value of `$this->last` where `$token` ends in `$this->str`.
         * @var int
         */
        $iEnd = $this->last;

        for ($j = 1; $j < Context::KEYWORD_MAX_LENGTH && $this->last < $this->len; ++$j, ++$this->last) {
            $token .= $this->str[$this->last];
            if (($this->last + 1 === $this->len) || (Context::isSeparator($this->str[$this->last + 1]))) {
                if (($flags = Context::isKeyword($token))) {
                    $ret = new Token($token, Token::TYPE_KEYWORD, $flags);
                    $iEnd = $this->last;
                    // We don't break so we find longest keyword.
                    // For example, `OR` and `ORDER` have a common prefix `OR`.
                    // If we stopped at `OR`, the parsing would be invalid.
                }
            }
        }

        $this->last = $iEnd;
        return $ret;
    }

    /**
     * Parses an operator.
     *
     * @return Token
     */
    public function parseOperator()
    {
        $token = '';

        /**
         * Value to be returned.
         * @var Token|bool
         */
        $ret = null;

        /**
         * The value of `$this->last` where `$token` ends in `$this->str`.
         * @var int
         */
        $iEnd = $this->last;

        for ($j = 1; $j < Context::OPERATOR_MAX_LENGTH && $this->last < $this->len; ++$j, ++$this->last) {
            $token .= $this->str[$this->last];
            if ($flags = Context::isOperator($token)) {
                $ret = new Token($token, Token::TYPE_OPERATOR, $flags);
                $iEnd = $this->last;
            }
        }

        $this->last = $iEnd;
        return $ret;
    }

    /**
     * Parses a whitespace.
     *
     * @return Token
     */
    public function parseWhitespace()
    {
        $token = $this->str[$this->last];

        if (!Context::isWhitespace($token)) {
            return null;
        }

        while ((++$this->last < $this->len) && (Context::isWhitespace($this->str[$this->last]))) {
            $token .= $this->str[$this->last];
        }

        --$this->last;
        return new Token($token, Token::TYPE_WHITESPACE);
    }

    /**
     * Parses a comment.
     *
     * @return Token
     */
    public function parseComment()
    {
        $iBak = $this->last;
        $token = $this->str[$this->last];

        // Bash style comments. (#comment\n)
        if (Context::isComment($token)) {
            while ((++$this->last < $this->len) && ($this->str[$this->last] !== "\n")) {
                $token .= $this->str[$this->last];
            }
            $token .= $this->str[$this->last];
            return new Token($token, Token::TYPE_COMMENT, Token::FLAG_COMMENT_BASH);
        }

        // C style comments. (/*comment*\/)
        if (++$this->last < $this->len) {
            $token .= $this->str[$this->last];
            if (Context::isComment($token)) {
                $flags = Token::FLAG_COMMENT_C;
                if (($this->last + 1 < $this->len) && ($this->str[$this->last + 1] === '!')) {
                    // It is a MySQL-specific command.
                    $flags |= Token::FLAG_COMMENT_MYSQL_CMD;
                }
                while ((++$this->last < $this->len) &&
                    (($this->str[$this->last - 1] !== '*') || ($this->str[$this->last] !== '/'))) {
                    $token .= $this->str[$this->last];
                }
                $token .= $this->str[$this->last];
                return new Token($token, Token::TYPE_COMMENT, $flags);
            }
        }

        // SQL style comments. (-- comment\n)
        if (++$this->last < $this->len) {
            $token .= $this->str[$this->last];
            if (Context::isComment($token)) {
                if ($this->str[$this->last] !== "\n") {
                    // Checking if this comment did not end already (```--\n```).
                    while ((++$this->last < $this->len) && ($this->str[$this->last] !== "\n")) {
                        $token .= $this->str[$this->last];
                    }
                    if ($this->last < $this->len) {
                        $token .= $this->str[$this->last];
                    }
                }
                return new Token($token, Token::TYPE_COMMENT, Token::FLAG_COMMENT_SQL);
            }
        }

        $this->last = $iBak;
        return null;
    }

    /**
     * Parses a boolean.
     *
     * @return Token
     */
    public function parseBool()
    {
        if ($this->last + 3 >= $this->len) {
            // At least `min(strlen('TRUE'), strlen('FALSE'))` characters are
            // required.
            return null;
        }

        $iBak = $this->last;
        $token = $this->str[$this->last] . $this->str[++$this->last]
            . $this->str[++$this->last] . $this->str[++$this->last]; // _TRUE_ or _FALS_e

        if (Context::isBool($token)) {
            return new Token($token, Token::TYPE_BOOL);
        } elseif (++$this->last < $this->len) {
            $token .= $this->str[$this->last]; // fals_E_
            if (Context::isBool($token)) {
                return new Token($token, Token::TYPE_BOOL, 1);
            }
        }

        $this->last = $iBak;
        return null;
    }

    /**
     * Parses a number.
     *
     * @return Token
     */
    public function parseNumber()
    {
        // A rudimentary state machine is being used to parse numbers due to
        // the various forms of their notation.
        //
        // Below are the states of the machines and the conditions to change
        // the state.
        //
        //      1 ---------------------[ + or - ]---------------------> 1
        //      1 --------------------[ 0x or 0X ]--------------------> 2
        //      1 ---------------------[ 0 to 9 ]---------------------> 3
        //      1 ------------------------[ . ]-----------------------> 4
        //
        //      2 ---------------------[ 0 to F ]---------------------> 2
        //
        //      3 ---------------------[ 0 to 9 ]---------------------> 3
        //      3 ------------------------[ . ]-----------------------> 4
        //      3 ---------------------[ e or E ]---------------------> 5
        //
        //      4 ---------------------[ 0 to 9 ]---------------------> 4
        //      4 ---------------------[ e or E ]---------------------> 5
        //
        //      5 ----------------[ + or - or 0 to 9 ]----------------> 6
        //
        // State 1 may be reached by negative numbers.
        // State 2 is reached only by hex numbers.
        // State 4 is reached only by float numbers.
        // State 5 is reached only by numbers in approximate form.
        //
        // Valid final states are: 2, 3, 4 and 6. Any parsing that finished in a
        // state other than these is invalid.
        $iBak = $this->last;
        $token = '';
        $flags = 0;
        $state = 1;
        for (; $this->last < $this->len; ++$this->last) {
            if ($state === 1) {
                if ($this->str[$this->last] === '+') {
                    // Do nothing.
                } elseif ($this->str[$this->last] === '-') {
                    $flags |= Token::FLAG_NUMBER_NEGATIVE;
                    // Do nothing.
                } elseif (($this->str[$this->last] === '0') && ($this->last + 1 < $this->len)
                    && (($this->str[$this->last + 1] === 'x') || ($this->str[$this->last + 1] === 'X'))
                ) {
                    $token .= $this->str[$this->last++];
                    $state = 2;
                } elseif (($this->str[$this->last] >= '0') && ($this->str[$this->last] <= '9')) {
                    $state = 3;
                } elseif ($this->str[$this->last] === '.') {
                    $state = 4;
                } else {
                    break;
                }
            } elseif ($state === 2) {
                $flags |= Token::FLAG_NUMBER_HEX;
                if ((($this->str[$this->last] >= '0') && ($this->str[$this->last] <= '9'))
                    || (($this->str[$this->last] >= 'A') && ($this->str[$this->last] <= 'F'))
                    || (($this->str[$this->last] >= 'a') && ($this->str[$this->last] <= 'f'))
                ) {
                    // Do nothing.
                } else {
                    break;
                }
            } elseif ($state === 3) {
                if (($this->str[$this->last] >= '0') && ($this->str[$this->last] <= '9')) {
                    // Do nothing.
                } elseif ($this->str[$this->last] === '.') {
                    $state = 4;
                } elseif (($this->str[$this->last] === 'e') || ($this->str[$this->last] === 'E')) {
                    $state = 5;
                } else {
                    break;
                }
            } elseif ($state === 4) {
                $flags |= Token::FLAG_NUMBER_FLOAT;
                if (($this->str[$this->last] >= '0') && ($this->str[$this->last] <= '9')) {
                    // Do nothing.
                } elseif (($this->str[$this->last] === 'e') || ($this->str[$this->last] === 'E')) {
                    $state = 5;
                } else {
                    break;
                }
            } elseif ($state === 5) {
                $flags |= Token::FLAG_NUMBER_APPROXIMATE;
                if (($this->str[$this->last] === '+') || ($this->str[$this->last] === '-')
                    || ((($this->str[$this->last] >= '0') && ($this->str[$this->last] <= '9')))
                ) {
                    $state = 6;
                } else {
                    break;
                }
            } elseif ($state === 6) {
                if (($this->str[$this->last] >= '0') && ($this->str[$this->last] <= '9')) {
                    // Do nothing.
                } else {
                    break;
                }
            }
            $token .= $this->str[$this->last];
        }
        if (($state === 2) || ($state === 3) || (($token !== '.') && ($state === 4)) || ($state === 6)) {
            --$this->last;
            return new Token($token, Token::TYPE_NUMBER, $flags);
        }
        $this->last = $iBak;
        return null;
    }

    /**
     * Parses a string.
     *
     * @param string $quote Additional starting symbol.
     *
     * @return Token
     */
    public function parseString($quote = '')
    {
        $token = $this->str[$this->last];
        if ((!($flags = Context::isString($token))) && ($token !== $quote)) {
            return null;
        }
        $quote = $token;

        while (++$this->last < $this->len) {
            if (($this->last + 1 < $this->len)
                && ((($this->str[$this->last] === $quote) && ($this->str[$this->last + 1] === $quote))
                || (($this->str[$this->last] === '\\') && ($quote !== '`')))
            ) {
                $token .= $this->str[$this->last] . $this->str[++$this->last];
            } else {
                if ($this->str[$this->last] === $quote) {
                    break;
                }
                $token .= $this->str[$this->last];
            }
        }

        if (($this->last >= $this->len) || ($this->str[$this->last] !== $quote)) {
            $this->error('Ending quote ' . $quote . ' was expected.', '', $this->last);
        } else {
            $token .= $this->str[$this->last];
        }
        return new Token($token, Token::TYPE_STRING, $flags);
    }

    /**
     * Parses a symbol.
     *
     * @return Token
     */
    public function parseSymbol()
    {
        $token = $this->str[$this->last];
        if (!($flags = Context::isSymbol($token))) {
            return null;
        }

        if ($flags & Token::FLAG_SYMBOL_VARIABLE) {
            ++$this->last;
        } else {
            $token = '';
        }

        if (($str = $this->parseString('`')) === null) {
            if (($str = static::parseUnknown()) === null) {
                $this->error('Variable name was expected.', $this->str[$this->last], $this->last);
            }
        }

        if ($str !== null) {
            $token .= $str->token;
        }

        return new Token($token, Token::TYPE_SYMBOL, $flags);
    }

    /**
     * Parses unknown parts of the query.
     *
     * @return Token
     */
    public function parseUnknown()
    {
        $token = $this->str[$this->last];
        if (Context::isSeparator($token)) {
            return null;
        }
        while ((++$this->last < $this->len) && (!Context::isSeparator($this->str[$this->last]))) {
            $token .= $this->str[$this->last];
        }
        --$this->last;
        return new Token($token);
    }

    /**
     * Parses the delimiter of the query.
     *
     * @return Token
     */
    public function parseDelimiter()
    {
        $idx = 0;

        while ($idx < $this->delimiterLen) {
            if ($this->delimiter[$idx] !== $this->str[$this->last + $idx]) {
                return null;
            }
            ++$idx;
        }

        $this->last += $this->delimiterLen - 1;
        return new Token($this->delimiter, Token::TYPE_DELIMITER);
    }
}
