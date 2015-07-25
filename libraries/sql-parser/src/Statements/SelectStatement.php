<?php

/**
 * `SELECT` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\ArrayObj;
use SqlParser\Components\FunctionCall;
use SqlParser\Components\Expression;
use SqlParser\Components\IntoKeyword;
use SqlParser\Components\JoinKeyword;
use SqlParser\Components\Limit;
use SqlParser\Components\OrderKeyword;
use SqlParser\Components\Condition;

/**
 * `SELECT` statement.
 *
 * SELECT
 *     [ALL | DISTINCT | DISTINCTROW ]
 *       [HIGH_PRIORITY]
 *       [MAX_STATEMENT_TIME = N]
 *       [STRAIGHT_JOIN]
 *       [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
 *       [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
 *     select_expr [, select_expr ...]
 *     [FROM table_references
 *       [PARTITION partition_list]
 *     [WHERE where_condition]
 *     [GROUP BY {col_name | expr | position}
 *       [ASC | DESC], ... [WITH ROLLUP]]
 *     [HAVING where_condition]
 *     [ORDER BY {col_name | expr | position}
 *       [ASC | DESC], ...]
 *     [LIMIT {[offset,] row_count | row_count OFFSET offset}]
 *     [PROCEDURE procedure_name(argument_list)]
 *     [INTO OUTFILE 'file_name'
 *         [CHARACTER SET charset_name]
 *         export_options
 *       | INTO DUMPFILE 'file_name'
 *       | INTO var_name [, var_name]]
 *     [FOR UPDATE | LOCK IN SHARE MODE]]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class SelectStatement extends Statement
{

    /**
     * Options for `SELECT` statements and their slot ID.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'ALL'                           => 1,
        'DISTINCT'                      => 1,
        'DISTINCTROW'                   => 1,
        'HIGH_PRIORITY'                 => 2,
        'MAX_STATEMENT_TIME'            => array(3, 'var='),
        'STRAIGHT_JOIN'                 => 4,
        'SQL_SMALL_RESULT'              => 5,
        'SQL_BIG_RESULT'                => 6,
        'SQL_BUFFER_RESULT'             => 7,
        'SQL_CACHE'                     => 8,
        'SQL_NO_CACHE'                  => 8,
        'SQL_CALC_FOUND_ROWS'           => 9,
    );

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array
     */
    public static $CLAUSES = array(
        'SELECT'                        => array('SELECT',      2),
        // Used for options.
        '_OPTIONS'                      => array('_OPTIONS',    1),
        // Used for selected expressions.
        '_SELECT'                       => array('SELECT',      1),
        'FROM'                          => array('FROM',        3),
        'PARTITION'                     => array('PARTITION',   3),
        'JOIN'                          => array('JOIN',        1),
        'WHERE'                         => array('WHERE',       3),
        'GROUP BY'                      => array('GROUP BY',    3),
        'HAVING'                        => array('HAVING',      3),
        'ORDER BY'                      => array('ORDER BY',    3),
        'LIMIT'                         => array('LIMIT',       3),
        'PROCEDURE'                     => array('PROCEDURE',   3),
        'INTO'                          => array('INTO',        3),
        'UNION'                         => array('UNION',       3),
        // These are available only when `UNION` is present.
        // 'ORDER BY'                      => array('ORDER BY',    3),
        // 'LIMIT'                         => array('LIMIT',       3),
    );

    /**
     * Expressions that are being selected by this statement.
     *
     * @var Expression[]
     */
    public $expr = array();

    /**
     * Tables used as sources for this statement.
     *
     * @var Expression[]
     */
    public $from = array();

    /**
     * Partitions used as source for this statement.
     *
     * @var ArrayObj
     */
    public $partition;

    /**
     * Conditions used for filtering each row of the result set.
     *
     * @var Condition[]
     */
    public $where;

    /**
     * Conditions used for grouping the result set.
     *
     * @var OrderKeyword[]
     */
    public $group;

    /**
     * Conditions used for filtering the result set.
     *
     * @var Condition[]
     */
    public $having;

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

    /**
     * Procedure that should process the data in the result set.
     *
     * @var FunctionCall
     */
    public $procedure;

    /**
     * Destination of this result set.
     *
     * @var IntoKeyword
     */
    public $into;

    /**
     * Joins.
     *
     * @var JoinKeyword[]
     */
    public $join;

    /**
     * Unions.
     *
     * @var SelectStatement[]
     */
    public $union = array();
}
