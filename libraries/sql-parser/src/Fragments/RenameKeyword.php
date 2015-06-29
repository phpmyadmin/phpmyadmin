<?php

/**
 * `RENAME TABLE` keyword parser.
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
 * `RENAME TABLE` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class RenameKeyword extends Fragment
{

    /**
     * The old name.
     *
     * @var string
     */
    public $old;

    /**
     * The new name.
     *
     * @var string
     */
    public $new;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return RenameKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new RenameKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ old name ]--------------------> 1
         *
         *      1 ------------------------[ TO ]-----------------------> 2
         *
         *      2 ---------------------[ old name ]--------------------> 3
         *
         *      3 ------------------------[ , ]------------------------> 0
         *      3 -----------------------[ else ]----------------------> -1
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
                if (($state === 1) && ($token->value === 'TO')) {
                    $state = 2;
                    continue;
                }

                // No other keyword is expected.
                break;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if (($state === 3) && ($token->value === ',')) {
                    $ret[] = $expr;
                    $expr = new RenameKeyword();
                    $state = 0;
                    continue;
                }

                // No other operator is expected.
                break;
            }

            if ($state == 0) {
                $expr->old = $token->value;
                $state = 1;
            } elseif ($state == 2) {
                $expr->new = $token->value;
                $state = 3;
            }

        }

        // Last iteration was not saved.
        if (!empty($expr->old)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}
