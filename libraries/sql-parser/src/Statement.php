<?php

/**
 * The result of the parser is an array of statements are extensions of the
 * class defined here.
 *
 * A statement represents the result of parsing the lexemes.
 */

namespace SqlParser;

use SqlParser\Components\OptionsArray;

/**
 * Abstract statement definition.
 *
 * @category Statements
 *
 * @license  https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
abstract class Statement
{
    /**
     * Options for this statement.
     *
     * The option would be the key and the value can be an integer or an array.
     *
     * The integer represents only the index used.
     *
     * The array may have two keys: `0` is used to represent the index used and
     * `1` is the type of the option (which may be 'var' or 'var='). Both
     * options mean they expect a value after the option (e.g. `A = B` or `A B`,
     * in which case `A` is the key and `B` is the value). The only difference
     * is in the building process. `var` options are built as `A B` and  `var=`
     * options are built as `A = B`
     *
     * Two options that can be used together must have different values for
     * indexes, else, when they will be used together, an error will occur.
     *
     * @var array
     */
    public static $OPTIONS = array();

    /**
     * The clauses of this statement, in order.
     *
     * The value attributed to each clause is used by the builder and it may
     * have one of the following values:
     *
     *     - 1 = 01 - add the clause only
     *     - 2 = 10 - add the keyword
     *     - 3 = 11 - add both the keyword and the clause
     *
     * @var array
     */
    public static $CLAUSES = array();

    /**
     * The options of this query.
     *
     * @var OptionsArray
     *
     * @see static::$OPTIONS
     */
    public $options;

    /**
     * The index of the first token used in this statement.
     *
     * @var int
     */
    public $first;

    /**
     * The index of the last token used in this statement.
     *
     * @var int
     */
    public $last;

    /**
     * Constructor.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function __construct(Parser $parser = null, TokensList $list = null)
    {
        if (($parser !== null) && ($list !== null)) {
            $this->parse($parser, $list);
        }
    }

    /**
     * Builds the string representation of this statement.
     *
     * @return string
     */
    public function build()
    {
        /**
         * Query to be returned.
         *
         * @var string
         */
        $query = '';

        /**
         * Clauses which were built already.
         *
         * It is required to keep track of built clauses because some fields,
         * for example `join` is used by multiple clauses (`JOIN`, `LEFT JOIN`,
         * `LEFT OUTER JOIN`, etc.). The same happens for `VALUE` and `VALUES`.
         *
         * A clause is considered built just after fields' value
         * (`$this->field`) was used in building.
         *
         * @var array
         */
        $built = array();

        /**
         * Statement's clauses.
         *
         * @var array
         */
        $clauses = $this->getClauses();

        foreach ($clauses as $clause) {
            /**
             * The name of the clause.
             *
             * @var string
             */
            $name = $clause[0];

            /**
             * The type of the clause.
             *
             * @see self::$CLAUSES
             *
             * @var int
             */
            $type = $clause[1];

            /**
             * The builder (parser) of this clause.
             *
             * @var Component
             */
            $class = Parser::$KEYWORD_PARSERS[$name]['class'];

            /**
             * The name of the field that is used as source for the builder.
             * Same field is used to store the result of parsing.
             *
             * @var string
             */
            $field = Parser::$KEYWORD_PARSERS[$name]['field'];

            // The field is empty, there is nothing to be built.
            if (empty($this->$field)) {
                continue;
            }

            // Checking if this field was already built.
            if ($type & 1) {
                if (!empty($built[$field])) {
                    continue;
                }
                $built[$field] = true;
            }

            // Checking if the name of the clause should be added.
            if ($type & 2) {
                $query .= $name . ' ';
            }

            // Checking if the result of the builder should be added.
            if ($type & 1) {
                $query .= $class::build($this->$field) . ' ';
            }
        }

        return $query;
    }

    /**
     * Parses the statements defined by the tokens list.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     */
    public function parse(Parser $parser, TokensList $list)
    {
        /**
         * Array containing all list of clauses parsed.
         * This is used to check for duplicates.
         *
         * @var array
         */
        $parsedClauses = array();

        // This may be corrected by the parser.
        $this->first = $list->idx;

        /**
         * Whether options were parsed or not.
         * For statements that do not have any options this is set to `true` by
         * default.
         *
         * @var bool
         */
        $parsedOptions = empty(static::$OPTIONS);

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Checking if this closing bracket is the pair for a bracket
            // outside the statement.
            if (($token->value === ')') && ($parser->brackets > 0)) {
                --$parser->brackets;
                continue;
            }

            // Only keywords are relevant here. Other parts of the query are
            // processed in the functions below.
            if ($token->type !== Token::TYPE_KEYWORD) {
                if (($token->type !== TOKEN::TYPE_COMMENT)
                    && ($token->type !== Token::TYPE_WHITESPACE)
                ) {
                    $parser->error(__('Unexpected token.'), $token);
                }
                continue;
            }

            // Unions are parsed by the parser because they represent more than
            // one statement.
            if (($token->value === 'UNION') || ($token->value === 'UNION ALL') || ($token->value === 'UNION DISTINCT')) {
                break;
            }

            $lastIdx = $list->idx;

            // ON DUPLICATE KEY UPDATE ...
            // has to be parsed in parent statement (INSERT or REPLACE)
            // so look for it and break
            if (get_class($this) === 'SqlParser\Statements\SelectStatement'
                && $token->value === 'ON'
            ) {
                ++$list->idx; // Skip ON

                // look for ON DUPLICATE KEY UPDATE
                $first = $list->getNextOfType(Token::TYPE_KEYWORD);
                $second = $list->getNextOfType(Token::TYPE_KEYWORD);
                $third = $list->getNextOfType(Token::TYPE_KEYWORD);

                if ($first && $second && $third
                    && $first->value === 'DUPLICATE'
                    && $second->value === 'KEY'
                    && $third->value === 'UPDATE'
                ) {
                    $list->idx = $lastIdx;
                    break;
                }
            }
            $list->idx = $lastIdx;

            /**
             * The name of the class that is used for parsing.
             *
             * @var Component
             */
            $class = null;

            /**
             * The name of the field where the result of the parsing is stored.
             *
             * @var string
             */
            $field = null;

            /**
             * Parser's options.
             *
             * @var array
             */
            $options = array();

            // Looking for duplicated clauses.
            if ((!empty(Parser::$KEYWORD_PARSERS[$token->value]))
                || (!empty(Parser::$STATEMENT_PARSERS[$token->value]))
            ) {
                if (!empty($parsedClauses[$token->value])) {
                    $parser->error(
                        __('This type of clause was previously parsed.'),
                        $token
                    );
                    break;
                }
                $parsedClauses[$token->value] = true;
            }

            // Checking if this is the beginning of a clause.
            if (!empty(Parser::$KEYWORD_PARSERS[$token->value])) {
                $class = Parser::$KEYWORD_PARSERS[$token->value]['class'];
                $field = Parser::$KEYWORD_PARSERS[$token->value]['field'];
                if (!empty(Parser::$KEYWORD_PARSERS[$token->value]['options'])) {
                    $options = Parser::$KEYWORD_PARSERS[$token->value]['options'];
                }
            }

            // Checking if this is the beginning of the statement.
            if (!empty(Parser::$STATEMENT_PARSERS[$token->value])) {
                if ((!empty(static::$CLAUSES)) // Undefined for some statements.
                    && (empty(static::$CLAUSES[$token->value]))
                ) {
                    // Some keywords (e.g. `SET`) may be the beginning of a
                    // statement and a clause.
                    // If such keyword was found and it cannot be a clause of
                    // this statement it means it is a new statement, but no
                    // delimiter was found between them.
                    $parser->error(
                        __('A new statement was found, but no delimiter between it and the previous one.'),
                        $token
                    );
                    break;
                }
                if (!$parsedOptions) {
                    if (empty(static::$OPTIONS[$token->value])) {
                        // Skipping keyword because if it is not a option.
                        ++$list->idx;
                    }
                    $this->options = OptionsArray::parse(
                        $parser,
                        $list,
                        static::$OPTIONS
                    );
                    $parsedOptions = true;
                }
            } elseif ($class === null) {
                // Handle special end options in Select statement
                // See Statements\SelectStatement::$END_OPTIONS
                if (get_class($this) === 'SqlParser\Statements\SelectStatement'
                    && ($token->value === 'FOR UPDATE'
                    || $token->value === 'LOCK IN SHARE MODE')
                ) {
                    $this->end_options = OptionsArray::parse(
                        $parser,
                        $list,
                        static::$END_OPTIONS
                    );
                } else {
                    // There is no parser for this keyword and isn't the beginning
                    // of a statement (so no options) either.
                    $parser->error(__('Unrecognized keyword.'), $token);
                    continue;
                }
            }

            $this->before($parser, $list, $token);

            // Parsing this keyword.
            if ($class !== null) {
                ++$list->idx; // Skipping keyword or last option.
                $this->$field = $class::parse($parser, $list, $options);
            }

            $this->after($parser, $list, $token);
        }

        // This may be corrected by the parser.
        $this->last = --$list->idx; // Go back to last used token.
    }

    /**
     * Function called before the token is processed.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     * @param Token      $token  the token that is being parsed
     */
    public function before(Parser $parser, TokensList $list, Token $token)
    {
    }

    /**
     * Function called after the token was processed.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     * @param Token      $token  the token that is being parsed
     */
    public function after(Parser $parser, TokensList $list, Token $token)
    {
    }

    /**
     * Gets the clauses of this statement.
     *
     * @return array
     */
    public function getClauses()
    {
        return static::$CLAUSES;
    }

    /**
     * Builds the string representation of this statement.
     *
     * @see static::build
     *
     * @return string
     */
    public function __toString()
    {
        return $this->build();
    }

    /**
     * Validates the order of the clauses in parsed statement
     * Ideally this should be called after successfully
     * completing the parsing of each statement.
     *
     * @param Parser     $parser the instance that requests parsing
     * @param TokensList $list   the list of tokens to be parsed
     *
     * @return bool
     */
    public function validateClauseOrder($parser, $list)
    {
        $clauses = array_flip(array_keys($this->getClauses()));

        if (empty($clauses)
            || count($clauses) == 0
        ) {
            return true;
        }

        $minIdx = -1;

        /**
         * For tracking JOIN clauses in a query
         *   0 - JOIN not found till now
         *   1 - JOIN has been found
         *   2 - A Non-JOIN clause has been found
         *       after a previously found JOIN clause.
         *
         * @var int
         */
        $joinStart = 0;

        $error = 0;
        foreach ($clauses as $clauseType => $index) {
            $clauseStartIdx = Utils\Query::getClauseStartOffset(
                $this,
                $list,
                $clauseType
            );

            // Handle ordering of Multiple Joins in a query
            if ($clauseStartIdx != -1) {
                if ($joinStart == 0 && stripos($clauseType, 'JOIN') !== false) {
                    $joinStart = 1;
                } elseif ($joinStart == 1 && stripos($clauseType, 'JOIN') === false) {
                    $joinStart = 2;
                } elseif ($joinStart == 2 && stripos($clauseType, 'JOIN') !== false) {
                    $error = 1;
                }
            }

            if ($clauseStartIdx != -1 && $clauseStartIdx < $minIdx) {
                if ($joinStart == 0 || ($joinStart == 2 && $error = 1)) {
                    $token = $list->tokens[$clauseStartIdx];
                    $parser->error(
                        __('Unexpected ordering of clauses.'),
                        $token
                    );

                    return false;
                } else {
                    $minIdx = $clauseStartIdx;
                }
            } elseif ($clauseStartIdx != -1) {
                $minIdx = $clauseStartIdx;
            }
        }

        return true;
    }
}
