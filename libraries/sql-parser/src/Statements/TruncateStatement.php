<?php

/**
 * `TRUNCATE` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;
use SqlParser\Components\Expression;

/**
 * `TRUNCATE` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class TruncateStatement extends Statement
{

    /**
     * Options for `TRUNCATE` statements.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'TABLE'                         => 1,
    );

    /**
     * The name of the truncated table.
     *
     * @var Expression
     */
    public $table;
}
