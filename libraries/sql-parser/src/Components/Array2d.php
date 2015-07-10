<?php

/**
 * `VALUES` keyword parser.
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
 * `VALUES` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Array2d extends Component
{

    /**
     * An array with the values of the row to be inserted.
     *
     * @var array
     */
    public $values;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return Array2d
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new Array2d();
        $value = '';

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ------------------------[ ( ]-----------------------> 1
         *
         *      1 ----------------------[ value ]---------------------> 2
         *
         *      2 ------------------------[ , ]-----------------------> 1
         *      2 ------------------------[ ) ]-----------------------> 3
         *
         *      3 ---------------------[ options ]--------------------> 4
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
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

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === '(') {
                    $state = 1;
                    continue;
                } elseif ($token->value === ',') {
                    if ($state !== 3) {
                        $expr->values[] = $value;
                        $value = '';
                        $state = 1;
                    }
                    continue;
                } elseif ($token->value === ')') {
                    $state = 3;
                    $expr->values[] = $value;
                    $ret[] = $expr;
                    $value = '';
                    $expr = new Array2d();
                    continue;
                }

                // No other operator is expected.
                break;
            }

            if ($state === 1) {
                $value .= $token->value;
                $state = 2;
            }

        }

        // Last iteration was not saved.
        if (!empty($expr->values)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}
