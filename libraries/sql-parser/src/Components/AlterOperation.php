<?php

/**
 * Parses an alter operation.
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
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class AlterOperation extends Component
{
    /**
     * All database options.
     *
     * @var array
     */
    public static $DB_OPTIONS = array(
        'CHARACTER SET' => array(1, 'var'),
        'CHARSET' => array(1, 'var'),
        'DEFAULT CHARACTER SET' => array(1, 'var'),
        'DEFAULT CHARSET' => array(1, 'var'),
        'UPGRADE' => array(1, 'var'),
        'COLLATE' => array(2, 'var'),
        'DEFAULT COLLATE' => array(2, 'var'),
    );

    /**
     * All table options.
     *
     * @var array
     */
    public static $TABLE_OPTIONS = array(
        'ENGINE' => array(1, 'var='),
        'AUTO_INCREMENT' => array(1, 'var='),
        'AVG_ROW_LENGTH' => array(1, 'var'),
        'MAX_ROWS' => array(1, 'var'),
        'ROW_FORMAT' => array(1, 'var'),
        'COMMENT' => array(1, 'var'),
        'ADD' => 1,
        'ALTER' => 1,
        'ANALYZE' => 1,
        'CHANGE' => 1,
        'CHECK' => 1,
        'COALESCE' => 1,
        'CONVERT' => 1,
        'DISABLE' => 1,
        'DISCARD' => 1,
        'DROP' => 1,
        'ENABLE' => 1,
        'IMPORT' => 1,
        'MODIFY' => 1,
        'OPTIMIZE' => 1,
        'ORDER' => 1,
        'PARTITION' => 1,
        'REBUILD' => 1,
        'REMOVE' => 1,
        'RENAME' => 1,
        'REORGANIZE' => 1,
        'REPAIR' => 1,
        'UPGRADE' => 1,

        'COLUMN' => 2,
        'CONSTRAINT' => 2,
        'DEFAULT' => 2,
        'TO' => 2,
        'BY' => 2,
        'FOREIGN' => 2,
        'FULLTEXT' => 2,
        'KEY' => 2,
        'KEYS' => 2,
        'PARTITIONING' => 2,
        'PRIMARY KEY' => 2,
        'SPATIAL' => 2,
        'TABLESPACE' => 2,
        'INDEX' => 2,
    );

    /**
     * All view options.
     *
     * @var array
     */
    public static $VIEW_OPTIONS = array(
        'AS' => 1,
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
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return AlterOperation
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new self();

        /**
         * Counts brackets.
         *
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
             *
             * @var Token
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
                $ret->options = OptionsArray::parse($parser, $list, $options);

                if ($ret->options->has('AS')) {
                    for (; $list->idx < $list->count; ++$list->idx) {
                        if ($list->tokens[$list->idx]->type === Token::TYPE_DELIMITER) {
                            break;
                        }
                        $ret->unknown[] = $list->tokens[$list->idx];
                    }
                    break;
                }

                $state = 1;
            } elseif ($state === 1) {
                $ret->field = Expression::parse(
                    $parser,
                    $list,
                    array(
                        'breakOnAlias' => true,
                        'parseField' => 'column',
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
                } elseif (!empty(Parser::$STATEMENT_PARSERS[$token->value])) {
                    // We have reached the end of ALTER operation and suddenly found
                    // a start to new statement, but have not find a delimiter between them

                    if (!($token->value == 'SET' && $list->tokens[$list->idx - 1]->value == 'CHARACTER')) {
                        $parser->error(
                            __('A new statement was found, but no delimiter between it and the previous one.'),
                            $token
                        );
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
     * @param AlterOperation $component the component to be built
     * @param array          $options   parameters for building
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
