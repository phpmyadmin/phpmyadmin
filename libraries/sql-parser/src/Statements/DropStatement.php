<?php

/**
 * `DROP` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `DROP` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class DropStatement extends Statement
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

        'TEMPORARY'                     => 2,
        'IF EXISTS'                     => 3,
    );

    /**
     * Dropped elements.
     *
     * @var FieldFragment[]
     */
    public $fields;
}
