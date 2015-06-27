<?php

/**
 * `SELECT` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;

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
        'MAX_STATEMENT_TIME'            => array(3, 'var'),
        'STRAIGHT_JOIN'                 => 4,
        'SQL_SMALL_RESULT'              => 5,
        'SQL_BIG_RESULT'                => 6,
        'SQL_BUFFER_RESULT'             => 7,
        'SQL_CACHE'                     => 8,
        'SQL_NO_CACHE'                  => 8,
        'SQL_CALC_FOUND_ROWS'           => 9,
    );

    /**
     * The sections of this statement, in order.
     *
     * Used by the query builder to arrange the clauses.
     *
     * @var array
     */
    public static $SECTIONS = array(
        'SELECT' => 0, '%OPTIONS' => 1,'FROM' => 2, 'PARTITION' => 3,
        'WHERE' => 4, 'GROUP BY' => 5, 'HAVING' => 6, 'ORDER BY' => 7,
        'LIMIT' => 8, 'PROCEDURE' => 9, 'INTO' => 10, 'UNION' => 11,
        'JOIN' => 12, '%OPTIONS' => 13
    );

    /**
     * Expressions that are being selected by this statement.
     *
     * @var FieldFragment[]
     */
    public $expr;

    /**
     * Tables used as sources for this statement.
     *
     * @var FieldFragment[]
     */
    public $from;

    /**
     * Partitions used as source for this statement.
     *
     * @var ArrayFragment[]
     */
    public $partition;

    /**
     * Conditions used for filtering each row of the result set.
     *
     * @var WhereKeyword
     */
    public $where;

    /**
     * Conditions used for grouping the result set.
     *
     * @var GroupKeyword
     */
    public $group;

    /**
     * Conditions used for filtering the result set.
     *
     * @var WhereKeyword[]
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
     * @var LimitKeyword
     */
    public $limit;

    /**
     * Procedure that should process the data in the result set.
     *
     * @var CallKeyword
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
     * @var JoinKeyword
     */
    public $join;
}
