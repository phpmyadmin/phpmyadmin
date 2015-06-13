<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses the definition that follows the `CREATE` keyword.
 */
class CreateDefFragment extends Fragment
{

    /**
     * All table options.
     *
     * @var array
     */
    public static $TABLE_OPTIONS = array(
        'ENGINE'                        => array(1, 'var'),
        'AUTO_INCREMENT'                => array(2, 'var'),
        'AVG_ROW_LENGTH'                => array(3, 'var'),
        'DEFAULT CHARACTER SET'         => array(4, 'var'),
        'CHARACTER SET'                 => array(4, 'var'),
        'CHECKSUM'                      => array(5, 'var'),
        'DEFAULT COLLATE'               => array(5, 'var'),
        'COLLATE'                       => array(6, 'var'),
        'COMMENT'                       => array(7, 'var'),
        'CONNECTION'                    => array(8, 'var'),
        'DATA DIRECTORY'                => array(9, 'var'),
        'DELAY_KEY_WRITE'               => array(10, 'var'),
        'INDEX DIRECTORY'               => array(11, 'var'),
        'INSERT_METHOD'                 => array(12, 'var'),
        'KEY_BLOCK_SIZE'                => array(13, 'var'),
        'MAX_ROWS'                      => array(14, 'var'),
        'MIN_ROWS'                      => array(15, 'var'),
        'PACK_KEYS'                     => array(16, 'var'),
        'PASSWORD'                      => array(17, 'var'),
        'ROW_FORMAT'                    => array(18, 'var'),
        'TABLESPACE'                    => array(19, 'var'),
        'STORAGE'                       => array(20, 'var'),
        'UNION'                         => array(21, 'var'),
    );

    /**
     * All function options.
     *
     * @var array
     */
    public static $FUNC_OPTIONS = array(
        'COMMENT'                      => array(1, 'var'),
        'LANGUAGE SQL'                 => 2,
        'DETERMINISTIC'                => 3,
        'NOT DETERMINISTIC'            => 3,
        'CONSTAINS SQL'                => 4,
        'NO SQL'                       => 4,
        'READS SQL DATA'               => 4,
        'MODIFIES SQL DATA'            => 4,
        'SQL SEQURITY DEFINER'         => array(5, 'var'),
    );

    /**
     * The name of the new table.
     *
     * @var string
     */
    public $name;

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return CreateDefFragment
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new CreateDefFragment();

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

            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                break;
            }

            $ret->tokens[] = $token;
            $ret->name .= $token->value;
        }

        --$list->idx;
        return $ret;
    }
}
