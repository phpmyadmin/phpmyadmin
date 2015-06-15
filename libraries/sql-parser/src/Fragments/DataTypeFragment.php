<?php

namespace SqlParser\Fragments;

use SqlParser\Context;
use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a data type.
 */
class DataTypeFragment extends Fragment
{

    public static $OPTIONS = array(
        'BINARY'                        => 1,
        'CHARACTER SET'                 => array(2, 'var'),
        'CHARSET'                       => array(3, 'var'),
        'COLLATE'                       => 4,
        'UNSIGNED'                      => 5,
        'ZEROFILL'                      => 6,
    );

    /**
     * The name of the data type.
     *
     * @var string
     */
    public $name;

    /**
     * The size of this data type.
     *
     * @var array
     */
    public $size = array();

    /**
     * The options of this data type.
     *
     * @var OptionsFragment
     */
    public $options = array();

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return DataTypeFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new DataTypeFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ data type ]--------------------> 1
         *
         *      1 ----------------[ size and options ]----------------> 2
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                $ret->name = $token->value;
                $ret->tokens[] = $token;
                if (!isset(Context::$DATA_TYPES[$token->value])) {
                    $parser->error('Unrecognized data type.', $token);
                }
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $size = ArrayFragment::parse($parser, $list);
                    foreach ($size->tokens as $token) {
                        $ret->size[] = $token->token;
                    }
                    $ret->tokens = array_merge($ret->tokens, $size->tokens);
                    ++$list->idx;
                }
                $ret->options = OptionsFragment::parse($parser, $list, static::$OPTIONS);
                ++$list->idx;
                break;
            }

        }

        if (empty($ret->name)) {
            return null;
        }

        --$list->idx;
        return $ret;
    }
}
