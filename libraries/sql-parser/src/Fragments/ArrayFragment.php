<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses an array.
 */
class ArrayFragment extends Fragment
{

    /**
     * The array.
     *
     * @var array
     */
    public $array = array();

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return ArrayFragment
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new ArrayFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 ------------------[ array element ]-----------------> 2
         *
         *      2 ------------------------[ , ]-----------------------> 1
         *      2 ------------------------[ ) ]-----------------------> -1
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
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
                if (($token->type !== Token::TYPE_OPERATOR) || ($token->value !== '(')) {
                    $parser->error('An open bracket was expected.', $token);
                    break;
                }
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                    // Empty array.
                    break;
                }
                $ret->array[] = $token->value;
                $ret->tokens[] = $token;
                $state = 2;
            } elseif ($state === 2) {
                if (($token->type !== Token::TYPE_OPERATOR) || (($token->value !== ',') && ($token->value !== ')'))) {
                    $parser->error('Symbols \')\' or \',\' were expected', $token);
                    break;
                }
                if ($token->value === ',') {
                    $state = 1;
                } else { // )
                    break;
                }
            }

        }

        return $ret;
    }
}
