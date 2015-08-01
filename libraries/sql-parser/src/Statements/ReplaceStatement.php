<?php

/**
 * `REPLACE` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\IntoKeyword;
use SqlParser\Components\SetOperation;
use SqlParser\Components\Array2d;

/**
 * `REPLACE` statement.
 *
 * REPLACE [LOW_PRIORITY | DELAYED]
 *     [INTO] tbl_name [(col_name,...)]
 *     {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
 *
 * or
 *
 * REPLACE [LOW_PRIORITY | DELAYED]
 *     [INTO] tbl_name
 *     SET col_name={expr | DEFAULT}, ...
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ReplaceStatement extends Statement
{

    /**
     * Options for `REPLACE` statements and their slot ID.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'LOW_PRIORITY'                  => 1,
        'DELAYED'                       => 1,
    );

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array
     */
    public static $CLAUSES = array(
        'REPLACE'                       => array('REPLACE',     2),
        // Used for options.
        '_OPTIONS'                      => array('_OPTIONS',    1),
        'INTO'                          => array('FROM',        3),
        'VALUES'                        => array('VALUES',      1),
        'SET'                           => array('PARTITION',   3),
    );

    /**
     * Tables used as target for this statement.
     *
     * @var IntoKeyword
     */
    public $into;

    /**
     * Values to be replaced.
     *
     * @var Array2d
     */
    public $values;

    /**
     * The replaced values.
     *
     * @var SetOperation[]
     */
    public $set;
}
