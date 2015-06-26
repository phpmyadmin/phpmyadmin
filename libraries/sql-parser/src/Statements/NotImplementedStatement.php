<?php

namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Statement;
use sqlParser\Token;
use SqlParser\TokensList;

/**
 * Not implemented (yet) statements.
 *
 * The `before` function makes the parser jump straight to the first delimiter.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class NotImplementedStatement extends Statement
{

    /**
     * Jump to the end of the delimiter.
     *
     * @param  Parser     $parser   The instance that requests parsing.
     * @param  TokensList $list The list of tokens to be parsed.
     * @param  Token      $token The token that is being parsed.
     *
     * @return
     */
    public function before(Parser $parser, TokensList $list, Token $token)
    {
        $list->getNextOfType(Token::TYPE_DELIMITER);
        --$list->idx;
    }
}
