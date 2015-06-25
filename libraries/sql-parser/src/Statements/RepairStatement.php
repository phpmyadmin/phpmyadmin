<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `REPAIR` statement.
 *
 * REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE
 *  tbl_name [, tbl_name] ...
 *  [QUICK] [EXTENDED] [USE_FRM]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class RepairStatement extends Statement
{

    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'TABLE'                         => 1,

        'NO_WRITE_TO_BINLOG'            => 2,
        'LOCAL'                         => 3,

        'QUICK'                         => 4,
        'EXTENDED'                      => 5,
        'USE_FRM'                       => 6,
    );

    /**
     * The options of this query.
     *
     * @var OptionsFragment
     *
     * @see static::$OPTIONS
     */
    public $options;

    /**
     * Repaired tables.
     *
     * @var FieldFragment[]
     */
    public $tables;
}
