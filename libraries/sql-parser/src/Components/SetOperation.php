<?php

/**
 * `SET` keyword parser.
 *
 * @package    SqlParser
 * @subpackage Components
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
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
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
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return SetOperation[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new SetOperation();

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
         * @var int $state
         */
        $state = 0;

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
                continue;
            }

            // No keyword is expected.
            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                break;
            }

            if ($state === 0) {
                if ($token->token === '=') {
                    $state = 1;
                } else {
                    $expr->column .= $token->token;
                }
            } elseif ($state === 1) {
                if ($token->token === ',') {
                    $expr->column = trim($expr->column);
                    $expr->value = trim($expr->value);
                    $ret[] = $expr;
                    $expr = new SetOperation();
                    $state = 0;
                } else {
                    $expr->value .= $token->token;
                }
            }
        }

        // Last iteration was not saved.
        if (!empty($expr->column)) {
            $expr->column = trim($expr->column);
            $expr->value = trim($expr->value);
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param SetOperation|SetOperation[] $component The component to be built.
     * @param array                       $options   Parameters for building.
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
