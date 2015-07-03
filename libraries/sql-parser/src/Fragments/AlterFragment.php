<?php

/**
 * Parses a reference to a field.
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
 * Parses a reference to a field.
 *
 * @category   Fragments
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class AlterFragment extends Fragment
{

    /**
     * All alter operations.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'ADD'                           => 3,
        'ALTER'                         => 3,
        'ANALYZE'                       => 3,
        'CHANGE'                        => 3,
        'CHECK'                         => 3,
        'COALESCE'                      => 3,
        'CONVERT'                       => 3,
        'DISABLE'                       => 3,
        'DISCARD'                       => 3,
        'DROP'                          => 3,
        'ENABLE'                        => 3,
        'IMPORT'                        => 3,
        'MODIFY'                        => 3,
        'OPTIMIZE'                      => 3,
        'ORDER'                         => 3,
        'PARTITION'                     => 3,
        'REBUILD'                       => 3,
        'REMOVE'                        => 3,
        'RENAME'                        => 3,
        'REORGANIZE'                    => 3,
        'REPAIR'                        => 3,

        'COLUMN'                        => 4,
        'CONSTRAINT'                    => 4,
        'DEFAULT'                       => 4,
        'TO'                            => 4,
        'BY'                            => 4,
        'FOREIGN'                       => 4,
        'FULLTEXT'                      => 4,
        'KEY'                           => 4,
        'KEYS'                          => 4,
        'PARTITIONING'                  => 4,
        'PRIMARY KEY'                   => 4,
        'SPATIAL'                       => 4,
        'TABLESPACE'                    => 4,
        'INDEX'                         => 4,

        'DEFAULT CHARACTER SET'         => array(5, 'var'),

        'COLLATE'                       => array(6, 'var'),
    );

    /**
     * Options of this operation.
     *
     * @var OptionsFragment
     */
    public $options;

    /**
     * The altered field.
     *
     * @var FieldFragment
     */
    public $field;

    /**
     * Unparsed tokens.
     *
     * @var Token[]
     */
    public $unknown = array();

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return AlterFragment
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new AlterFragment();

        /**
         * Counts brackets.
         * @var int
         */
        $brackets = 0;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ options ]---------------------> 1
         *
         *      1 ----------------------[ field ]----------------------> 2
         *
         *      2 -------------------------[ , ]-----------------------> 0
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
                if ($state !== 2) {
                    // State 2 parses the unknown part which must include whitespaces as well.
                    continue;
                }
            }

            if ($state === 0) {
                $ret->options = OptionsFragment::parse($parser, $list, static::$OPTIONS);
                $state = 1;
            } elseif ($state === 1) {
                $ret->field = FieldFragment::parse(
                    $parser,
                    $list,
                    array(
                        'noAlias' => true,
                        'noBrackets' => true,
                    )
                );
                if ($ret->field === null) {
                    // No field was read. We go back one token so the next
                    // iteration will parse the same on, but in state 2.
                    --$list->idx;
                }
                $state = 2;
            } elseif ($state === 2) {
                if ($token->type === Token::TYPE_OPERATOR) {
                    if ($token->value === '(') {
                        ++$brackets;
                    } elseif ($token->value === ')') {
                        --$brackets;
                    } elseif ($token->value === ',') {
                        break;
                    }
                }
                $ret->unknown[] = $token;
            }
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param AlterFragment $fragment The fragment to be built.
     *
     * @return string
     */
    public static function build($fragment)
    {
        $ret = OptionsFragment::build($fragment->options) . ' ';
        if (!empty($fragment->field)) {
            $ret .= FieldFragment::build($fragment->field) . ' ';
        }
        foreach ($fragment->unknown as $token) {
            $ret .= $token->token;
        }
        return $ret;
    }
}
