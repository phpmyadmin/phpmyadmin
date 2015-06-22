<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `CALL` statement.
 *
 * CALL sp_name([parameter[,...]])
 *
 * or
 *
 * CALL sp_name[()]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class CallStatement extends Statement
{

    /**
     * The name of the function and its parameters.
     *
     * @var CallKeyword
     */
    public $call;
}
