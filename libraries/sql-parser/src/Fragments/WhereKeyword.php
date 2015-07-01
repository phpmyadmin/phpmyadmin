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
    public static $OPERATORS = array('&&', 'AND', 'OR', 'XOR', '||');

    /**
     * Identifiers recognized.
     *
     * @var array
     */
    public $identifiers = array();

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
    public $expr;

    /**
     * Constructor.
     *
     * @param string $expr The condition or the operator.
     */
    public function __construct($expr = null)
    {
        $this->expr = trim($expr);
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

        $expr = new WhereKeyword();

        /**
         * Counts brackets.
         * @var int
         */
        $brackets = 0;

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
                $expr->expr = trim($expr->expr);
                if (!empty($expr->expr)) {
                    // Adding the condition that is delimited by this operator.
                    $ret[] = $expr;
                }

                // Adding the operator.
                $expr = new WhereKeyword($token->value);
                $expr->isOperator = true;
                $ret[] = $expr;

                $expr = new WhereKeyword();
                continue;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === '(') {
                    ++$brackets;
                } elseif ($token->value === ')') {
                    --$brackets;
                }
            }

            // No keyword is expected.
            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                if ($brackets == 0) {
                    break;
                }
            }

            $expr->expr .= $token->token;
            if (($token->type === Token::TYPE_NONE)
                || (($token->type === Token::TYPE_KEYWORD) && (!($token->flags & Token::FLAG_KEYWORD_RESERVED)))
                || ($token->type === Token::TYPE_STRING)
                || ($token->type === Token::TYPE_SYMBOL)
            ) {
                $expr->identifiers[] = $token->value;
            }
        }

        // Last iteration was not processed.
        $expr->expr = trim($expr->expr);
        if (!empty($expr->expr)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param WhereKeyword[] $fragment The fragment to be built.
     *
     * @return string
     */
    public static function build($fragment)
    {
        $ret = array();
        foreach ($fragment as $f) {
            $ret[] = $f->expr;
        }
        return implode(' ', $ret);
    }
}
