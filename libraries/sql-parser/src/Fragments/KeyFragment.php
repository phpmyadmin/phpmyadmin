<?php

/**
 * Parses the definition of a key.
 *
 * @package    SqlParser
 * @subpackage Fragments
 */
namespace SqlParser\Fragments;

use SqlParser\Context;
use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses the definition of a key.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @category   Fragments
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class KeyFragment extends Fragment
{

    /**
     * All key options.
     *
     * @var array
     */
    public static $KEY_OPTIONS = array(
        'KEY_BLOCK_SIZE'                => array(1, 'var'),
        'USING'                         => array(2, 'var'),
        'WITH PARSER'                   => array(3, 'var'),
    );

    /**
     * The name of this key.
     *
     * @var string
     */
    public $name;

    /**
     * Columns.
     *
     * @var array
     */
    public $columns;

    /**
     * The type of this key.
     *
     * @var string
     */
    public $type;

    /**
     * The options of this key.
     *
     * @var OptionsFragment
     */
    public $options;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return KeyFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new KeyFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ type ]-----------------------> 1
         *
         *      1 ----------------------[ name ]-----------------------> 1
         *      1 ---------------------[ columns ]---------------------> 2
         *
         *      2 ---------------------[ options ]---------------------> 3
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

            if ($state === 0) {
                $ret->type = $token->value;
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $ret->columns = ArrayFragment::parse($parser, $list)->values;
                    $state = 2;
                } else {
                    $ret->name = $token->value;
                }
            } elseif ($state === 2) {
                $ret->options = OptionsFragment::parse($parser, $list, static::$KEY_OPTIONS);
                ++$list->idx;
                break;
            }

        }

        --$list->idx;
        return $ret;

    }
}
