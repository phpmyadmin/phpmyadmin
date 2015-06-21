<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `ORDER BY` keyword parser.
 */
class OrderKeyword extends Fragment
{

    /**
     * The name of the column that is being used for ordering.
     *
     * @var string
     */
    public $column;

    /**
     * The order type.
     *
     * @var string
     */
    public $type = 'ASC';

    /**
     * @param Parser $parser The parser that serves as context.
     * @param TokensList $list The list of tokens that are being parsed.
     * @param array $options Parameters for parsing.
     *
     * @return OrderKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new OrderKeyword();

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

            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                // Type of ordering. By default, it is `ASC`.
                if (($token->value === 'ASC') || ($token->value === 'DESC')) {
                    $expr->type = $token->value;
                    continue;
                }

                // No other keyword is expected.
                break;
            }

            // Saving field.
            if (($token->type === Token::TYPE_OPERATOR) && ($token->token === ',')) {
                $ret[] = $expr;
                $expr = new OrderKeyword();
                continue;
            }

            $expr->column .= $token->token;

        }

        // Last iteration was not processed.
        if (!empty($expr->column)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}
