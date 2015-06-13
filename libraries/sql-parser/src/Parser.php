<?php

namespace SqlParser;

use SqlParser\Exceptions\ParserException;

/**
 * Takes multiple tokens (contained in a Lexer instance) as input and builds a
 * parse tree.
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

        // Meta-keywords.
        /*
        '_ARRAY'        => 'SqlParser\\Fragments\\ArrayFragment',
        '_CALL'         => 'SqlParser\\Fragments\\CallKeyword',
        '_CREATE_DEF'   => 'SqlParser\\Fragments\\CreateDefFragment',
        '_DATA_TYPE'    => 'SqlParser\\Fragments\\DataTypeFragment',
        '_FIELD'        => 'SqlParser\\Fragments\\FieldFragment',
        '_FIELD_DEF'    => 'SqlParser\\Fragments\\FieldDefFragment',
        '_PARAM_DEF'    => 'SqlParser\\Fragments\\ParamDefFragment',
        '_OPTIONS'      => 'SqlParser\\Fragments\\OptionsFragment',
        '_RENAME'       => 'SqlParser\\Fragments\\RenameKeyword',
        '_SELECT'       => 'SqlParser\\Fragments\\SelectKeyword',
        */

        'FROM'          => 'SqlParser\\Fragments\\FromKeyword',
        'GROUP'         => 'SqlParser\\Fragments\\OrderKeyword',
        'HAVING'        => 'SqlParser\\Fragments\\WhereKeyword',
        'INTO'          => 'SqlParser\\Fragments\\IntoKeyword',
        'JOIN'          => 'SqlParser\\Fragments\\JoinKeyword',
        'LIMIT'         => 'SqlParser\\Fragments\\LimitKeyword',
        'ORDER'         => 'SqlParser\\Fragments\\OrderKeyword',
        'PARTITION'     => 'SqlParser\\Fragments\\ArrayFragment',
        'SET'           => 'SqlParser\\Fragments\\SetKeyword',
        'VALUE'         => 'SqlParser\\Fragments\\ValuesKeyword',
        'VALUES'        => 'SqlParser\\Fragments\\ValuesKeyword',
        'WHERE'         => 'SqlParser\\Fragments\\WhereKeyword',

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
     * @param mixed $list
     * @param bool $strict
     */
    public function __construct($list, $strict = false)
    {
        if ((is_string($list)) || ($list instanceof UtfString)) {
            $lexer = new Lexer($list, $strict);
            $this->list = $lexer->tokens;
        } elseif ($list instanceof TokensList) {
            $this->list = $list;
        }
        $this->strict = $strict;
        $this->parse();
    }

    /**
     * Builds the parse trees.
     */
    public function parse()
    {
        $tokens = &$this->list->tokens;
        $count = &$this->list->count;
        $last = &$this->list->idx;

        for (; $last < $count; ++$last) {
            /** @var Token Token parsed at this moment. */
            $token = $tokens[$last];

            // Statements can start with keywords only.
            // Comments, whitespaces, etc. are ignored.
            if ($token->type !== Token::TYPE_KEYWORD) {
                continue;
            }

            // Checking if it is a known statement that can be parsed.
            if (empty(static::$STATEMENT_PARSERS[$token->value])) {
                $this->error(
                    'Unrecognized statement type "' . $token->value .'".',
                    $token
                );
                // TODO: Skip to first delimiter.
                continue;
            }

            /** @var string The name of the class that is used for parsing. */
            $class = static::$STATEMENT_PARSERS[$token->value];

            /** @var Statement Processed statement. */
            $stmt = new $class();

            $stmt->parse($this, $this->list);
            $this->statements[] = $stmt;
        }
    }

    /**
     * Creates a new error log.
     *
     * @param string $str
     * @param Token $token
     * @param int $code
     */
    public function error($str = '', Token $token = null, $code = 0)
    {
        $error = new ParserException($str, $token, $code);
        if ($this->strict) {
            throw $error;
        }
        $this->errors[] = $error;
    }
}
