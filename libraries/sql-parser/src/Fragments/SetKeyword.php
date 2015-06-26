<?php

/**
 * `SET` keyword parser.
 *
 * @package    SqlParser
 * @subpackage Fragments
 */
namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `SET` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class SetKeyword extends Fragment
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
     * @return SetKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new SetKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ field name ]--------------------> 1
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
            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                break;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === ',') {
                    $ret[] = $expr;
                    $expr = new SetKeyword();
                    $state = 0;
                    continue;
                } elseif ($token->value === '=') {
                    $state = 1;
                }
            }

            if ($state === 0) {
                $expr->column .= $token->value;
            } else { // } else if ($state === 1) {
                $expr->value = $token->value;
            }
        }

        // Last iteration was not saved.
        if (!empty($expr->column)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}
