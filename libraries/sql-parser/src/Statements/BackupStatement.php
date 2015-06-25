<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `BACKUP` statement.
 *
 * BACKUP TABLE tbl_name [, tbl_name] ... TO '/path/to/backup/directory'
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class BackupStatement extends Statement
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

        'TO'                            => array(4, 'var'),
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
     * Backup tables.
     *
     * @var FieldFragment[]
     */
    public $tables;
}
