<?php

namespace SqlParser\Utils;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Statements\AlterStatement;
use SqlParser\Statements\AnalyzeStatement;
use SqlParser\Statements\CallStatement;
use SqlParser\Statements\CheckStatement;
use SqlParser\Statements\ChecksumStatement;
use SqlParser\Statements\CreateStatement;
use SqlParser\Statements\DeleteStatement;
use SqlParser\Statements\DropStatement;
use SqlParser\Statements\ExplainStatement;
use SqlParser\Statements\InsertStatement;
use SqlParser\Statements\OptimizeStatement;
use SqlParser\Statements\RepairStatement;
use SqlParser\Statements\ReplaceStatement;
use SqlParser\Statements\SelectStatement;
use SqlParser\Statements\ShowStatement;
use SqlParser\Statements\UpdateStatement;

/**
 * Statement utilities.
 *
 * @category   Routines
 * @package    SqlParser
 * @subpackage Utils
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Query
{

    /**
     * Functions that set the flag `is_func`.
     *
     * @var array
     */
    public static $FUNCTIONS = array(
        'SUM','AVG','STD','STDDEV','MIN','MAX','BIT_OR','BIT_AND'
    );

    /**
     * Gets an array with flags this statement has.
     *
     * @param Statement $statement
     * @param bool $all If `false`, false values will not be included.
     *
     * @return array
     */
    public static function getFlags($statement, $all = false)
    {
        $flags = array();
        if ($all) {
            $flags = array(
                'distinct'      => false,
                'drop_database' => false,
                'is_affected'   => false,
                'is_analyse'    => false,
                'is_count'      => false,
                'is_delete'     => false,
                'is_explain'    => false,
                'is_export'     => false,
                'is_func'       => false,
                'is_group'      => false,
                'is_insert'     => false,
                'is_maint'      => false,
                'is_procedure'  => false,
                'is_replace'    => false,
                'is_select'     => false,
                'is_show'       => false,
                'is_subquery'   => false,
                'join'          => false,
                'offset'        => false,
                'reload'        => false,
                'select_from'   => false,
                'union'         => false
            );
        }

        // TODO: 'union', 'join', 'offset'
        if (($statement instanceof AlterStatement)
            || ($statement instanceof CreateStatement)
        ) {
            $flags['reload'] = true;
        } else if (($statement instanceof AnalyzeStatement)
            || ($statement instanceof CheckStatement)
            || ($statement instanceof ChecksumStatement)
            || ($statement instanceof OptimizeStatement)
            || ($statement instanceof RepairStatement)
        ) {
            $flags['is_maint'] = true;
        } else if ($statement instanceof CallStatement) {
            $flags['is_procedure'] = true;
        } else if ($statement instanceof DeleteStatement) {
            $flags['is_delete'] = true;
            $flags['is_affected'] = true;
        } else if ($statement instanceof DropStatement) {
            $flags['reload'] = true;

            if (($statement->options->has('DATABASE')
                || ($statement->options->has('SCHEMA')))
            ) {
                $flags['drop_database'] = true;
            }
        } else if ($statement instanceof ExplainStatement) {
            $flags['is_explain'] = true;
        } else if ($statement instanceof InsertStatement) {
            $flags['is_affected'] = true;
            $flags['is_insert'] = true;
        } else if ($statement instanceof ReplaceStatement) {
            $flags['is_affected'] = true;
            $flags['is_replace'] = true;
        } else if ($statement instanceof SelectStatement) {
            $flags['is_select'] = true;

            if (!empty($statement->from)) {
                $flags['select_from'] = true;
            }

            if ($statement->options->has('DISTINCT')) {
                $flags['distinct'] = true;
            }

            if ((!empty($statement->group)) || (!empty($statement->having))) {
                $flags['is_group'] = true;
            }

            if ((!empty($statement->into))
                && ($statement->into->type === 'OUTFILE')
            ) {
                $flags['is_export'] = true;
            }

            foreach ($statement->expr as $expr) {
                if (!empty($expr->function)) {
                    if ($expr->function === 'COUNT') {
                        $flags['is_count'] = true;
                    } else if (in_array($expr->function, static::$FUNCTIONS)) {
                        $flags['is_func'] = true;
                    }
                }
                if (!empty($expr->subquery)) {
                    $flags['is_subquery'] = true;
                }
            }

            if ((!empty($statement->procedure))
                && ($statement->procedure->name === 'ANALYSE')
            ) {
                $flags['is_analyse'] = true;
            }
        } else if ($statement instanceof ShowStatement) {
            $flags['is_show'] = true;
        } else if ($statement instanceof UpdateStatement) {
            $flags['is_affected'] = true;
        }

        return $flags;
    }

    /**
     * Parses a query and gets all information about it.
     *
     * @param  string $query
     *
     * @return array
     */
    public static function getAll($query)
    {
        $parser = new Parser($query);

        if (!isset($parser->statements[0])) {
            return array();
        }

        $statement = $parser->statements[0];

        $ret = static::getFlags($statement, true);

        $ret['parser'] = $parser;
        $ret['statement'] = $statement;

        if ($statement instanceof SelectStatement) {
            $ret['tables'] = array();
            foreach ($statement->expr as $expr) {
                if (!empty($expr->table)) {
                    $ret['tables'][] = array(
                        $expr->table,
                        !empty($expr->database) ? $expr->database : null
                    );
                }
            }
        }

        return $ret;
    }

}
