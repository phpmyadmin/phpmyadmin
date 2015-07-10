<?php

/**
 * `JOIN` keyword parser.
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
 * `JOIN` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class JoinKeyword extends Component
{

    /**
     * Join expression.
     *
     * @var Expression
     */
    public $expr;

    /**
     * Join conditions.
     *
     * @var Condition[]
     */
    public $on;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return JoinKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new JoinKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ expr ]----------------------> 1
         *
         *      1 ------------------------[ ON ]-----------------------> 2
         *
         *      2 --------------------[ conditions ]-------------------> -1
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

            if ($state === 0) {
                $ret->expr = Expression::parse($parser, $list, array('skipColumn' => true));
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'ON')) {
                    $state = 2;
                }
            } elseif ($state === 2) {
                $ret->on = Condition::parse($parser, $list);
                ++$list->idx;
                break;
            }

        }

        --$list->idx;
        return $ret;
    }
}
