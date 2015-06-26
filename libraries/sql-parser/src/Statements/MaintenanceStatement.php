<?php

namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Statement;
use sqlParser\Token;
use SqlParser\TokensList;
use SqlParser\Fragments\OptionsFragment;

/**
 * Maintenance statements.
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
     * @var FieldFragment[]
     */
    public $tables;

    /**
     * Parses the additional options fragment at the end.
     *
     * @param  Parser     $parser   The instance that requests parsing.
     * @param  TokensList $list The list of tokens to be parsed.
     * @param  Token      $token The token that is being parsed.
     *
     * @return
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
            OptionsFragment::parse(
                $parser,
                $list,
                static::$OPTIONS
            )
        );
    }
}
