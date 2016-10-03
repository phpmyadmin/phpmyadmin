<?php

/**
 * Parses a list of expressions delimited by a comma.
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
 * Parses a list of expressions delimited by a comma.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class ExpressionArray extends Component
{

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
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
     * @param Expression[] $component The component to be built.
     * @param array        $options   Parameters for building.
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
