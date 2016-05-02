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
     * List of allowed reserved keywords in expressions.
     *
     * @var array
     */
    private static $ALLOWED_KEYWORDS = array(
        'AS' => 1, 'DUAL' => 1, 'NULL' => 1, 'REGEXP' => 1
    );

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
     * Possible options:
     *
     *      `field`
     *
     *          First field to be filled.
     *          If this is not specified, it takes the value of `parseField`.
     *
     *      `parseField`
     *
     *          Specifies the type of the field parsed. It may be `database`,
     *          `table` or `column`. These expressions may not include
     *          parentheses.
     *
     *      `breakOnAlias`
     *
     *          If not empty, breaks when the alias occurs (it is not included).
     *
     *      `breakOnParentheses`
     *
     *          If not empty, breaks when the first parentheses occurs.
     *
     *      `parenthesesDelimited`
     *
     *          If not empty, breaks after last parentheses occurred.
     *
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
         * @var bool $alias
         */
        $alias = false;

        /**
         * Counts brackets.
         *
         * @var int $brackets
         */
        $brackets = 0;

        /**
         * Keeps track of the last two previous tokens.
         *
         * @var Token[] $prev
         */
        $prev = array(null, null);

        // When a field is parsed, no parentheses are expected.
        if (!empty($options['parseField'])) {
            $options['breakOnParentheses'] = true;
            $options['field'] = $options['parseField'];
        }

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
            if (($token->type === Token::TYPE_WHITESPACE)
                || ($token->type === Token::TYPE_COMMENT)
            ) {
                if ($isExpr) {
                    $ret->expr .= $token->token;
                }
                continue;
            }

            if ($token->type === Token::TYPE_KEYWORD) {
                if (($brackets > 0) && (empty($ret->subquery))
                    && (!empty(Parser::$STATEMENT_PARSERS[$token->value]))
                ) {
                    // A `(` was previously found and this keyword is the
                    // beginning of a statement, so this is a subquery.
                    $ret->subquery = $token->value;
                } elseif (($token->flags & Token::FLAG_KEYWORD_FUNCTION)
                    && (empty($options['parseField'])
                    && ! $alias)
                ) {
                    $isExpr = true;
                } elseif (($token->flags & Token::FLAG_KEYWORD_RESERVED)
                    && ($brackets === 0)
                ) {
                    if (empty(self::$ALLOWED_KEYWORDS[$token->value])) {
                        // A reserved keyword that is not allowed in the
                        // expression was found so the expression must have
                        // ended and a new clause is starting.
                        break;
                    }
                    if ($token->value === 'AS') {
                        if (!empty($options['breakOnAlias'])) {
                            break;
                        }
                        if ($alias) {
                            $parser->error(
                                __('An alias was expected.'),
                                $token
                            );
                            break;
                        }
                        $alias = true;
                        continue;
                    }
                    $isExpr = true;
                } elseif ($brackets === 0 && count($ret->expr) > 0 && ! $alias) {
                    /* End of expression */
                    break;
                }
            }

            if (($token->type === Token::TYPE_NUMBER)
                || ($token->type === Token::TYPE_BOOL)
                || (($token->type === Token::TYPE_SYMBOL)
                && ($token->flags & Token::FLAG_SYMBOL_VARIABLE))
                || (($token->type === Token::TYPE_OPERATOR)
                && ($token->value !== '.'))
            ) {
                if (!empty($options['parseField'])) {
                    break;
                }

                // Numbers, booleans and operators (except dot) are usually part
                // of expressions.
                $isExpr = true;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ((!empty($options['breakOnParentheses']))
                    && (($token->value === '(') || ($token->value === ')'))
                ) {
                    // No brackets were expected.
                    break;
                }
                if ($token->value === '(') {
                    ++$brackets;
                    if ((empty($ret->function)) && ($prev[1] !== null)
                        && (($prev[1]->type === Token::TYPE_NONE)
                        || ($prev[1]->type === Token::TYPE_SYMBOL)
                        || (($prev[1]->type === Token::TYPE_KEYWORD)
                        && ($prev[1]->flags & Token::FLAG_KEYWORD_FUNCTION)))
                    ) {
                        $ret->function = $prev[1]->value;
                    }
                } elseif ($token->value === ')' && $brackets == 0) {
                    // Not our bracket
                    break;
                } elseif ($token->value === ')') {
                    --$brackets;
                    if ($brackets === 0) {
                        if (!empty($options['parenthesesDelimited'])) {
                            // The current token is the last bracket, the next
                            // one will be outside the expression.
                            $ret->expr .= $token->token;
                            ++$list->idx;
                            break;
                        }
                    } elseif ($brackets < 0) {
                        // $parser->error(__('Unexpected closing bracket.'), $token);
                        // $brackets = 0;
                        break;
                    }
                } elseif ($token->value === ',') {
                    // Expressions are comma-delimited.
                    if ($brackets === 0) {
                        break;
                    }
                }
            }

            // Saving the previous tokens.
            $prev[0] = $prev[1];
            $prev[1] = $token;

            if ($alias) {
                // An alias is expected (the keyword `AS` was previously found).
                if (!empty($ret->alias)) {
                    $parser->error(__('An alias was previously found.'), $token);
                    break;
                }
                $ret->alias = $token->value;
                $alias = false;
            } elseif ($isExpr) {
                // Handling aliases.
                if (/* (empty($ret->alias)) && */ ($brackets === 0)
                    && (($prev[0] === null)
                    || ((($prev[0]->type !== Token::TYPE_OPERATOR)
                    || ($prev[0]->token === ')'))
                    && (($prev[0]->type !== Token::TYPE_KEYWORD)
                    || (!($prev[0]->flags & Token::FLAG_KEYWORD_RESERVED)))))
                    && (($prev[1]->type === Token::TYPE_STRING)
                    || (($prev[1]->type === Token::TYPE_SYMBOL)
                    && (!($prev[1]->flags & Token::FLAG_SYMBOL_VARIABLE)))
                    || ($prev[1]->type === Token::TYPE_NONE))
                ) {
                    if (!empty($ret->alias)) {
                        $parser->error(__('An alias was previously found.'), $token);
                        break;
                    }
                    $ret->alias = $prev[1]->value;
                } else {
                    $ret->expr .= $token->token;
                }
            } elseif (!$isExpr) {
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
                    $ret->expr .= $token->token;
                } else {
                    $field = empty($options['field']) ? 'column' : $options['field'];
                    if (empty($ret->$field)) {
                        $ret->$field = $token->value;
                        $ret->expr .= $token->token;
                        $dot = false;
                    } else {
                        // No alias is expected.
                        if (!empty($options['breakOnAlias'])) {
                            break;
                        }
                        if (!empty($ret->alias)) {
                            $parser->error(__('An alias was previously found.'), $token);
                            break;
                        }
                        $ret->alias = $token->value;
                    }
                }
            }
        }

        if ($alias) {
            $parser->error(
                __('An alias was expected.'),
                $list->tokens[$list->idx - 1]
            );
        }

        // White-spaces might be added at the end.
        $ret->expr = trim($ret->expr);

        if ($ret->expr === '') {
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
            if ($component->expr !== '' && !is_null($component->expr)) {
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
