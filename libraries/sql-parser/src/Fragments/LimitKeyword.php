<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `lIMIT` keyword parser.
 */
class LimitKeyword extends Fragment
{

    /**
     * The number of rows skipped.
     *
     * @var int
     */
    public $offset;

    /**
     * The number of rows to be returned.
     *
     * @var int
     */
    public $row_count;

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return LimitKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new LimitKeyword();

        $offset = false;

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

            // NOTE: `OFFSET` is not a keyword.
            if (($token->type === Token::TYPE_NONE) && ($token->value === 'OFFSET')) {
                if ($offset) {
                    $parser->error('An offset was expected.');
                }
                $offset = true;
                continue;
            }

            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                $ret->offset = $ret->row_count;
                $ret->row_count = 0;
                continue;
            }

            if ($offset) {
                $ret->offset = $token->value;
                $offset = false;
            } else {
                $ret->row_count = $token->value;
            }

            $ret->tokens[] = $token;

        }

        if ($offset) {
            $parser->error('An offset was expected.');
        }

        --$list->idx;
        return $ret;
    }
}
