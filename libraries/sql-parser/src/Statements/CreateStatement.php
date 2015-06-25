<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `CREATE` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class CreateStatement extends Statement
{

    /**
     * Options for `CREATE` statements.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'DATABASE'                      => 1,
        'EVENT'                         => 1,
        'FUNCTION'                      => 1,
        'INDEX'                         => 1,
        'PROCEDURE'                     => 1,
        'SERVER'                        => 1,
        'TABLE'                         => 1,
        'TABLESPACE'                    => 1,
        'TRIGGER'                       => 1,
        'USER'                          => 1,
        'VIEW'                          => 1,

        'TEMPORARY'                     => 2,
        'IF NOT EXISTS'                 => 3,
        'DEFINER'                       => array(4, 'var'),
    );

    /**
     * The name of the enw table.
     *
     * @var CreateDefFragment
     */
    public $name;

    /**
     * The options of this query.
     *
     * @var OptionsFragment
     *
     * @see static::$OPTIONS
     */
    public $options;

    /**
     * The options of the entity (table, procedure, function, etc.).
     *
     * @var OptionsFragment
     *
     * @see CreateDefFragment::$TABLE_OPTIONS
     * @see CreateDefFragment::$FUNC_OPTIONS
     */
    public $entityOptions;

    /**
     * Field created by this statement.
     *
     * @var FieldDefFragment[]
     */
    public $fields;

    /**
     * The return data type of this routine.
     *
     * @var DataTypeFragment
     */
    public $return;

    /**
     * The parameters of this routine.
     *
     * @var ParamDefFragment[]
     */
    public $parameters;

    /**
     * The body of this function or procedure.
     *
     * @var Token[]
     */
    public $body;
}
