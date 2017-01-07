<?php

/**
 * Parses a reference to a CASE expression.
 */

namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a reference to a CASE expression.
 *
 * @category   Components
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class CaseExpression extends Component
{
    /**
     * The value to be compared.
     *
     * @var Expression
     */
    public $value;

    /**
     * The conditions in WHEN clauses.
     *
     * @var array
     */
    public $conditions;

    /**
     * The results matching with the WHEN clauses.
     *
     * @var array
     */
    public $results;

    /**
     * The values to be compared against.
     *
     * @var array
     */
    public $compare_values;

    /**
     * The result in ELSE section of expr.
     *
     * @var array
     */
    public $else_result;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param Parser     $parser the parser that serves as context
     * @param TokensList $list   the list of tokens that are being parsed
     *
     * @return Expression
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new self();

        /**
         * State of parser.
         *
         * @var int
         */
        $state = 0;

        /**
         * Syntax type (type 0 or type 1).
         *
         * @var int
         */
        $type = 0;

        ++$list->idx; // Skip 'CASE'

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE)
                || ($token->type === Token::TYPE_COMMENT)
            ) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD
                    && $token->value === 'WHEN'
                ) {
                    ++$list->idx; // Skip 'WHEN'
                    $new_condition = Condition::parse($parser, $list);
                    $type = 1;
                    $state = 1;
                    $ret->conditions[] = $new_condition;
                } elseif ($token->type === Token::TYPE_KEYWORD
                    && $token->value === 'ELSE'
                ) {
                    ++$list->idx; // Skip 'ELSE'
                    $ret->else_result = Expression::parse($parser, $list);
                    $state = 0; // last clause of CASE expression
                } elseif ($token->type === Token::TYPE_KEYWORD
                    && ($token->value === 'END'
                    || $token->value === 'end')
                ) {
                    $state = 3; // end of CASE expression
                    ++$list->idx;
                    break;
                } elseif ($token->type === Token::TYPE_KEYWORD) {
                    $parser->error(__('Unexpected keyword.'), $token);
                    break;
                } else {
                    $ret->value = Expression::parse($parser, $list);
                    $type = 0;
                    $state = 1;
                }
            } elseif ($state === 1) {
                if ($type === 0) {
                    if ($token->type === Token::TYPE_KEYWORD
                        && $token->value === 'WHEN'
                    ) {
                        ++$list->idx; // Skip 'WHEN'
                        $new_value = Expression::parse($parser, $list);
                        $state = 2;
                        $ret->compare_values[] = $new_value;
                    } elseif ($token->type === Token::TYPE_KEYWORD
                        && $token->value === 'ELSE'
                    ) {
                        ++$list->idx; // Skip 'ELSE'
                        $ret->else_result = Expression::parse($parser, $list);
                        $state = 0; // last clause of CASE expression
                    } elseif ($token->type === Token::TYPE_KEYWORD
                        && ($token->value === 'END'
                        || $token->value === 'end')
                    ) {
                        $state = 3; // end of CASE expression
                        ++$list->idx;
                        break;
                    } elseif ($token->type === Token::TYPE_KEYWORD) {
                        $parser->error(__('Unexpected keyword.'), $token);
                        break;
                    }
                } else {
                    if ($token->type === Token::TYPE_KEYWORD
                        && $token->value === 'THEN'
                    ) {
                        ++$list->idx; // Skip 'THEN'
                        $new_result = Expression::parse($parser, $list);
                        $state = 0;
                        $ret->results[] = $new_result;
                    } elseif ($token->type === Token::TYPE_KEYWORD) {
                        $parser->error(__('Unexpected keyword.'), $token);
                        break;
                    }
                }
            } elseif ($state === 2) {
                if ($type === 0) {
                    if ($token->type === Token::TYPE_KEYWORD
                        && $token->value === 'THEN'
                    ) {
                        ++$list->idx; // Skip 'THEN'
                        $new_result = Expression::parse($parser, $list);
                        $ret->results[] = $new_result;
                        $state = 1;
                    } elseif ($token->type === Token::TYPE_KEYWORD) {
                        $parser->error(__('Unexpected keyword.'), $token);
                        break;
                    }
                }
            }
        }

        if ($state !== 3) {
            $parser->error(
                __('Unexpected end of CASE expression'),
                $list->tokens[$list->idx - 1]
            );
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param Expression $component the component to be built
     * @param array      $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $ret = 'CASE ';
        if (isset($component->value)) {
            // Syntax type 0
            $ret .= $component->value . ' ';
            for (
                $i = 0;
                $i < count($component->compare_values) && $i < count($component->results);
                ++$i
            ) {
                $ret .= 'WHEN ' . $component->compare_values[$i] . ' ';
                $ret .= 'THEN ' . $component->results[$i] . ' ';
            }
        } else {
            // Syntax type 1
            for (
                $i = 0;
                $i < count($component->conditions) && $i < count($component->results);
                ++$i
            ) {
                $ret .= 'WHEN ' . Condition::build($component->conditions[$i]) . ' ';
                $ret .= 'THEN ' . $component->results[$i] . ' ';
            }
        }
        if (isset($component->else_result)) {
            $ret .= 'ELSE ' . $component->else_result . ' ';
        }
        $ret .= 'END';

        return $ret;
    }
}
