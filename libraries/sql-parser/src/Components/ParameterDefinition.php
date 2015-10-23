<?php

/**
 * The definition of a parameter of a function or procedure.
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
 * The definition of a parameter of a function or procedure.
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ParameterDefinition extends Component
{

    /**
     * The name of the new column.
     *
     * @var string
     */
    public $name;

    /**
     * Parameter's direction (IN, OUT or INOUT).
     *
     * @var string
     */
    public $inOut;

    /**
     * The data type of thew new column.
     *
     * @var DataType
     */
    public $type;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return ParameterDefinition[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new ParameterDefinition();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 ----------------[ IN / OUT / INOUT ]----------------> 1
         *      1 ----------------------[ name ]----------------------> 2
         *
         *      2 -------------------[ data type ]--------------------> 3
         *
         *      3 ------------------------[ , ]-----------------------> 1
         *      3 ------------------------[ ) ]-----------------------> (END)
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
                }
                continue;
            } elseif ($state === 1) {
                if (($token->value === 'IN') || ($token->value === 'OUT') || ($token->value === 'INOUT')) {
                    $expr->inOut = $token->value;
                    ++$list->idx;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                } else {
                    $expr->name = $token->value;
                    $state = 2;
                }
            } elseif ($state === 2) {
                $expr->type = DataType::parse($parser, $list);
                $state = 3;
            } elseif ($state === 3) {
                $ret[] = $expr;
                $expr = new ParameterDefinition();
                if ($token->value === ',') {
                    $state = 1;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                }
            }
        }

        // Last iteration was not saved.
        if ((isset($expr->name)) && ($expr->name !== '')) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param ParameterDefinition[] $component The component to be built.
     * @param array                 $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return '(' . implode(', ', $component) . ')';
        } else {
            $tmp = '';
            if (!empty($component->inOut)) {
                $tmp .= $component->inOut . ' ';
            }

            return trim(
                $tmp . Context::escape($component->name) . ' ' . $component->type
            );
        }
    }
}
