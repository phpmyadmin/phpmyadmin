<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

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
class CheckStatement extends Statement
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
