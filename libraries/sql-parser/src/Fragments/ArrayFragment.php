<?php

/**
 * Parses an array.
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
 * Parses an array.
 *
 * @category   Fragments
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ArrayFragment extends Fragment
{

    /**
     * The array that contains the unprocessed value of each token.
     *
     * @var array
     */
    public $raw = array();

    /**
     * The array that contains the processed value of each token.
     *
     * @var array
     */
    public $values = array();

    /**
     * Constructor.
     *
     * @param array $raw    The unprocessed values.
     * @param array $values The processed values.
     */
    public function __construct(array $raw = array(), array $values = array())
    {
        $this->raw = $raw;
        $this->values = $values;
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return ArrayFragment
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new ArrayFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 ------------------[ array element ]-----------------> 2
         *
         *      2 ------------------------[ , ]-----------------------> 1
         *      2 ------------------------[ ) ]-----------------------> -1
         *
         * @var int
         */
        $state = 0;

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
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                if (($token->type !== Token::TYPE_OPERATOR) || ($token->value !== '(')) {
                    $parser->error('An open bracket was expected.', $token);
                    break;
                }
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                    // Empty array.
                    break;
                }
                $ret->values[] = $token->value;
                $ret->raw[] = $token->token;
                $state = 2;
            } elseif ($state === 2) {
                if (($token->type !== Token::TYPE_OPERATOR) || (($token->value !== ',') && ($token->value !== ')'))) {
                    $parser->error('Symbols \')\' or \',\' were expected', $token);
                    break;
                }
                if ($token->value === ',') {
                    $state = 1;
                } else { // )
                    break;
                }
            }

        }

        return $ret;
    }

    /**
     * @param ArrayFragment $fragment The fragment to be built.
     *
     * @return string
     */
    public static function build($fragment)
    {
        $values = array();
        if (!empty($fragment->raw)) {
            $values = $fragment->raw;
        } else {
            foreach ($fragment->values as $value) {
                $values[] = $value;
            }
        }
        return '(' . implode(', ', $values) . ')';
    }
}
