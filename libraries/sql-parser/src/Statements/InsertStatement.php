<?php

/**
 * `INSERT` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\IntoKeyword;
use SqlParser\Components\Array2d;

/**
 * `INSERT` statement.
 *
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     [(col_name,...)]
 *     {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * or
 *
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     SET col_name={expr | DEFAULT}, ...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * or
 *
 * INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     [(col_name,...)]
 *     SELECT ...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class InsertStatement extends Statement
{

    /**
     * Options for `INSERT` statements.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'LOW_PRIORITY'                  => 1,
        'DELAYED'                       => 2,
        'HIGH_PRIORITY'                 => 3,
        'IGNORE'                        => 4,
    );

    /**
     * Tables used as target for this statement.
     *
     * @var IntoKeyword
     */
    public $into;

    /**
     * Values to be inserted.
     *
     * @var Array2d
     */
    public $values;
}
