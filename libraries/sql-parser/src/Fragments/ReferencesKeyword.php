<?php

/**
 * `REFERENCES` keyword parser.
 *
 * @package    SqlParser
 * @subpackage Fragments
 */
namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `REFERENCES` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ReferencesKeyword extends Fragment
{

    /**
     * All references options.
     *
     * @var array
     */
    public static $REFERENCES_OPTIONS = array(
        'MATCH'                         => array(1, 'var'),
        'ON DELETE'                     => array(2, 'var'),
        'ON UPDATE'                     => array(3, 'var'),
    );

    /**
     * The referenced table.
     *
     * @var string
     */
    public $table;

    /**
     * The referenced columns.
     *
     * @var array
     */
    public $columns;

    /**
     * The options of the referencing.
     *
     * @var OptionsFragment
     */
    public $options;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return ReferencesKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new ReferencesKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ table ]---------------------> 1
         *
         *      1 ---------------------[ columns ]--------------------> 2
         *
         *      2 ---------------------[ options ]--------------------> -1
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
                $ret->table = $token->value;
                $state = 1;
            } else if ($state === 1) {
                $ret->columns = ArrayFragment::parse($parser, $list)->values;
                $state = 2;
            } else if ($state === 2) {
                $ret->options = OptionsFragment::parse($parser, $list, static::$REFERENCES_OPTIONS);
                ++$list->idx;
                break;
            }

        }

        --$list->idx;
        return $ret;
    }
}
