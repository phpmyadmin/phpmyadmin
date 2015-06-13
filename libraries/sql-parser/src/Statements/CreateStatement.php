<?php

namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `CREATE` statement.
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
     * The parameters of this routine.
     *
     * @var ParamDefFragment[]
     */
    public $parameters;

    /**
     * The options of the table.
     *
     * @var OptionsFragment
     *
     * @see CreateDefFragment::$TABLE_OPTIONS
     */
    public $tableOptions;

    /**
     * Field created by this statement.
     *
     * @var FieldDefFragment[]
     */
    public $fields;

    /**
     * The body of this function or procedure.
     *
     * @var Token[]
     */
    public $body;
}
