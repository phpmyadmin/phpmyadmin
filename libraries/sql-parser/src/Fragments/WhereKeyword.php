<?php

/**
 * `WHERE` keyword parser.
 *
 * @package    SqlParser
 * @subpackage Fragments
 */
namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `WHERE` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class WhereKeyword extends Fragment
{

    /**
     * Logical operators that can be used to chain expressions.
     *
     * @var array
     */
    public static $OPERATORS = array('&&', '(', ')', 'AND', 'OR', 'XOR', '||');

    /**
     * Whether this fragment is an operator.
     *
     * @var bool
     */
    public $isOperator = false;

    /**
     * The condition.
     *
     * @var string
     */
    public $condition;

    /**
     * Constructor.
     *
     * @param string $condition The condition or the operator.
     */
    public function __construct($condition = null)
    {
        $this->condition = trim($condition);
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return WhereKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        /**
         * The condition that was parsed so far.
         * @var string
         */
        $condition = '';

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if ($token->type === Token::TYPE_COMMENT) {
                continue;
            }

            // Conditions are delimited by logical operators.
            if (in_array($token->value, static::$OPERATORS, true)) {
                if (!empty(trim($condition))) {
                    // Adding the condition that is delimited by this operator.
                    $ret[] = new WhereKeyword($condition);
                    $condition = '';
                }

                // Adding the operator.
                $expr = new WhereKeyword($token->value);
                $expr->isOperator = true;
                $ret[] = $expr;

                continue;
            }

            // No keyword is expected.
            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                break;
            }

            $condition .= $token->token;

        }

        // Last iteration was not processed.
        if (!empty(trim($condition))) {
            $ret[] = new WhereKeyword($condition);
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param WhereKeyword $fragment The fragment to be built.
     *
     * @return string
     */
    public static function build($fragment)
    {
        $conditions = array();
        foreach ($fragment as $f) {
            $conditions[] = $f->condition;
        }
        return implode(' ', $conditions);
    }
}
