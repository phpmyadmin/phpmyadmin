<?php

/**
 * `REFERENCES` keyword parser.
 *
 * @package    SqlParser
 * @subpackage Components
 */
namespace SqlParser\Components;

use SqlParser\Context;
use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `REFERENCES` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Reference extends Component
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
     * @var Expression
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
     * @var OptionsArray
     */
    public $options;

    /**
     * Constructor.
     *
     * @param Expression   $table   The name of the table referenced.
     * @param array        $columns The columns referenced.
     * @param OptionsArray $options The options.
     */
    public function __construct($table = null, array $columns = array(), $options = null)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->options = $options;
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return Reference
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new Reference();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ table ]---------------------> 1
         *
         *      1 ---------------------[ columns ]--------------------> 2
         *
         *      2 ---------------------[ options ]--------------------> (END)
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

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                $ret->table = Expression::parse(
                    $parser,
                    $list,
                    array(
                        'parseField' => 'table',
                        'breakOnAlias' => true,
                    )
                );
                $state = 1;
            } elseif ($state === 1) {
                $ret->columns = ArrayObj::parse($parser, $list)->values;
                $state = 2;
            } elseif ($state === 2) {
                $ret->options = OptionsArray::parse($parser, $list, static::$REFERENCES_OPTIONS);
                ++$list->idx;
                break;
            }

        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param Reference $component The component to be built.
     * @param array     $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        return trim(
            $component->table
            . ' (' . implode(', ', Context::escape($component->columns)) . ') '
            . $component->options
        );
    }
}
