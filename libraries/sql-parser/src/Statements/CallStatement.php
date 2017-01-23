<?php

/**
 * `CALL` statement.
 */

namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\FunctionCall;

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
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class CallStatement extends Statement
{
    /**
     * The name of the function and its parameters.
     *
     * @var FunctionCall
     */
    public $call;
}
