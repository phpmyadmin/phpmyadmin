<?php

/**
 * Parses the definition of a field.
 *
 * Used for parsing `CREATE TABLE` statement.
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
 * Parses the definition of a field.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @category   Fragments
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class FieldDefFragment extends Fragment
{

    /**
     * All field options.
     *
     * @var array
     */
    public static $FIELD_OPTIONS = array(
        'NOT NULL'                      => 1,
        'NULL'                          => 1,
        'DEFAULT'                       => array(2, 'var'),
        'AUTO_INCREMENT'                => 3,
        'PRIMARY'                       => 4,
        'PRIMARY KEY'                   => 4,
        'UNIQUE'                        => 4,
        'UNIQUE KEY'                    => 4,
        'COMMENT'                       => array(5, 'var'),
        'COLUMN_FORMAT'                 => array(6, 'var'),
        'ON UPDATE'                     => array(7, 'var'),
    );

    /**
     * The name of the new column.
     *
     * @var string
     */
    public $name;

    /**
     * Whether this field is a constraint or not.
     *
     * @var bool
     */
    public $isConstraint;

    /**
     * The data type of thew new column.
     *
     * @var DataTypeFragment
     */
    public $type;

    /**
     * The key.
     *
     * @var KeyFragment
     */
    public $key;

    /**
     * The table that is referenced.
     *
     * @var ReferencesKeyword
     */
    public $references;

    /**
     * The options of the new field fragment.
     *
     * @var OptionsFragment
     */
    public $options;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return FieldDefFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new FieldDefFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 --------------------[ CONSTRAINT ]------------------> 1
         *      1 -----------------------[ key ]----------------------> 2
         *      1 -------------[ constraint / column name ]-----------> 2
         *
         *      2 --------------------[ data type ]-------------------> 3
         *
         *      3 ---------------------[ options ]--------------------> 4
         *
         *      4 --------------------[ REFERENCES ]------------------> 4
         *
         *      5 ------------------------[ , ]-----------------------> 1
         *      5 ------------------------[ ) ]-----------------------> -1
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
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 1;
                }
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'CONSTRAINT')) {
                    $expr->isConstraint = true;
                } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_KEY)) {
                    $expr->key = KeyFragment::parse($parser, $list);
                    $state = 4;
                } else {
                    $expr->name = $token->value;
                    if (!$expr->isConstraint) {
                        $state = 2;
                    }
                }
            } elseif ($state === 2) {
                $expr->type = DataTypeFragment::parse($parser, $list);
                $state = 3;
            } elseif ($state === 3) {
                $expr->options = OptionsFragment::parse($parser, $list, static::$FIELD_OPTIONS);
                $state = 4;
            } elseif ($state === 4) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'REFERENCES')) {
                    ++$list->idx; // Skipping keyword 'REFERENCES'.
                    $expr->references = ReferencesKeyword::parse($parser, $list);
                } else {
                    --$list->idx;
                }
                $state = 5;
            } elseif ($state === 5) {
                if ((!empty($expr->type)) || (!empty($expr->key))) {
                    $ret[] = $expr;
                }
                $expr = new FieldDefFragment();
                if ($token->value === ',') {
                    $state = 1;
                    continue;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                }
            }

        }

        // Last iteration was not saved.
        if ((!empty($expr->type)) || (!empty($expr->key))) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;

    }
}
