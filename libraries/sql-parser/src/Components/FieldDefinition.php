<?php

/**
 * Parses the definition of a field.
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
 * Parses the definition of a field.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class FieldDefinition extends Component
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
    public function __construct($name = null, $options = null, $type = null,
        $isConstraint = false, $references = null
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
     * @return FieldDefinition[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new FieldDefinition();

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
                }
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'CONSTRAINT')) {
                    $expr->isConstraint = true;
                } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_KEY)) {
                    $expr->key = Key::parse($parser, $list);
                    $state = 4;
                } else {
                    $expr->name = $token->value;
                    if (!$expr->isConstraint) {
                        $state = 2;
                    }
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
                $expr = new FieldDefinition();
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

    /**
     * @param FieldDefinition[] $component The component to be built.
     *
     * @return string
     */
    public static function build($component)
    {
        if (is_array($component)) {
            $ret = array();
            foreach ($component as $c) {
                $ret[] = static::build($c);
            }
            return "(\n" . implode(",\n", $ret) . "\n)";
        } else {
            $tmp = '';

            if ($component->isConstraint) {
                $tmp .= 'CONSTRAINT ';
            }

            if (!empty($component->name)) {
                $tmp .= Context::escape($component->name) . ' ';
            }

            if (!empty($component->type)) {
                $tmp .= DataType::build($component->type) . ' ';
            }

            if (!empty($component->key)) {
                $tmp .= Key::build($component->key) . ' ';
            }

            if (!empty($component->references)) {
                $tmp .= 'REFERENCES ' . Reference::build($component->references) . ' ';
            }

            $tmp .= OptionsArray::build($component->options);

            return trim($tmp);
        }
    }
}
