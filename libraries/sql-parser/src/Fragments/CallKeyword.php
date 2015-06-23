<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a function call.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class CallKeyword extends Fragment
{

    /**
     * The name of this function.
     *
     * @var string
     */
    public $name;

    /**
     * The list of parameters
     *
     * @var array
     */
    public $parameters = array();

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return CallKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new CallKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ name ]-----------------------> 1
         *
         *      1 --------------------[ parameters ]-------------------> -1
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

            if ($state === 0) {
                $ret->name = $token->value;
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $ret->parameters = ArrayFragment::parse($parser, $list)->values;
                }
                break;
            }

        }

        return $ret;
    }
}
