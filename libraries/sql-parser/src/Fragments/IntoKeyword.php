<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `INTO` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class IntoKeyword extends Fragment
{

    /**
     * Type of target (OUTFILE or SYMBOL).
     *
     * @var string
     */
    public $type;

    /**
     * The name of the table or file.
     *
     * @var string
     */
    public $name;

    /**
     * The name of the columns.
     *
     * @var array
     */
    public $fields;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return IntoKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new IntoKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ name ]----------------------> 1
         *      0 ---------------------[ OUTFILE ]---------------------> 2
         *
         *      1 ------------------------[ ( ]------------------------> -1
         *
         *      2 ---------------------[ filename ]--------------------> 1
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

            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                if (($state === 0) && ($token->value === 'OUTFILE')) {
                    $ret->type = 'OUTFILE';
                    $state = 2;
                    continue;
                }

                // No other keyword is expected.
                break;
            }

            if ($state === 0) {
                $ret->name = $token->value;
                $state = 1;
            } else if ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $ret->fields = ArrayFragment::parse($parser, $list)->values;
                    ++$list->idx;
                }
                break;
            } else if ($state === 2) {
                $ret->name = $token->value;
                ++$list->idx;
                break;
            }
        }

        --$list->idx;
        return $ret;
    }
}
