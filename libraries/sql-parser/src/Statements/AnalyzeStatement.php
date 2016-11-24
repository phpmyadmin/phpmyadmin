<?php

/**
 * `ANALYZE` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\Expression;

/**
 * `ANALYZE` statement.
 *
 * ANALYZE [NO_WRITE_TO_BINLOG | LOCAL] TABLE
 *  tbl_name [, tbl_name] ...
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class AnalyzeStatement extends Statement
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
    );

    /**
     * Analyzed tables.
     *
     * @var Expression[]
     */
    public $tables;
}
