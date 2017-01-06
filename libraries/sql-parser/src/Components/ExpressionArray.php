<?php

/**
 * Parses a list of expressions delimited by a comma.
 */

namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a list of expressions delimited by a comma.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class ExpressionArray extends Component
{
    /**
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return Expression[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ array ]---------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -----------------------[ else ]----------------------> (END)
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

            if (($token->type === Token::TYPE_KEYWORD)
                && ($token->flags & Token::FLAG_KEYWORD_RESERVED)
                && ((~$token->flags & Token::FLAG_KEYWORD_FUNCTION))
                && ($token->value !== 'DUAL')
                && ($token->value !== 'NULL')
                && ($token->value !== 'CASE')
            ) {
                // No keyword is expected.
                break;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD
                    && $token->value === 'CASE'
                ) {
                    $expr = CaseExpression::parse($parser, $list, $options);
                } else {
                    $expr = Expression::parse($parser, $list, $options);
                }

                if ($expr === null) {
                    break;
                }
                $ret[] = $expr;
                $state = 1;
            } elseif ($state === 1) {
                if ($token->value === ',') {
                    $state = 0;
                } else {
                    break;
                }
            }
        }

        if ($state === 0) {
            $parser->error(
                __('An expression was expected.'),
                $list->tokens[$list->idx]
            );
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param Expression[] $component the component to be built
     * @param array        $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $ret = array();
        foreach ($component as $frag) {
            $ret[] = $frag::build($frag);
        }

        return implode($ret, ', ');
    }
}
