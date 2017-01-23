<?php

/**
 * `REPAIR` statement.
 */

namespace SqlParser\Statements;

/**
 * `REPAIR` statement.
 *
 * REPAIR [NO_WRITE_TO_BINLOG | LOCAL] TABLE
 *  tbl_name [, tbl_name] ...
 *  [QUICK] [EXTENDED] [USE_FRM]
 *
 * @category   Statements
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class RepairStatement extends MaintenanceStatement
{
    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'TABLE' => 1,

        'NO_WRITE_TO_BINLOG' => 2,
        'LOCAL' => 3,

        'QUICK' => 4,
        'EXTENDED' => 5,
        'USE_FRM' => 6,
    );
}
