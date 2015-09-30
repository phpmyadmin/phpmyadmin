<?php

/**
 * `JOIN` keyword parser.
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
 * `JOIN` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class JoinKeyword extends Component
{

    /**
     * Types of join.
     *
     * @var array
     */
    public static $JOINS = array(
        'FULL JOIN'                     => 'FULL',
        'INNER JOIN'                    => 'INNER',
        'JOIN'                          => 'JOIN',
        'LEFT JOIN'                     => 'LEFT',
        'LEFT OUTER JOIN'               => 'LEFT',
        'RIGHT JOIN'                    => 'RIGHT',
        'RIGHT OUTER JOIN'              => 'RIGHT',
    );

    /**
     * Type of this join.
     *
     * @see static::$JOINS
     * @var string
     */
    public $type;

    /**
     * Join expression.
     *
     * @var Expression
     */
    public $expr;

    /**
     * Join conditions.
     *
     * @var Condition[]
     */
    public $on;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return JoinKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new JoinKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ JOIN ]----------------------> 1
         *
         *      1 -----------------------[ expr ]----------------------> 2
         *
         *      2 ------------------------[ ON ]-----------------------> 3
         *
         *      3 --------------------[ conditions ]-------------------> 0
         *
         * @var int $state
         */
        $state = 0;

        // By design, the parser will parse first token after the keyword.
        // In this case, the keyword must be analyzed too, in order to determine
        // the type of this join.
        if ($list->idx > 0) {
            --$list->idx;
        }

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
                if (($token->type === Token::TYPE_KEYWORD)
                    && (!empty(static::$JOINS[$token->value]))
                ) {
                    $expr->type = static::$JOINS[$token->value];
                    $state = 1;
                } else {
                    break;
                }
            } elseif ($state === 1) {
                $expr->expr = Expression::parse($parser, $list, array('skipColumn' => true));
                $state = 2;
            } elseif ($state === 2) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'ON')) {
                    $state = 3;
                }
            } elseif ($state === 3) {
                $expr->on = Condition::parse($parser, $list);
                $ret[] = $expr;
                $expr = new JoinKeyword();
                $state = 0;
            }

        }

        if (!empty($expr->type)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param JoinKeyword[] $component The component to be built.
     * @param array         $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $ret = array();
        foreach ($component as $c) {
            $ret[] = (($c->type === 'JOIN') ? 'JOIN ' : ($c->type . ' JOIN '))
                . $c->expr . ' ON ' . Condition::build($c->on);
        }
        return implode(' ', $ret);
    }
}
