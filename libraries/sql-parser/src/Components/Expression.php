<?php

/**
 * Parses a reference to an expression (column, table or database name, function
 * call, mathematical expression, etc.).
 *
 * @package    SqlParser
 * @subpackage Components
 */
namespace SqlParser\Components;

use SqlParser\Context;
use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a reference to an expression (column, table or database name, function
 * call, mathematical expression, etc.).
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Expression extends Component
{

    /**
     * The name of this database.
     *
     * @var string
     */
    public $database;

    /**
     * The name of this table.
     *
     * @var string
     */
    public $table;

    /**
     * The name of the column.
     *
     * @var string
     */
    public $column;

    /**
     * The sub-expression.
     *
     * @var string
     */
    public $expr = '';

    /**
     * The alias of this expression.
     *
     * @var string
     */
    public $alias;

    /**
     * The name of the function.
     *
     * @var mixed
     */
    public $function;

    /**
     * The type of subquery.
     *
     * @var string
     */
    public $subquery;

    /**
     * Constructor.
     *
     * Syntax:
     *     new Expression('expr')
     *     new Expression('expr', 'alias')
     *     new Expression('database', 'table', 'column')
     *     new Expression('database', 'table', 'column', 'alias')
     *
     * If the database, table or column name is not required, pass an empty
     * string.
     *
     * @param string $database The name of the database or the the expression.
     *                          the the expression.
     * @param string $table    The name of the table or the alias of the expression.
     *                          the alias of the expression.
     * @param string $column   The name of the column.
     * @param string $alias    The name of the alias.
     */
    public function __construct($database = null, $table = null, $column = null, $alias = null)
    {
        if (($column === null) && ($alias === null)) {
            $this->expr = $database; // case 1
            $this->alias = $table; // case 2
        } else {
            $this->database = $database; // case 3
            $this->table = $table; // case 3
            $this->column = $column; // case 3
            $this->alias = $alias; // case 4
        }
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return Expression
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new Expression();

        /**
         * Whether current tokens make an expression or a table reference.
         *
         * @var bool $isExpr
         */
        $isExpr = false;

        /**
         * Whether a period was previously found.
         *
         * @var bool $dot
         */
        $dot = false;

        /**
         * Whether an alias is expected. Is 2 if `AS` keyword was found.
         *
         * @var int $alias
         */
        $alias = 0;

        /**
         * Counts brackets.
         *
         * @var int $brackets
         */
        $brackets = 0;

        /**
         * Keeps track of the previous token.
         * Possible values:
         *     string, if function was previously found;
         *     true, if opening bracket was previously found;
         *     null, in any other case.
         *
         * @var string|bool $prev
         */
        $prev = null;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
             * @var Token $token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                if (($isExpr) && (!$alias)) {
                    $ret->expr .= $token->token;
                }
                if (($alias === 0) && (empty($options['noAlias'])) && (!$isExpr) && (!$dot) && (!empty($ret->expr))) {
                    $alias = 1;
                }
                continue;
            }

            if (($token->type === Token::TYPE_KEYWORD)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ($token->value !== 'DUAL')
            ) {
                // Keywords may be found only between brackets.
                if ($brackets === 0) {
                    if ((empty($options['noAlias'])) && ($token->value === 'AS')) {
                        $alias = 2;
                        continue;
                    }
                    if (!($token->flags & Token::FLAG_KEYWORD_FUNCTION)) {
                        break;
                    }
                } elseif ($prev === true) {
                    if ((empty($ret->subquery) && (!empty(Parser::$STATEMENT_PARSERS[$token->value])))) {
                        // A `(` was previously found and this keyword is the
                        // beginning of a statement, so this is a subquery.
                        $ret->subquery = $token->value;
                    }
                }
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ((!empty($options['noBrackets']))
                    && (($token->value === '(') || ($token->value === ')'))
                ) {
                    break;
                }
                if ($token->value === '(') {
                    ++$brackets;
                    if ((empty($ret->function)) && ($prev !== null) && ($prev !== true)) {
                        // A function name was previously found and now an open
                        // bracket, so this is a function call.
                        $ret->function = $prev;
                    }
                    $isExpr = true;
                } elseif ($token->value === ')') {
                    --$brackets;
                    if ($brackets === 0) {
                        if (!empty($options['bracketsDelimited'])) {
                            // The current token is the last brackets, the next
                            // one will be outside.
                            $ret->expr .= $token->token;
                            ++$list->idx;
                            break;
                        }
                    } elseif ($brackets < 0) {
                        $parser->error(__('Unexpected closing bracket.'), $token);
                        $brackets = 0;
                    }
                } elseif ($token->value === ',') {
                    if ($brackets === 0) {
                        break;
                    }
                }
            }

            if (($token->type === Token::TYPE_NUMBER) || ($token->type === Token::TYPE_BOOL)
                || (($token->type === Token::TYPE_SYMBOL) && ($token->flags & Token::FLAG_SYMBOL_VARIABLE))
                || (($token->type === Token::TYPE_OPERATOR)) && ($token->value !== '.')
            ) {
                // Numbers, booleans and operators are usually part of expressions.
                $isExpr = true;
            }

            if ($alias) {
                // An alias is expected (the keyword `AS` was previously found).
                if (!empty($ret->alias)) {
                    $parser->error(__('An alias was previously found.'), $token);
                }
                $ret->alias = $token->value;
                $alias = 0;
            } else {
                if (!$isExpr) {
                    if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '.')) {
                        // Found a `.` which means we expect a column name and
                        // the column name we parsed is actually the table name
                        // and the table name is actually a database name.
                        if ((!empty($ret->database)) || ($dot)) {
                            $parser->error(__('Unexpected dot.'), $token);
                        }
                        $ret->database = $ret->table;
                        $ret->table = $ret->column;
                        $ret->column = null;
                        $dot = true;
                    } else {
                        // We found the name of a column (or table if column
                        // field should be skipped; used to parse table names).
                        $field = (!empty($options['skipColumn'])) ? 'table' : 'column';
                        if (!empty($ret->$field)) {
                            // No alias is expected.
                            if (!empty($options['noAlias'])) {
                                break;
                            }

                            // Parsing aliases without `AS` keyword and any
                            // whitespace.
                            // Example: SELECT 1`foo`
                            if (($token->type === Token::TYPE_STRING)
                                || (($token->type === Token::TYPE_SYMBOL)
                                && ($token->flags & Token::FLAG_SYMBOL_BACKTICK))
                            ) {
                                if (!empty($ret->alias)) {
                                    $parser->error(
                                        __('An alias was previously found.'),
                                        $token
                                    );
                                }
                                $ret->alias = $token->value;
                            }
                        } else {
                            $ret->$field = $token->value;
                        }
                        $dot = false;
                    }
                } else {
                    // Parsing aliases without `AS` keyword.
                    // Example: SELECT 'foo' `bar`
                    if (($brackets === 0) && (empty($options['noAlias']))) {
                        if (($token->type === Token::TYPE_NONE) || ($token->type === Token::TYPE_STRING)
                            || (($token->type === Token::TYPE_SYMBOL) && ($token->flags & Token::FLAG_SYMBOL_BACKTICK))
                        ) {
                            if (!empty($ret->alias)) {
                                $parser->error(
                                    __('An alias was previously found.'),
                                    $token
                                );
                            }
                            $ret->alias = $token->value;
                            continue;
                        }
                    }
                }

                $ret->expr .= $token->token;
            }

            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_FUNCTION)) {
                $prev = strtoupper($token->value);
            } elseif (($token->type === Token::TYPE_OPERATOR) || ($token->value === '(')) {
                $prev = true;
            } else {
                $prev = null;
            }
        }

        if ($alias === 2) {
            $parser->error(
                __('An alias was expected.'),
                $list->tokens[$list->idx - 1]
            );
        }

        // Whitespaces might be added at the end.
        $ret->expr = trim($ret->expr);

        if (empty($ret->expr)) {
            return null;
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param Expression|Expression[] $component The component to be built.
     * @param array                   $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return implode($component, ', ');
        } else {
            if (!empty($component->expr)) {
                $ret = $component->expr;
            } else {
                $fields = array();
                if ((isset($component->database)) && ($component->database !== '')) {
                    $fields[] = $component->database;
                }
                if ((isset($component->table)) && ($component->table !== '')) {
                    $fields[] = $component->table;
                }
                if ((isset($component->column)) && ($component->column !== '')) {
                    $fields[] = $component->column;
                }
                $ret = implode('.', Context::escape($fields));
            }

            if (!empty($component->alias)) {
                $ret .= ' AS ' . Context::escape($component->alias);
            }

            return $ret;
        }
    }
}
