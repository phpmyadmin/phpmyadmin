<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

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
class ChecksumStatement extends Statement
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

    /**
     * The options of this query.
     *
     * @var OptionsFragment
     *
     * @see static::$OPTIONS
     */
    public $options;

    /**
     * Checked tables.
     *
     * @var FieldFragment[]
     */
    public $tables;
}
