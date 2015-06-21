<?php

namespace SqlParser\Utils;

use SqlParser\Statements\SelectStatement;

class Misc
{

    /**
     * Gets a list of all aliases and their original names.
     *
     * @param SelectStatement $tree The tree that was generated after parsing.
     * @param string $db
     *
     * @return array
     */
    public static function getAliases(SelectStatement $tree, $db)
    {
        $retval = array();

        $tables = array();

        if ((!empty($tree->join->expr->table)) && (!empty($tree->join->expr->alias))) {
            $thisDb = empty($tree->join->expr->database) ?
                $db : $tree->join->expr->database;

            $retval = array(
                $thisDb => array(
                    'alias' => null,
                    'tables' => array(
                        $tree->join->expr->table => array(
                            'alias' => $tree->join->expr->alias,
                            'columns' => array(),
                        ),
                    ),
                ),
            );

            $tables[$thisDb][$tree->join->expr->alias] = $tree->join->expr->table;
        }

        foreach ($tree->from as $expr) {
            if (empty($expr->table)) {
                continue;
            }

            $thisDb = empty($expr->database) ? $db : $expr->database;

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

        foreach ($tree->expr as $expr) {
            if ((empty($expr->column)) || (empty($expr->alias))) {
                continue;
            }

            $thisDb = empty($expr->database) ? $db : $expr->database;

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
