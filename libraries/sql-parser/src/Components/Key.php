<?php

/**
 * Parses the definition of a key.
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
 * Parses the definition of a key.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Key extends Component
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
     * @var OptionsArray
     */
    public $options;

    /**
     * Constructor.
     *
     * @param string       $name    The name of the key.
     * @param array        $columns The columns covered by this key.
     * @param string       $type    The type of this key.
     * @param OptionsArray $options The options of this key.
     */
    public function __construct($name = null, array $columns = array(),
        $type = null, $options = null
    ) {
        $this->name = $name;
        $this->columns = $columns;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return Key[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new Key();

        /**
         * Last parsed column.
         *
         * @var array
         */
        $lastColumn = array();

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
                $ret->type = $token->value;
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 2;
                } else {
                    $ret->name = $token->value;
                }
            } elseif ($state === 2) {
                if ($token->type === Token::TYPE_OPERATOR) {
                    if ($token->value === '(') {
                        $state = 3;
                    } elseif (($token->value === ',') || ($token->value === ')')) {
                        $state = ($token->value === ',') ? 2 : 4;
                        if (!empty($lastColumn)) {
                            $ret->columns[] = $lastColumn;
                            $lastColumn = array();
                        }
                    }
                } else {
                    $lastColumn['name'] = $token->value;
                }
            } elseif ($state === 3) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                    $state = 2;
                } else {
                    $lastColumn['length'] = $token->value;
                }
            } elseif ($state === 4) {
                $ret->options = OptionsArray::parse($parser, $list, static::$KEY_OPTIONS);
                ++$list->idx;
                break;
            }
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param Key   $component The component to be built.
     * @param array $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $ret = $component->type . ' ';
        if (!empty($component->name)) {
            $ret .= Context::escape($component->name) . ' ';
        }

        $columns = array();
        foreach ($component->columns as $column) {
            $tmp = Context::escape($column['name']);
            if (isset($column['length'])) {
                $tmp .= '(' . $column['length'] . ')';
            }
            $columns[] = $tmp;
        }

        $ret .= '(' . implode(',', $columns) . ') ' . $component->options;
        return trim($ret);
    }
}
