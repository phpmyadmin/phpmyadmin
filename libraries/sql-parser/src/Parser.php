<?php

/**
 * Defines the parser of the library.
 *
 * This is one of the most important components, along with the lexer.
 *
 * @package SqlParser
 */
namespace SqlParser;

use SqlParser\Statements\SelectStatement;
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

        'EXPLAIN'       => 'SqlParser\\Statements\\ExplainStatement',

        // Table Maintenance Statements
        // https://dev.mysql.com/doc/refman/5.7/en/table-maintenance-sql.html
        'ANALYZE'       => 'SqlParser\\Statements\\AnalyzeStatement',
        'BACKUP'        => 'SqlParser\\Statements\\BackupStatement',
        'CHECK'         => 'SqlParser\\Statements\\CheckStatement',
        'CHECKSUM'      => 'SqlParser\\Statements\\ChecksumStatement',
        'OPTIMIZE'      => 'SqlParser\\Statements\\OptimizeStatement',
        'REPAIR'        => 'SqlParser\\Statements\\RepairStatement',
        'RESTORE'       => 'SqlParser\\Statements\\RestoreStatement',

        // Database Administration Statements
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-server-administration.html
        'SET'           => '',
        'SHOW'          => 'SqlParser\\Statements\\ShowStatement',

        // Data Definition Statements.
        // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-definition.html
        'ALTER'         => 'SqlParser\\Statements\\AlterStatement',
        'CREATE'        => 'SqlParser\\Statements\\CreateStatement',
        'DROP'          => 'SqlParser\\Statements\\DropStatement',
        'RENAME'        => 'SqlParser\\Statements\\RenameStatement',
        'TRUNCATE'      => 'SqlParser\\Statements\\TruncateStatement',

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

        // This is not a proper keyword and was added here to help the builder.
        '_OPTIONS'      => array(
            'class'     => 'SqlParser\\Fragments\\OptionsFragment',
            'field'     => 'options',
        ),

        'ALTER'         => array(
            'class'     => 'SqlParser\\Fragments\\FieldFragment',
            'field'     => 'table',
            'options'   => array('skipColumn' => true),
        ),
        'ANALYZE'       => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
        ),
        'BACKUP'        => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
        ),
        'CALL'          => array(
            'class'     => 'SqlParser\\Fragments\\CallKeyword',
            'field'     => 'call',
        ),
        'CHECK'         => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
        ),
        'CHECKSUM'      => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
        ),
        'DROP'          => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'fields',
            'options'   => array('skipColumn' => true),
        ),
        'FROM'          => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'from',
            'options'   => array('skipColumn' => true),
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
        'OPTIMIZE'      => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
        ),
        'ORDER BY'      => array(
            'class'     => 'SqlParser\\Fragments\\OrderKeyword',
            'field'     => 'order',
        ),
        'PARTITION'     => array(
            'class'     => 'SqlParser\\Fragments\\ArrayFragment',
            'field'     => 'partition',
        ),
        'PROCEDURE'     => array(
            'class'     => 'SqlParser\\Fragments\\CallKeyword',
            'field'     => 'procedure',
        ),
        'RENAME'        => array(
            'class'     => 'SqlParser\\Fragments\\RenameKeyword',
            'field'     => 'renames',
        ),
        'REPAIR'        => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
        ),
        'RESTORE'       => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
        ),
        'SET'           => array(
            'class'     => 'SqlParser\\Fragments\\SetKeyword',
            'field'     => 'set',
        ),
        'SELECT'        => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'expr',
        ),
        'TRUNCATE'      => array(
            'class'     => 'SqlParser\\Fragments\\FieldFragment',
            'field'     => 'table',
            'options'   => array('skipColumn' => true),
        ),
        'UPDATE'        => array(
            'class'     => 'SqlParser\\Fragments\\FieldListFragment',
            'field'     => 'tables',
            'options'   => array('skipColumn' => true),
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
     * List of errors that occurred during parsing.
     *
     * Usually, the parsing does not stop once an error occurred because that
     * error might be a false positive or a partial result (even a bad one)
     * might be needed.
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
            $this->list = $lexer->list;
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

        /**
         * Last parsed statement.
         * @var Statement $lastStatement
         */
        $lastStatement = null;

        /**
         * Whether a union is parsed or not.
         * @var bool $inUnion
         */
        $inUnion = true;

        /**
         * The index of the last token from the last statement.
         * @var int $prevLastIdx
         */
        $prevLastIdx = -1;

        /**
         * The list of tokens.
         * @var TokensList $list
         */
        $list = &$this->list;

        for (; $list->idx < $list->count; ++$list->idx) {

            /**
             * Token parsed at this moment.
             * @var Token $token
             */
            $token = $list->tokens[$list->idx];

            // Statements can start with keywords only.
            // Comments, whitespaces, etc. are ignored.
            if ($token->type !== Token::TYPE_KEYWORD) {
                continue;
            }

            if ($token->value === 'UNION') {
                $inUnion = true;
                continue;
            }

            // Checking if it is a known statement that can be parsed.
            if (empty(static::$STATEMENT_PARSERS[$token->value])) {
                $this->error(
                    'Unrecognized statement type "' . $token->value . '".',
                    $token
                );
                // Skipping to the end of this statement.
                $list->getNextOfType(Token::TYPE_DELIMITER);
                //
                $prevLastIdx = $list->idx;
                continue;
            }

            /**
             * The name of the class that is used for parsing.
             * @var string $class
             */
            $class = static::$STATEMENT_PARSERS[$token->value];

            /**
             * Processed statement.
             * @var Statement $stmt
             */
            $stmt = new $class($this, $this->list);

            // The first token that is a part of this token is the next token
            // unprocessed by the previous statement.
            // There might be brackets around statements and this shouldn't
            // affect the parser
            $stmt->first = $prevLastIdx + 1;

            // Storing the index of the last token parsed and updating the old
            // index.
            $stmt->last = $list->idx;
            $prevLastIdx = $list->idx;

            // Finally, storing the statement.
            if (($inUnion)
                && ($lastStatement instanceof SelectStatement)
                && ($stmt instanceof SelectStatement)
            ) {
                $lastStatement->union[] = $stmt;
                $inUnion = false;
            } else {
                $this->statements[] = $stmt;
                $lastStatement = $stmt;
            }

        }
    }

    /**
     * Creates a new error log.
     *
     * @param string $msg   The error message.
     * @param Token  $token The token that produced the error.
     * @param int    $code  The code of the error.
     *
     * @throws ParserException Throws the exception, if strict mode is enabled.
     *
     * @return void
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
