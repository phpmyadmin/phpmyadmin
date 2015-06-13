<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `RENAME` statement.
 *
 * RENAME TABLE tbl_name TO new_tbl_name
 *  [, tbl_name2 TO new_tbl_name2] ...
 *
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
