<?php

/**
 * `TRUNCATE` statement.
 */

namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Components\Expression;

/**
 * `TRUNCATE` statement.
 *
 * @category   Statements
 *
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
        'TABLE' => 1,
    );

    /**
     * The name of the truncated table.
     *
     * @var Expression
     */
    public $table;
}
