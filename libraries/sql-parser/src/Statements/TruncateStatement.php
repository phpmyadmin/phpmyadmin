<?php

/**
 * `TRUNCATE` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Token;
use SqlParser\TokensList;
use SqlParser\Fragments\ArrayFragment;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\FieldDefFragment;
use SqlParser\Fragments\FieldFragment;
use SqlParser\Fragments\OptionsFragment;
use SqlParser\Fragments\ParamDefFragment;

/**
 * `TRUNCATE` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
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
     * @var FieldFragment
     */
    public $table;
}
