<?php

/**
 * `SET` keyword parser.
 */

namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `SET` keyword parser.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class SetOperation extends Component
{
    /**
     * The name of the column that is being updated.
     *
     * @var string
     */
    public $column;

    /**
     * The new value.
     *
     * @var string
     */
    public $value;

    /**
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return SetOperation[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new self();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ column name ]-------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 ----------------------[ value ]----------------------> 1
         *
         * @var int
         */
        $state = 0;

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

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            // No keyword is expected.
            if (($token->type === Token::TYPE_KEYWORD)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ($state == 0)
            ) {
                break;
            }

            if ($state === 0) {
                if ($token->token === '=') {
                    $state = 1;
                } elseif ($token->value !== ',') {
                    $expr->column .= $token->token;
                }
            } elseif ($state === 1) {
                $tmp = Expression::parse(
                    $parser,
                    $list,
                    array(
                        'breakOnAlias' => true,
                    )
                );
                if ($tmp == null) {
                    $parser->error(__('Missing expression.'), $token);
                    break;
                }
                $expr->column = trim($expr->column);
                $expr->value = $tmp->expr;
                $ret[] = $expr;
                $expr = new self();
                $state = 0;
            }
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param SetOperation|SetOperation[] $component the component to be built
     * @param array                       $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return implode(', ', $component);
        } else {
            return $component->column . ' = ' . $component->value;
        }
    }
}
