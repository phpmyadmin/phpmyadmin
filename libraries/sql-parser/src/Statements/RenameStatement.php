<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `RENAME` statement.
 *
 * RENAME TABLE tbl_name TO new_tbl_name
 *  [, tbl_name2 TO new_tbl_name2] ...
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class RenameStatement extends Statement
{

    /**
     * The old and new names of the tables.
     *
     * @var RenameKeyword[]
     */
    public $renames;
}
