<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `FROM` keyword parser.
 */
class FromKeyword extends Fragment
{

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return FieldFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new FieldFragment();

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

            // No keyword is expected.
            if ($token->type === Token::TYPE_KEYWORD) {
                break;
            }

            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                $ret[] = $expr;
            } else {
                $expr = FieldFragment::parse($parser, $list, array('skipColumn' => true));
                if ($expr === null) {
                    break;
                }
            }

        }

        // Last iteration was not saved.
        if (!empty($expr->tokens)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}
