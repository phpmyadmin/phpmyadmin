<?php

namespace SqlParser\Statements;

use SqlParser\Statements\NotImplementedStatement;

/**
 * `ALTER` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class AlterStatement extends NotImplementedStatement
{

    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'DATABASE'                      => 1,
        'EVENT'                         => 1,
        'FUNCTION'                      => 1,
        'INDEX'                         => 1,
        'LOGFILE'                       => 1,
        'PROCEDURE'                     => 1,
        'SCHEMA'                        => 1,
        'SERVER'                        => 1,
        'TABLE'                         => 1,
        'TABLESPACE'                    => 1,
        'TRIGGER'                       => 1,
    );

}
