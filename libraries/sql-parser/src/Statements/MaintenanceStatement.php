<?php

/**
 * Maintenance statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Token;
use SqlParser\TokensList;
use SqlParser\Components\Expression;
use SqlParser\Components\OptionsArray;

/**
 * Maintenance statement.
 *
 * They follow the syntax:
 *     STMT [some options] tbl_name [, tbl_name] ... [some more options]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class MaintenanceStatement extends Statement
{

    /**
     * Tables maintained.
     *
     * @var Expression[]
     */
    public $tables;

    /**
     * Function called after the token was processed.
     *
     * Parses the additional options from the end.
     *
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     * @param Token      $token  The token that is being parsed.
     *
     * @return void
     */
    public function after(Parser $parser, TokensList $list, Token $token)
    {
        // [some options] is going to be parsed first.
        //
        // There is a parser specified in `Parser::$KEYWORD_PARSERS`
        // which parses the name of the tables.
        //
        // Finally, we parse here [some more options] and that's all.
        ++$list->idx;
        $this->options->merge(
            OptionsArray::parse(
                $parser,
                $list,
                static::$OPTIONS
            )
        );
    }
}
