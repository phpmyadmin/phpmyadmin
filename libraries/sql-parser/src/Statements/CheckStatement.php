<?php

/**
 * `CHECK` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

/**
 * `CHECK` statement.
 *
 * CHECK TABLE tbl_name [, tbl_name] ... [option] ...
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class CheckStatement extends MaintenanceStatement
{

    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'TABLE'                         => 1,

        'FOR UPGRADE'                   => 2,
        'QUICK'                         => 3,
        'FAST'                          => 4,
        'MEDIUM'                        => 5,
        'EXTENDED'                      => 6,
        'CHANGED'                       => 7,
    );
}
