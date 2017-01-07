<?php

/**
 * `LIMIT` keyword parser.
 */

namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `LIMIT` keyword parser.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class Limit extends Component
{
    /**
     * The number of rows skipped.
     *
     * @var int
     */
    public $offset;

    /**
     * The number of rows to be returned.
     *
     * @var int
     */
    public $rowCount;

    /**
     * Constructor.
     *
     * @param int $rowCount the row count
     * @param int $offset   the offset
     */
    public function __construct($rowCount = 0, $offset = 0)
    {
        $this->rowCount = $rowCount;
        $this->offset = $offset;
    }

    /**
     * @param Parser     $parser  the parser that serves as context
     * @param TokensList $list    the list of tokens that are being parsed
     * @param array      $options parameters for parsing
     *
     * @return Limit
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new self();

        $offset = false;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
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

            if (($token->type === Token::TYPE_KEYWORD) && ($token->flags & Token::FLAG_KEYWORD_RESERVED)) {
                break;
            }

            if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'OFFSET')) {
                if ($offset) {
                    $parser->error(__('An offset was expected.'), $token);
                }
                $offset = true;
                continue;
            }

            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ',')) {
                $ret->offset = $ret->rowCount;
                $ret->rowCount = 0;
                continue;
            }

            if ($offset) {
                $ret->offset = $token->value;
                $offset = false;
            } else {
                $ret->rowCount = $token->value;
            }
        }

        if ($offset) {
            $parser->error(
                __('An offset was expected.'),
                $list->tokens[$list->idx - 1]
            );
        }

        --$list->idx;

        return $ret;
    }

    /**
     * @param Limit $component the component to be built
     * @param array $options   parameters for building
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        return $component->offset . ', ' . $component->rowCount;
    }
}
