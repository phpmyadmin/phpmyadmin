<?php

/**
 * Miscellaneous utilities.
 *
 * @package    SqlParser
 * @subpackage Utils
 */
namespace SqlParser\Utils;

use SqlParser\Components\Expression;
use SqlParser\Statements\SelectStatement;

/**
 * Miscellaneous utilities.
 *
 * @category   Misc
 * @package    SqlParser
 * @subpackage Utils
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Misc
{

    /**
     * Gets a list of all aliases and their original names.
     *
     * @param SelectStatement $statement The statement to be processed.
     * @param string          $database  The name of the database.
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
            if (empty($expr->table)) {
                continue;
            }

            $thisDb = empty($expr->database) ? $database : $expr->database;

            if (!isset($retval[$thisDb])) {
                $retval[$thisDb] = array(
                    'alias' => null,
                    'tables' => array(),
                );
            }

            if (!isset($retval[$thisDb]['tables'][$expr->table])) {
                $retval[$thisDb]['tables'][$expr->table] = array(
                    'alias' => empty($expr->alias) ? null : $expr->alias,
                    'columns' => array(),
                );
            }

            if (!isset($tables[$thisDb])) {
                $tables[$thisDb] = array();
            }
            $tables[$thisDb][$expr->alias] = $expr->table;
        }

        foreach ($statement->expr as $expr) {
            if ((empty($expr->column)) || (empty($expr->alias))) {
                continue;
            }

            $thisDb = empty($expr->database) ? $database : $expr->database;

            if (empty($expr->table)) {
                foreach ($retval[$thisDb]['tables'] as &$table) {
                    $table['columns'][$expr->column] = $expr->alias;
                }
            } else {
                $thisTable = isset($tables[$thisDb][$expr->table]) ?
                    $tables[$thisDb][$expr->table] : $expr->table;
                $retval[$thisDb]['tables'][$thisTable]['columns'][$expr->column] = $expr->alias;
            }
        }

        return $retval;
    }
}
