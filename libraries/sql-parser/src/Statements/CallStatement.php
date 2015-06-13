<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `CALL` statement.
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
