<?php

/**
 * `CHECKSUM` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

/**
 * `CHECKSUM` statement.
 *
 * CHECKSUM TABLE tbl_name [, tbl_name] ... [ QUICK | EXTENDED ]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ChecksumStatement extends MaintenanceStatement
{

    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'TABLE'                         => 1,

        'QUICK'                         => 2,
        'EXTENDED'                      => 3,
    );
}
