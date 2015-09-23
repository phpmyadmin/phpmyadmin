<?php

/**
 * `SET` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\SetOperation;

/**
 * `SET` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class SetStatement extends Statement
{

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array
     */
    public static $CLAUSES = array(
        'SET'                           => array('SET',         3),
    );

    /**
     * The updated values.
     *
     * @var SetOperation[]
     */
    public $set;
}
