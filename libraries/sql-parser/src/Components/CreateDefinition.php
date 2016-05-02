<?php

/**
 * Parses the create definition of a column or a key.
 *
 * Used for parsing `CREATE TABLE` statement.
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
 * Parses the create definition of a column or a key.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class CreateDefinition extends Component
{

    /**
     * All field options.
     *
     * @var array
     */
    public static $FIELD_OPTIONS = array(

        // Tells the `OptionsArray` to not sort the options.
        // See the note below.
        '_UNSORTED'                     => true,

        'NOT NULL'                      => 1,
        'NULL'                          => 1,
        'DEFAULT'                       => array(2, 'expr', array('breakOnAlias' => true)),
        'AUTO_INCREMENT'                => 3,
        'PRIMARY'                       => 4,
        'PRIMARY KEY'                   => 4,
        'UNIQUE'                        => 4,
        'UNIQUE KEY'                    => 4,
        'COMMENT'                       => array(5, 'var'),
        'COLUMN_FORMAT'                 => array(6, 'var'),
        'ON UPDATE'                     => array(7, 'var'),

        // Generated columns options.
        'GENERATED ALWAYS'              => 8,
        'AS'                            => array(9, 'expr', array('parenthesesDelimited' => true)),
        'VIRTUAL'                       => 10,
        'PERSISTENT'                    => 11,
        'STORED'                        => 11,
        // Common entries.
        //
        // NOTE: Some of the common options are not in the same order which
        // causes troubles when checking if the options are in the right order.
        // I should find a way to define multiple sets of options and make the
        // parser select the right set.
        //
        // 'UNIQUE'                        => 4,
        // 'UNIQUE KEY'                    => 4,
        // 'COMMENT'                       => array(5, 'var'),
        // 'NOT NULL'                      => 1,
        // 'NULL'                          => 1,
        // 'PRIMARY'                       => 4,
        // 'PRIMARY KEY'                   => 4,
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
     * @var DataType
     */
    public $type;

    /**
     * The key.
     *
     * @var Key
     */
    public $key;

    /**
     * The table that is referenced.
     *
     * @var Reference
     */
    public $references;

    /**
     * The options of this field.
     *
     * @var OptionsArray
     */
    public $options;

    /**
     * Constructor.
     *
     * @param string       $name         The name of the field.
     * @param OptionsArray $options      The options of this field.
     * @param DataType|Key $type         The data type of this field or the key.
     * @param bool         $isConstraint Whether this field is a constraint or not.
     * @param Reference    $references   References.
     */
    public function __construct(
        $name = null,
        $options = null,
        $type = null,
        $isConstraint = false,
        $references = null
    ) {
        $this->name = $name;
        $this->options = $options;
        if ($type instanceof DataType) {
            $this->type = $type;
        } elseif ($type instanceof Key) {
            $this->key = $type;
            $this->isConstraint = $isConstraint;
            $this->references = $references;
        }
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return CreateDefinition[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new CreateDefinition();

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
         *      5 ------------------------[ ) ]-----------------------> 6 (-1)
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
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 1;
                } else {
                    $parser->error(
                        __('An opening bracket was expected.'),
                        $token
                    );
                    break;
                }
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'CONSTRAINT')) {
                    $expr->isConstraint = true;
                } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_KEY)) {
                    $expr->key = Key::parse($parser, $list);
                    $state = 4;
                } elseif ($token->type === Token::TYPE_SYMBOL || $token->type === Token::TYPE_NONE) {
                    $expr->name = $token->value;
                    if (!$expr->isConstraint) {
                        $state = 2;
                    }
                } else {
                    $parser->error(
                        __('A symbol name was expected!'),
                        $token
                    );
                    return $ret;
                }
            } elseif ($state === 2) {
                $expr->type = DataType::parse($parser, $list);
                $state = 3;
            } elseif ($state === 3) {
                $expr->options = OptionsArray::parse($parser, $list, static::$FIELD_OPTIONS);
                $state = 4;
            } elseif ($state === 4) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'REFERENCES')) {
                    ++$list->idx; // Skipping keyword 'REFERENCES'.
                    $expr->references = Reference::parse($parser, $list);
                } else {
                    --$list->idx;
                }
                $state = 5;
            } elseif ($state === 5) {
                if ((!empty($expr->type)) || (!empty($expr->key))) {
                    $ret[] = $expr;
                }
                $expr = new CreateDefinition();
                if ($token->value === ',') {
                    $state = 1;
                } elseif ($token->value === ')') {
                    $state = 6;
                    ++$list->idx;
                    break;
                } else {
                    $parser->error(
                        __('A comma or a closing bracket was expected.'),
                        $token
                    );
                    $state = 0;
                    break;
                }
            }
        }

        // Last iteration was not saved.
        if ((!empty($expr->type)) || (!empty($expr->key))) {
            $ret[] = $expr;
        }

        if (($state !== 0) && ($state !== 6)) {
            $parser->error(
                __('A closing bracket was expected.'),
                $list->tokens[$list->idx - 1]
            );
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param CreateDefinition|CreateDefinition[] $component The component to be built.
     * @param array                               $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return "(\n  " . implode(",\n  ", $component) . "\n)";
        } else {
            $tmp = '';

            if ($component->isConstraint) {
                $tmp .= 'CONSTRAINT ';
            }

            if ((isset($component->name)) && ($component->name !== '')) {
                $tmp .= Context::escape($component->name) . ' ';
            }

            if (!empty($component->type)) {
                $tmp .= DataType::build(
                    $component->type,
                    array('lowercase' => true)
                ) . ' ';
            }

            if (!empty($component->key)) {
                $tmp .= $component->key . ' ';
            }

            if (!empty($component->references)) {
                $tmp .= 'REFERENCES ' . $component->references . ' ';
            }

            $tmp .= $component->options;

            return trim($tmp);
        }
    }
}
