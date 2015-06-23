<?php

namespace SqlParser\Utils;

use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\ParamDefFragment;
use SqlParser\Statements\CreateStatement;

/**
 * Table utilities.
 *
 * @category   Tables
 * @package    SqlParser
 * @subpackage Utils
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Table
{

    public static function getForeignKeys(CreateStatement $tree)
    {
        if (($tree->fields === null) || (!$tree->options->has('TABLE'))) {
            return array();
        }

        $ret = array();

        foreach ($tree->fields as $field) {

            if ((empty($field->key)) || ($field->key->type !== 'FOREIGN KEY')) {
                continue;
            }

            $tmp = array(
                'constraint' => $field->name,
                'index_list' => $field->key->columns,
            );

            if (!empty($field->references)) {
                $tmp['ref_table_name'] = $field->references->table;
                $tmp['ref_index_list'] = $field->references->columns;

                if (($opt = $field->references->options->has('ON UPDATE'))) {
                    $tmp['on_update'] = str_replace(' ', '_', $opt);
                }

                if (($opt = $field->references->options->has('ON DELETE'))) {
                    $tmp['on_delete'] = str_replace(' ', '_', $opt);
                }

                // if (($opt = $field->references->options->has('MATCH'))) {
                //     $tmp['match'] = str_replace(' ', '_', $opt);
                // }
            }

            $ret[] = $tmp;

        }

        return $ret;
    }

    public static function getFields(CreateStatement $tree)
    {
        if (($tree->fields === null) || (!$tree->options->has('TABLE'))) {
            return array();
        }

        $ret = array();

        foreach ($tree->fields as $field) {

            // Skipping keys.
            if (empty($field->type)) {
                continue;
            }

            $ret[$field->name] = array(
                'type' => $field->type->name,
                'timestamp_not_null' => false,
            );

            if ($field->options) {

                if ($field->type->name === 'TIMESTAMP') {
                    if ($field->options->has('NOT NULL')) {
                        $ret[$field->name]['timestamp_not_null'] = true;
                    }
                }

                if (($option = $field->options->has('DEFAULT'))) {
                    $ret[$field->name]['default_value'] = $option;
                    if ($option === 'CURRENT_TIMESTAMP') {
                        $ret[$field->name]['default_current_timestamp'] = true;
                    }
                }

                if (($option = $field->options->has('ON UPDATE'))) {
                    if ($option === 'CURRENT_TIMESTAMP') {
                        $ret[$field->name]['on_update_current_timestamp'] = true;
                    }
                }
            }

        }

        return $ret;
    }
}
