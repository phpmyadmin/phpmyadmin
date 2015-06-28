<?php

/**
 * `ALTER` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;
use SqlParser\Fragments\FieldFragment;
use SqlParser\Fragments\OptionsFragment;
use SqlParser\Statements\NotImplementedStatement;

/**
 * `ALTER` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class AlterStatement extends NotImplementedStatement
{

    /**
     * Table affected.
     *
     * @var FieldFragment
     */
    public $table;

    /**
     * Column affected by this statement.
     *
     * @var FieldFragment
     */
    public $altered;

    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'ONLINE'                        => 1,
        'OFFLINE'                       => 1,

        'IGNORE'                        => 2,

        'TABLE'                         => 3,

        'ADD'                           => 4,
        'ALTER'                         => 4,
        'ANALYZE'                       => 4,
        'CHANGE'                        => 4,
        'CHECK'                         => 4,
        'COALESCE'                      => 4,
        'CONVERT'                       => 4,
        'DISABLE'                       => 4,
        'DISCARD'                       => 4,
        'DROP'                          => 4,
        'ENABLE'                        => 4,
        'IMPORT'                        => 4,
        'MODIFY'                        => 4,
        'OPTIMIZE'                      => 4,
        'ORDER'                         => 4,
        'PARTITION'                     => 4,
        'REBUILD'                       => 4,
        'REMOVE'                        => 4,
        'RENAME'                        => 4,
        'REORGANIZE'                    => 4,
        'REPAIR'                        => 4,

        'COLUMN'                        => 5,
        'CONSTRAINT'                    => 5,
        'DEFAULT'                       => 5,
        'TO'                            => 5,
        'BY'                            => 5,
        'FOREIGN'                       => 5,
        'FULLTEXT'                      => 5,
        'KEYS'                          => 5,
        'PARTITIONING'                  => 5,
        'PRIMARY KEY'                   => 5,
        'SPATIAL'                       => 5,
        'TABLESPACE'                    => 5,
        'INDEX'                         => 5,

        'DEFAULT CHARACTER SET'         => array(6, 'var'),

        'COLLATE'                       => array(7, 'var'),
    );

    /**
     * Extracts the name of affected column.
     *
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     * @param Token      $token  The token that is being parsed.
     *
     * @return void
     */
    public function after(Parser $parser, TokensList $list, Token $token)
    {
        // Parsing operation.
        ++$list->idx;
        $this->options->merge(
            OptionsFragment::parse(
                $parser,
                $list,
                static::$OPTIONS
            )
        );

        // Parsing affected field.
        ++$list->idx;
        $this->altered = FieldFragment::parse($parser, $list);

        //
        parent::after($parser, $list, $token);
    }

}
