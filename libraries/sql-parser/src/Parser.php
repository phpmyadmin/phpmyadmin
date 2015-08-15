<?php

/**
 * Defines the parser of the library.
 *
 * This is one of the most important components, along with the lexer.
 *
 * @package SqlParser
 */

namespace {

    if (!function_exists('__')) {

        /**
         * Translates the given string.
         *
         * @param string $str String to be translated.
         *
         * @return string
         */
        function __($str)
        {
            return $str;
        }
    }
}

namespace SqlParser {

    use SqlParser\Exceptions\ParserException;
    use SqlParser\Statements\SelectStatement;
    use SqlParser\Statements\TransactionStatement;

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

            // MySQL Utility Statements
            'EXPLAIN'           => 'SqlParser\\Statements\\ExplainStatement',
            'DESCRIBE'          => 'SqlParser\\Statements\\ExplainStatement',
            'HELP'              => '',
            'USE'               => '',
            'STATUS'            => '',

            // Table Maintenance Statements
            // https://dev.mysql.com/doc/refman/5.7/en/table-maintenance-sql.html
            'ANALYZE'           => 'SqlParser\\Statements\\AnalyzeStatement',
            'BACKUP'            => 'SqlParser\\Statements\\BackupStatement',
            'CHECK'             => 'SqlParser\\Statements\\CheckStatement',
            'CHECKSUM'          => 'SqlParser\\Statements\\ChecksumStatement',
            'OPTIMIZE'          => 'SqlParser\\Statements\\OptimizeStatement',
            'REPAIR'            => 'SqlParser\\Statements\\RepairStatement',
            'RESTORE'           => 'SqlParser\\Statements\\RestoreStatement',

            // Database Administration Statements
            // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-server-administration.html
            'SET'               => 'SqlParser\\Statements\\SetStatement',
            'SHOW'              => 'SqlParser\\Statements\\ShowStatement',

            // Data Definition Statements.
            // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-definition.html
            'ALTER'             => 'SqlParser\\Statements\\AlterStatement',
            'CREATE'            => 'SqlParser\\Statements\\CreateStatement',
            'DROP'              => 'SqlParser\\Statements\\DropStatement',
            'RENAME'            => 'SqlParser\\Statements\\RenameStatement',
            'TRUNCATE'          => 'SqlParser\\Statements\\TruncateStatement',

            // Data Manipulation Statements.
            // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-manipulation.html
            'CALL'              => 'SqlParser\\Statements\\CallStatement',
            'DELETE'            => 'SqlParser\\Statements\\DeleteStatement',
            'DO'                => '',
            'HANDLER'           => '',
            'INSERT'            => 'SqlParser\\Statements\\InsertStatement',
            'LOAD'              => '',
            'REPLACE'           => 'SqlParser\\Statements\\ReplaceStatement',
            'SELECT'            => 'SqlParser\\Statements\\SelectStatement',
            'UPDATE'            => 'SqlParser\\Statements\\UpdateStatement',

            // Prepared Statements.
            // https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html
            'PREPARE'           => '',
            'EXECUTE'           => '',

            // Transactional and Locking Statements
            // https://dev.mysql.com/doc/refman/5.7/en/commit.html
            'START TRANSACTION' => 'SqlParser\\Statements\\TransactionStatement',
            'BEGIN'             => 'SqlParser\\Statements\\TransactionStatement',
            'COMMIT'            => 'SqlParser\\Statements\\TransactionStatement',
            'ROLLBACK'          => 'SqlParser\\Statements\\TransactionStatement',
        );

        /**
         * Array of classes that are used in parsing SQL components.
         *
         * @var array
         */
        public static $KEYWORD_PARSERS = array(

            // This is not a proper keyword and was added here to help the
            // formatter.
            'PARTITION BY'          => array(),
            'SUBPARTITION BY'       => array(),

            // This is not a proper keyword and was added here to help the
            // builder.
            '_OPTIONS'              => array(
                'class'             => 'SqlParser\\Components\\OptionsArray',
                'field'             => 'options',
            ),
            'UNION'                 => array(
                'class'             => 'SqlParser\\Components\\UnionKeyword',
                'field'             => 'union',
            ),

            // Actual clause parsers.
            'ALTER'                 => array(
                'class'             => 'SqlParser\\Components\\Expression',
                'field'             => 'table',
                'options'           => array('skipColumn' => true),
            ),
            'ANALYZE'               => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'BACKUP'                => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'CALL'                  => array(
                'class'             => 'SqlParser\\Components\\FunctionCall',
                'field'             => 'call',
            ),
            'CHECK'                 => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'CHECKSUM'              => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'DROP'                  => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'fields',
                'options'           => array('skipColumn' => true),
            ),
            'FROM'                  => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'from',
                'options'           => array('skipColumn' => true),
            ),
            'GROUP BY'              => array(
                'class'             => 'SqlParser\\Components\\OrderKeyword',
                'field'             => 'group',
            ),
            'HAVING'                => array(
                'class'             => 'SqlParser\\Components\\Condition',
                'field'             => 'having',
            ),
            'INTO'                  => array(
                'class'             => 'SqlParser\\Components\\IntoKeyword',
                'field'             => 'into',
            ),
            'JOIN'                  => array(
                'class'             => 'SqlParser\\Components\\JoinKeyword',
                'field'             => 'join',
            ),
            'LEFT JOIN'             => array(
                'class'             => 'SqlParser\\Components\\JoinKeyword',
                'field'             => 'join',
            ),
            'RIGHT JOIN'            => array(
                'class'             => 'SqlParser\\Components\\JoinKeyword',
                'field'             => 'join',
            ),
            'INNER JOIN'            => array(
                'class'             => 'SqlParser\\Components\\JoinKeyword',
                'field'             => 'join',
            ),
            'FULL JOIN'             => array(
                'class'             => 'SqlParser\\Components\\JoinKeyword',
                'field'             => 'join',
            ),
            'LIMIT'                 => array(
                'class'             => 'SqlParser\\Components\\Limit',
                'field'             => 'limit',
            ),
            'OPTIMIZE'              => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'ORDER BY'              => array(
                'class'             => 'SqlParser\\Components\\OrderKeyword',
                'field'             => 'order',
            ),
            'PARTITION'             => array(
                'class'             => 'SqlParser\\Components\\ArrayObj',
                'field'             => 'partition',
            ),
            'PROCEDURE'             => array(
                'class'             => 'SqlParser\\Components\\FunctionCall',
                'field'             => 'procedure',
            ),
            'RENAME'                => array(
                'class'             => 'SqlParser\\Components\\RenameOperation',
                'field'             => 'renames',
            ),
            'REPAIR'                => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'RESTORE'               => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'SET'                   => array(
                'class'             => 'SqlParser\\Components\\SetOperation',
                'field'             => 'set',
            ),
            'SELECT'                => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'expr',
            ),
            'TRUNCATE'              => array(
                'class'             => 'SqlParser\\Components\\Expression',
                'field'             => 'table',
                'options'           => array('skipColumn' => true),
            ),
            'UPDATE'                => array(
                'class'             => 'SqlParser\\Components\\ExpressionArray',
                'field'             => 'tables',
                'options'           => array('skipColumn' => true),
            ),
            'VALUE'                 => array(
                'class'             => 'SqlParser\\Components\\Array2d',
                'field'             => 'values',
            ),
            'VALUES'                => array(
                'class'             => 'SqlParser\\Components\\Array2d',
                'field'             => 'values',
            ),
            'WHERE'                 => array(
                'class'             => 'SqlParser\\Components\\Condition',
                'field'             => 'where',
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
         * @param string|UtfString|TokensList $list   The list of tokens to be parsed.
         * @param bool                        $strict Whether strict mode should be enabled or not.
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
             * Last transaction.
             *
             * @var TransactionStatement $lastTransaction
             */
            $lastTransaction = null;

            /**
             * Last parsed statement.
             *
             * @var Statement $lastStatement
             */
            $lastStatement = null;

            /**
             * Whether a union is parsed or not.
             *
             * @var bool $inUnion
             */
            $inUnion = false;

            /**
             * The index of the last token from the last statement.
             *
             * @var int $prevLastIdx
             */
            $prevLastIdx = -1;

            /**
             * The list of tokens.
             *
             * @var TokensList $list
             */
            $list = &$this->list;

            for (; $list->idx < $list->count; ++$list->idx) {

                /**
                 * Token parsed at this moment.
                 *
                 * @var Token $token
                 */
                $token = $list->tokens[$list->idx];

                // `DELIMITER` is not an actual statement and it requires
                // special handling.
                if (($token->type === Token::TYPE_NONE)
                    && (strtoupper($token->token) === 'DELIMITER')
                ) {
                    // Skipping to the end of this statement.
                    $list->getNextOfType(Token::TYPE_DELIMITER);
                    $prevLastIdx = $list->idx;
                    continue;
                }

                // Statements can start with keywords only.
                // Comments, whitespaces, etc. are ignored.
                if ($token->type !== Token::TYPE_KEYWORD) {
                    if (($token->type !== TOKEN::TYPE_COMMENT)
                        && ($token->type !== Token::TYPE_WHITESPACE)
                        && ($token->type !== Token::TYPE_OPERATOR) // `(` and `)`
                        && ($token->type !== Token::TYPE_DELIMITER)
                    ) {
                        $this->error(
                            __('Unexpected beginning of statement.'),
                            $token
                        );
                    }
                    continue;
                }

                if ($token->value === 'UNION') {
                    $inUnion = true;
                    continue;
                }

                // Checking if it is a known statement that can be parsed.
                if (empty(static::$STATEMENT_PARSERS[$token->value])) {
                    if (!isset(static::$STATEMENT_PARSERS[$token->value])) {
                        // A statement is considered recognized if the parser
                        // is aware that it is a statement, but it does not have
                        // a parser for it yet.
                        $this->error(
                            __('Unrecognized statement type.'),
                            $token
                        );
                    }
                    // Skipping to the end of this statement.
                    $list->getNextOfType(Token::TYPE_DELIMITER);
                    $prevLastIdx = $list->idx;
                    continue;
                }

                /**
                 * The name of the class that is used for parsing.
                 *
                 * @var string $class
                 */
                $class = static::$STATEMENT_PARSERS[$token->value];

                /**
                 * Processed statement.
                 *
                 * @var Statement $statement
                 */
                $statement = new $class($this, $this->list);

                // The first token that is a part of this token is the next token
                // unprocessed by the previous statement.
                // There might be brackets around statements and this shouldn't
                // affect the parser
                $statement->first = $prevLastIdx + 1;

                // Storing the index of the last token parsed and updating the old
                // index.
                $statement->last = $list->idx;
                $prevLastIdx = $list->idx;

                // Handles unions.
                if (($inUnion)
                    && ($lastStatement instanceof SelectStatement)
                    && ($statement instanceof SelectStatement)
                ) {

                    /**
                     * This SELECT statement.
                     *
                     * @var SelectStatement $statement
                     */

                    /**
                     * Last SELECT statement.
                     *
                     * @var SelectStatement $lastStatement
                     */
                    $lastStatement->union[] = $statement;

                    // if there are no no delimiting brackets, the `ORDER` and
                    // `LIMIT` keywords actually belong to the first statement.
                    $lastStatement->order = $statement->order;
                    $lastStatement->limit = $statement->limit;
                    $statement->order = array();
                    $statement->limit = null;

                    // The statement actually ends where the last statement in
                    // union ends.
                    $lastStatement->last = $statement->last;

                    $inUnion = false;
                    continue;
                }

                // Handles transactions.
                if ($statement instanceof TransactionStatement) {

                    /**
                     * @var TransactionStatement $statement
                     */
                    if ($statement->type === TransactionStatement::TYPE_BEGIN) {
                        $lastTransaction = $statement;
                        $this->statements[] = $statement;
                    } elseif ($statement->type === TransactionStatement::TYPE_END) {
                        if ($lastTransaction === null) {
                            // Even though an error occurred, the query is being
                            // saved.
                            $this->statements[] = $statement;
                            $this->error(
                                __('No transaction was previously started.'),
                                $token
                            );
                        } else {
                            $lastTransaction->end = $statement;
                        }
                        $lastTransaction = null;
                    }
                    continue;
                }

                // Finally, storing the statement.
                if ($lastTransaction !== null) {
                    $lastTransaction->statements[] = $statement;
                } else {
                    $this->statements[] = $statement;
                }
                $lastStatement = $statement;

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
}
