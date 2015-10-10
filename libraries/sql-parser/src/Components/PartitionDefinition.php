<?php

/**
 * Parses the create definition of a partition.
 *
 * Used for parsing `CREATE TABLE` statement.
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
 * Parses the create definition of a partition.
 *
 * Used for parsing `CREATE TABLE` statement.
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class PartitionDefinition extends Component
{

    /**
     * All field options.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'STORAGE ENGINE'                => array(1, 'var'),
        'ENGINE'                        => array(1, 'var'),
        'COMMENT'                       => array(2, 'var'),
        'DATA DIRECTORY'                => array(3, 'var'),
        'INDEX DIRECTORY'               => array(4, 'var'),
        'MAX_ROWS'                      => array(5, 'var'),
        'MIN_ROWS'                      => array(6, 'var'),
        'TABLESPACE'                    => array(7, 'var'),
        'NODEGROUP'                     => array(8, 'var'),
    );

    /**
     * Whether this entry is a subpartition or a partition.
     *
     * @var bool
     */
    public $isSubpartition;

    /**
     * The name of this partition.
     *
     * @var string
     */
    public $name;

    /**
     * The type of this partition (what follows the `VALUES` keyword).
     *
     * @var string
     */
    public $type;

    /**
     * The expression used to defined this partition.
     *
     * @var Expression|string
     */
    public $expr;

    /**
     * The subpartitions of this partition.
     *
     * @var PartitionDefinition[]
     */
    public $subpartitions;

    /**
     * The options of this field.
     *
     * @var OptionsArray
     */
    public $options;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return PartitionDefinition
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new PartitionDefinition();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------[ PARTITION | SUBPARTITION ]------------> 1
         *
         *      1 -----------------------[ name ]----------------------> 2
         *
         *      2 ----------------------[ VALUES ]---------------------> 3
         *
         *      3 ---------------------[ LESS THAN ]-------------------> 4
         *      3 ------------------------[ IN ]-----------------------> 4
         *
         *      4 -----------------------[ expr ]----------------------> 5
         *
         *      5 ----------------------[ options ]--------------------> 6
         *
         *      6 ------------------[ subpartitions ]------------------> (END)
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
                $ret->isSubpartition = ($token->type === Token::TYPE_KEYWORD) && ($token->value === 'SUBPARTITION');
                $state = 1;
            } elseif ($state === 1) {
                $ret->name = $token->value;

                // Looking ahead for a 'VALUES' keyword.
                $idx = $list->idx;
                $list->getNext();
                $nextToken = $list->getNext();
                $list->idx = $idx;

                $state = ($nextToken->type === Token::TYPE_KEYWORD)
                    && ($nextToken->value === 'VALUES')
                    ? 2 : 5;
            } elseif ($state === 2) {
                $state = 3;
            } elseif ($state === 3) {
                $ret->type = $token->value;
                $state = 4;
            } elseif ($state === 4) {
                if ($token->value === 'MAXVALUE') {
                    $ret->expr = $token->value;
                } else {
                    $ret->expr = Expression::parse(
                        $parser,
                        $list,
                        array(
                            'bracketsDelimited' => true,
                            'noAlias' => true,
                        )
                    );
                }
                $state = 5;
            } elseif ($state === 5) {
                $ret->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
                $state = 6;
            } elseif ($state === 6) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $ret->subpartitions = ArrayObj::parse(
                        $parser,
                        $list,
                        array(
                            'type' => 'SqlParser\\Components\\PartitionDefinition'
                        )
                    );
                    ++$list->idx;
                }
                break;
            }
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param PartitionDefinition|PartitionDefinition[] $component The component to be built.
     * @param array                                     $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return "(\n" . implode(",\n", $component) . "\n)";
        } else {
            if ($component->isSubpartition) {
                return trim('SUBPARTITION ' . $component->name . ' ' . $component->options);
            } else {
                $subpartitions = empty($component->subpartitions)
                    ? '' : ' ' . PartitionDefinition::build($component->subpartitions);
                return trim(
                    'PARTITION ' . $component->name
                    . (empty($component->type) ? '' : ' VALUES ' . $component->type . ' ' . $component->expr)
                    . $component->options . $subpartitions
                );
            }
        }
    }
}
