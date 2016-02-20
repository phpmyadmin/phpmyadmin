<?php

/**
 * Parses an alter operation.
 *
 * @package    SqlParser
 * @subpackage Components
 */
namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses an alter operation.
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class AlterOperation extends Component
{

    /**
     * All alter operations.
     *
     * @var array
     */
    public static $OPTIONS = array(

        // table_options
        'ENGINE'                        => array(1, 'var='),
        'AUTO_INCREMENT'                => array(1, 'var='),
        'AVG_ROW_LENGTH'                => array(1, 'var'),
        'MAX_ROWS'                      => array(1, 'var'),
        'ROW_FORMAT'                    => array(1, 'var'),

        'ADD'                           => 1,
        'ALTER'                         => 1,
        'ANALYZE'                       => 1,
        'CHANGE'                        => 1,
        'CHECK'                         => 1,
        'COALESCE'                      => 1,
        'CONVERT'                       => 1,
        'DISABLE'                       => 1,
        'DISCARD'                       => 1,
        'DROP'                          => 1,
        'ENABLE'                        => 1,
        'IMPORT'                        => 1,
        'MODIFY'                        => 1,
        'OPTIMIZE'                      => 1,
        'ORDER'                         => 1,
        'PARTITION'                     => 1,
        'REBUILD'                       => 1,
        'REMOVE'                        => 1,
        'RENAME'                        => 1,
        'REORGANIZE'                    => 1,
        'REPAIR'                        => 1,

        'COLUMN'                        => 2,
        'CONSTRAINT'                    => 2,
        'DEFAULT'                       => 2,
        'TO'                            => 2,
        'BY'                            => 2,
        'FOREIGN'                       => 2,
        'FULLTEXT'                      => 2,
        'KEY'                           => 2,
        'KEYS'                          => 2,
        'PARTITIONING'                  => 2,
        'PRIMARY KEY'                   => 2,
        'SPATIAL'                       => 2,
        'TABLESPACE'                    => 2,
        'INDEX'                         => 2,

        'DEFAULT CHARACTER SET'         => array(3, 'var'),
        'DEFAULT CHARSET'               => array(3, 'var'),

        'COLLATE'                       => array(4, 'var'),
    );

    /**
     * Options of this operation.
     *
     * @var OptionsArray
     */
    public $options;

    /**
     * The altered field.
     *
     * @var Expression
     */
    public $field;

    /**
     * Unparsed tokens.
     *
     * @var Token[]|string
     */
    public $unknown = array();

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return AlterOperation
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new AlterOperation();

        /**
         * Counts brackets.
         *
         * @var int $brackets
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
         * @var int $state
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
             * @var Token $token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping comments.
            if ($token->type === Token::TYPE_COMMENT) {
                continue;
            }

            // Skipping whitespaces.
            if ($token->type === Token::TYPE_WHITESPACE) {
                if ($state === 2) {
                    // When parsing the unknown part, the whitespaces are
                    // included to not break anything.
                    $ret->unknown[] = $token;
                }
                continue;
            }

            if ($state === 0) {
                $ret->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
                $state = 1;
            } elseif ($state === 1) {
                $ret->field = Expression::parse(
                    $parser,
                    $list,
                    array(
                        'noAlias' => true,
                        'noBrackets' => true,
                    )
                );
                if ($ret->field === null) {
                    // No field was read. We go back one token so the next
                    // iteration will parse the same token, but in state 2.
                    --$list->idx;
                }
                $state = 2;
            } elseif ($state === 2) {
                if ($token->type === Token::TYPE_OPERATOR) {
                    if ($token->value === '(') {
                        ++$brackets;
                    } elseif ($token->value === ')') {
                        --$brackets;
                    } elseif (($token->value === ',') && ($brackets === 0)) {
                        break;
                    }
                }
                $ret->unknown[] = $token;
            }
        }

        if ($ret->options->isEmpty()) {
            $parser->error(
                __('Unrecognized alter operation.'),
                $list->tokens[$list->idx]
            );
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param AlterOperation $component The component to be built.
     * @param array          $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $ret = $component->options . ' ';
        if ((isset($component->field)) && ($component->field !== '')) {
            $ret .= $component->field . ' ';
        }
        $ret .= TokensList::build($component->unknown);
        return $ret;
    }
}
