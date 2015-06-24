<?php

namespace SqlParser;

use SqlParser\Exceptions\ParserException;

/**
 * Takes multiple tokens (contained in a Lexer instance) as input and builds a
 * parse tree.
 *
 * @category Parser
 * @package  SqlParser
 * @author   Dan Ungureanu <udan1107@gmail.com>
 * @license  http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Parser
{

    /**
     * Array of classes that are used in parsing the SQL statements.
     *
     * @var array
     */
    public static $STATEMENT_PARSERS = array(

        // Data Definition Statements.
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-definition.html
        'ALTER'         => '',
        'CREATE'        => 'SqlParser\\Statements\\CreateStatement',
        'DROP'          => '',
        'RENAME'        => 'SqlParser\\Statements\\RenameStatement',
        'TRUNCATE'      => '',

        // Data Manipulation Statements.
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-manipulation.html
        'CALL'          => 'SqlParser\\Statements\\CallStatement',
        'DELETE'        => 'SqlParser\\Statements\\DeleteStatement',
        'DO'            => '',
        'HANDLER'       => '',
        'INSERT'        => 'SqlParser\\Statements\\InsertStatement',
        'LOAD'          => '',
        'REPLACE'       => 'SqlParser\\Statements\\ReplaceStatement',
        'SELECT'        => 'SqlParser\\Statements\\SelectStatement',
        'UPDATE'        => 'SqlParser\\Statements\\UpdateStatement',

        // Prepared Statements.
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html
        'PREPARE'       => '',
        'EXECUTE'       => '',
    );

    /**
     * Array of classes that are used in parsing SQL fragments.
     *
     * @var array
     */
    public static $KEYWORD_PARSERS = array(

        'CALL'          => array(
            'class'     => 'SqlParser\\Fragments\\CallKeyword',
            'field'     => 'call',
        ),
        'FROM'          => array(
            'class'     => 'SqlParser\\Fragments\\FromKeyword',
            'field'     => 'from',
        ),
        'GROUP BY'      => array(
            'class'     => 'SqlParser\\Fragments\\OrderKeyword',
            'field'     => 'group',
        ),
        'HAVING'        => array(
            'class'     => 'SqlParser\\Fragments\\WhereKeyword',
            'field'     => 'having',
        ),
        'INTO'          => array(
            'class'     => 'SqlParser\\Fragments\\IntoKeyword',
            'field'     => 'into',
        ),
        'JOIN'          => array(
            'class'     => 'SqlParser\\Fragments\\JoinKeyword',
            'field'     => 'join',
        ),
        'LIMIT'         => array(
            'class'     => 'SqlParser\\Fragments\\LimitKeyword',
            'field'     => 'limit',
        ),
        'ORDER BY'      => array(
            'class'     => 'SqlParser\\Fragments\\OrderKeyword',
            'field'     => 'order',
        ),
        'PARTITION'     => array(
            'class'     => 'SqlParser\\Fragments\\ArrayFragment',
            'field'     => 'partition',
        ),
        'RENAME'        => array(
            'class'     => 'SqlParser\\Fragments\\RenameKeyword',
            'field'     => 'renames',
        ),
        'SET'           => array(
            'class'     => 'SqlParser\\Fragments\\SetKeyword',
            'field'     => 'set',
        ),
        'SELECT'        => array(
            'class'     => 'SqlParser\\Fragments\\SelectKeyword',
            'field'     => 'expr',
        ),
        'UPDATE'        => array(
            'class'     => 'SqlParser\\Fragments\\FromKeyword',
            'field'     => 'from',
        ),
        'VALUE'         => array(
            'class'     => 'SqlParser\\Fragments\\ValuesKeyword',
            'field'     => 'values',
        ),
        'VALUES'        => array(
            'class'     => 'SqlParser\\Fragments\\ValuesKeyword',
            'field'     => 'values',
        ),
        'WHERE'         => array(
            'class'     => 'SqlParser\\Fragments\\WhereKeyword',
            'field'     => 'where',
        ),

    );

    /**
     * The list of tokens that are parsed.
     *
     * @var TokensList
     */
    public $list;

    /**
     * Whether errors should throw exceptions or just be stored.
     *
     * @var bool
     *
     * @see static::$errors
     */
    public $strict = false;

    /**
     * List of errors that occured during parsing.
     *
     * Usually, the parsing does not stop once an error occured because that
     * error might be misdetected or a partial result (even a bad one) might be
     * needed.
     *
     * @var ParserException[]
     *
     * @see Parser::error()
     */
    public $errors = array();

    /**
     * List of statements parsed.
     *
     * @var Statement[]
     */
    public $statements = array();

    /**
     * Constructor.
     *
     * @param mixed $list   The list of tokens to be parsed.
     * @param bool  $strict Whether strict mode should be enabled or not.
     */
    public function __construct($list = null, $strict = false)
    {
        if ((is_string($list)) || ($list instanceof UtfString)) {
            $lexer = new Lexer($list, $strict);
            $this->list = $lexer->tokens;
        } elseif ($list instanceof TokensList) {
            $this->list = $list;
        }

        $this->strict = $strict;

        if ($list !== null) {
            $this->parse();
        }
    }

    /**
     * Builds the parse trees.
     *
     * @return void
     */
    public function parse()
    {
        $tokens = &$this->list->tokens;
        $count = &$this->list->count;
        $last = &$this->list->idx;

        for (; $last < $count; ++$last) {

            /**
             * Token parsed at this moment.
             * @var Token
             */
            $token = $tokens[$last];

            // Statements can start with keywords only.
            // Comments, whitespaces, etc. are ignored.
            if ($token->type !== Token::TYPE_KEYWORD) {
                continue;
            }

            // Checking if it is a known statement that can be parsed.
            if (empty(static::$STATEMENT_PARSERS[$token->value])) {
                $this->error(
                    'Unrecognized statement type "' . $token->value . '".',
                    $token
                );
                // TODO: Skip to first delimiter.
                continue;
            }

            /**
             * The name of the class that is used for parsing.
             * @var string
             */
            $class = static::$STATEMENT_PARSERS[$token->value];

            /**
             * Processed statement.
             * @var Statement
             */
            $stmt = new $class();

            $stmt->parse($this, $this->list);
            $this->statements[] = $stmt;
        }
    }

    /**
     * Creates a new error log.
     *
     * @param string $msg   The error message.
     * @param Token  $token The token that produced the error.
     * @param int    $code  The code of the error.
     */
    public function error($msg = '', Token $token = null, $code = 0)
    {
        $error = new ParserException($msg, $token, $code);
        if ($this->strict) {
            throw $error;
        }
        $this->errors[] = $error;
    }
}
