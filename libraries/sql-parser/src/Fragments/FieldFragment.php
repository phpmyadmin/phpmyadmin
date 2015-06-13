<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a reference to a field.
 */
class FieldFragment extends Fragment
{

    /**
     * The name of this database.
     *
     * @var string
     */
    public $database;

    /**
     * The name of this table.
     *
     * @var string
     */
    public $table;

    /**
     * The name of the column.
     *
     * @var string
     */
    public $column;

    /**
     * The sub-expression.
     *
     * @var string
     */
    public $expr = '';

    /**
     * The alias of this expression.
     *
     * @var string
     */
    public $alias;

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return FieldFragment
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new FieldFragment();

        /** @var bool Whether current tokens make an expression or a table reference. */
        $isExpr = false;

        /** @var bool Whether a period was previously found. */
        $period = false;

        /** @var int Whether an alias is expected. Is 2 if `AS` keyword was found. */
        $alias = 0;

        /** @var int Counts brackets. */
        $brackets = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                if (($isExpr) && (!$alias)) {
                    $ret->expr .= $token->token;
                    $ret->tokens[] = $token;
                }
                if (($alias === 0) && (!$isExpr) && (!$period) && (!empty($ret->expr))) {
                    $alias = 1;
                }
                continue;
            }

            if ($token->type === Token::TYPE_KEYWORD) {
                // Keywords may be found only between brackets.
                if ($brackets === 0) {
                    if ($token->value === 'AS') {
                        $alias = 2;
                        continue;
                    }
                    break;
                }
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === '(') {
                    ++$brackets;
                    $isExpr = true;
                } elseif ($token->value === ')') {
                    --$brackets;
                    if ($brackets < 0) {
                        $parser->error('Unexpected bracket.', $token);
                        $brackets = 0;
                    }
                } elseif ($token->value === ',') {
                    if ($brackets === 0) {
                        break;
                    }
                }
            }

            if (($token->type === Token::TYPE_NUMBER) || ($token->type === Token::TYPE_BOOL) ||
                (($token->type === Token::TYPE_SYMBOL) && ($token->flags & Token::FLAG_SYMBOL_VARIABLE)) ||
                (($token->type === Token::TYPE_OPERATOR)) && ($token->value !== '.')) {
                // Numbers, booleans and operators are usually part of expressions.
                $isExpr = true;
            }

            if ($alias) {
                $ret->alias = $token->value;
                $alias = 0;
            } else {
                if (!$isExpr) {
                    if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '.')) {
                        $ret->database = $ret->table;
                        $ret->table = $ret->column;
                        $period = true;
                    } else {
                        if (!empty($options['skipColumn'])) {
                            $ret->table = $token->value;
                        } else {
                            $ret->column = $token->value;
                        }
                        $period = false;
                    }
                } else {
                    if ($brackets === 0) {
                        if (($token->type === Token::TYPE_NONE) || ($token->type === Token::TYPE_STRING) ||
                            (($token->type === Token::TYPE_SYMBOL) && ($token->flags & Token::FLAG_SYMBOL_BACKTICK))) {
                            $ret->alias = $token->value;
                        }
                    }
                }

                $ret->expr .= $token->token;
                $ret->tokens[] = $token;
            }
        }

        if ($alias === 2) {
            $parser->error('Alias was expected.', $token);
        }

        if (empty($ret->tokens)) {
            return null;
        }

        --$list->idx;
        return $ret;
    }
}
