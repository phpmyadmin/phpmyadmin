<?php

/**
 * `ORDER BY` keyword parser.
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
 * `ORDER BY` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class OrderKeyword extends Component
{

    /**
     * The expression that is used for ordering.
     *
     * @var Expression
     */
    public $expr;

    /**
     * The order type.
     *
     * @var string
     */
    public $type;

    /**
     * Constructor.
     *
     * @param Expression $expr The expression that we are sorting by.
     * @param string     $type The sorting type.
     */
    public function __construct($expr = null, $type = 'ASC')
    {
        $this->expr = $expr;
        $this->type = $type;
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return OrderKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new OrderKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 --------------------[ expression ]-------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 -------------------[ ASC / DESC ]--------------------> 1
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
                $expr->expr = Expression::parse($parser, $list);
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_KEYWORD) && (($token->value === 'ASC') || ($token->value === 'DESC'))) {
                    $expr->type = $token->value;
                } elseif (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                    if (!empty($expr->expr)) {
                        $ret[] = $expr;
                    }
                    $expr = new OrderKeyword();
                    $state = 0;
                } else {
                    break;
                }
            }

        }

        // Last iteration was not processed.
        if (!empty($expr->expr)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param OrderKeyword|OrderKeyword[] $component The component to be built.
     * @param array                       $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return implode(', ', $component);
        } else {
            return $component->expr . ' ' . $component->type;
        }
    }
}
