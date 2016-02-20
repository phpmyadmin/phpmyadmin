<?php

/**
 * `RENAME TABLE` keyword parser.
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
 * `RENAME TABLE` keyword parser.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class RenameOperation extends Component
{

    /**
     * The old table name.
     *
     * @var Expression
     */
    public $old;

    /**
     * The new table name.
     *
     * @var Expression
     */
    public $new;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return RenameOperation[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new RenameOperation();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------[ old name ]--------------------> 1
         *
         *      1 ------------------------[ TO ]-----------------------> 2
         *
         *      2 ---------------------[ old name ]--------------------> 3
         *
         *      3 ------------------------[ , ]------------------------> 0
         *      3 -----------------------[ else ]----------------------> (END)
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
                $expr->old = Expression::parse(
                    $parser,
                    $list,
                    array(
                        'noAlias' => true,
                        'noBrackets' => true,
                        'skipColumn' => true,
                    )
                );
                if (empty($expr->old)) {
                    $parser->error(
                        __('The old name of the table was expected.'),
                        $token
                    );
                }
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'TO')) {
                    $state = 2;
                } else {
                    $parser->error(
                        __('Keyword "TO" was expected.'),
                        $token
                    );
                    break;
                }
            } elseif ($state === 2) {
                $expr->new = Expression::parse(
                    $parser,
                    $list,
                    array(
                        'noBrackets' => true,
                        'skipColumn' => true,
                        'noAlias' => true,
                    )
                );
                if (empty($expr->new)) {
                    $parser->error(
                        __('The new name of the table was expected.'),
                        $token
                    );
                }
                $state = 3;
            } elseif ($state === 3) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                    $ret[] = $expr;
                    $expr = new RenameOperation();
                    $state = 0;
                } else {
                    break;
                }
            }
        }

        if ($state !== 3) {
            $parser->error(
                __('A rename operation was expected.'),
                $list->tokens[$list->idx - 1]
            );
        }

        // Last iteration was not saved.
        if (!empty($expr->old)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }

    /**
     * @param RenameOperation $component The component to be built.
     * @param array           $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        if (is_array($component)) {
            return implode(', ', $component);
        } else {
            return $component->old . ' TO ' . $component->new;
        }
    }
}
