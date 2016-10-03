<?php

/**
 * `UPDATE` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\Expression;
use SqlParser\Components\Limit;
use SqlParser\Components\OrderKeyword;
use SqlParser\Components\SetOperation;
use SqlParser\Components\Condition;

/**
 * `UPDATE` statement.
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_reference
 *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
 *     [WHERE where_condition]
 *     [ORDER BY ...]
 *     [LIMIT row_count]
 *
 * or
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_references
 *     SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
 *     [WHERE where_condition]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class UpdateStatement extends Statement
{

    /**
     * Options for `UPDATE` statements and their slot ID.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'LOW_PRIORITY'                  => 1,
        'IGNORE'                        => 2,
    );

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array
     */
    public static $CLAUSES = array(
        'UPDATE'                        => array('UPDATE',      2),
        // Used for options.
        '_OPTIONS'                      => array('_OPTIONS',    1),
        // Used for updated tables.
        '_UPDATE'                       => array('UPDATE',      1),
        'SET'                           => array('SET',         3),
        'WHERE'                         => array('WHERE',       3),
        'ORDER BY'                      => array('ORDER BY',    3),
        'LIMIT'                         => array('LIMIT',       3),
    );

    /**
     * Tables used as sources for this statement.
     *
     * @var Expression[]
     */
    public $tables;

    /**
     * The updated values.
     *
     * @var SetOperation[]
     */
    public $set;

    /**
     * Conditions used for filtering each row of the result set.
     *
     * @var Condition[]
     */
    public $where;

    /**
     * Specifies the order of the rows in the result set.
     *
     * @var OrderKeyword[]
     */
    public $order;

    /**
     * Conditions used for limiting the size of the result set.
     *
     * @var Limit
     */
    public $limit;
}
