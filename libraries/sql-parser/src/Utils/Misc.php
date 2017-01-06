<?php

/**
 * Miscellaneous utilities.
 */

namespace SqlParser\Utils;

use SqlParser\Components\Expression;
use SqlParser\Statements\SelectStatement;

/**
 * Miscellaneous utilities.
 *
 * @category   Misc
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class Misc
{
    /**
     * Gets a list of all aliases and their original names.
     *
     * @param SelectStatement $statement the statement to be processed
     * @param string          $database  the name of the database
     *
     * @return array
     */
    public static function getAliases($statement, $database)
    {
        if (!($statement instanceof SelectStatement)
            || (empty($statement->expr))
            || (empty($statement->from))
        ) {
            return array();
        }

        $retval = array();

        $tables = array();

        /**
         * Expressions that may contain aliases.
         * These are extracted from `FROM` and `JOIN` keywords.
         *
         * @var Expression[]
         */
        $expressions = $statement->from;

        // Adding expressions from JOIN.
        if (!empty($statement->join)) {
            foreach ($statement->join as $join) {
                $expressions[] = $join->expr;
            }
        }

        foreach ($expressions as $expr) {
            if ((!isset($expr->table)) || ($expr->table === '')) {
                continue;
            }

            $thisDb = ((isset($expr->database)) && ($expr->database !== '')) ?
                $expr->database : $database;

            if (!isset($retval[$thisDb])) {
                $retval[$thisDb] = array(
                    'alias' => null,
                    'tables' => array(),
                );
            }

            if (!isset($retval[$thisDb]['tables'][$expr->table])) {
                $retval[$thisDb]['tables'][$expr->table] = array(
                    'alias' => ((isset($expr->alias)) && ($expr->alias !== '')) ?
                        $expr->alias : null,
                    'columns' => array(),
                );
            }

            if (!isset($tables[$thisDb])) {
                $tables[$thisDb] = array();
            }
            $tables[$thisDb][$expr->alias] = $expr->table;
        }

        foreach ($statement->expr as $expr) {
            if ((!isset($expr->column)) || ($expr->column === '')
                || (!isset($expr->alias)) || ($expr->alias === '')
            ) {
                continue;
            }

            $thisDb = ((isset($expr->database)) && ($expr->database !== '')) ?
                $expr->database : $database;

            if ((isset($expr->table)) && ($expr->table !== '')) {
                $thisTable = isset($tables[$thisDb][$expr->table]) ?
                    $tables[$thisDb][$expr->table] : $expr->table;
                $retval[$thisDb]['tables'][$thisTable]['columns'][$expr->column] = $expr->alias;
            } else {
                foreach ($retval[$thisDb]['tables'] as &$table) {
                    $table['columns'][$expr->column] = $expr->alias;
                }
            }
        }

        return $retval;
    }
}
