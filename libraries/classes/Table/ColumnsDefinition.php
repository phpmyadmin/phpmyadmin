<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Partition;
use PhpMyAdmin\Relation;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\TablePartitionDefinition;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function array_merge;
use function bin2hex;
use function count;
use function explode;
use function in_array;
use function intval;
use function is_array;
use function is_iterable;
use function mb_strtoupper;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function stripcslashes;
use function substr;
use function trim;

/**
 * Displays the form used to define the structure of the table
 */
final class ColumnsDefinition
{
    /**
     * @param Transformations   $transformations Transformations
     * @param Relation          $relation        Relation
     * @param DatabaseInterface $dbi             Database Interface instance
     * @param string            $action          Action
     * @param int               $num_fields      The number of fields
     * @param string|null       $regenerate      Use regeneration
     * @param array|null        $selected        Selected
     * @param array|null        $fields_meta     Fields meta
     * @param array|null        $field_fulltext  Fields full text
     *
     * @return array<string, mixed>
     */
    public static function displayForm(
        Transformations $transformations,
        Relation $relation,
        DatabaseInterface $dbi,
        string $action,
        $num_fields = 0,
        $regenerate = null,
        $selected = null,
        $fields_meta = null,
        $field_fulltext = null
    ): array {
        global $db, $table, $cfg, $col_priv, $is_reload_priv, $mime_map;

        Util::checkParameters([
            'server',
            'db',
            'table',
            'action',
            'num_fields',
        ]);

        $length_values_input_size = 8;
        $content_cells = [];
        $form_params = ['db' => $db];

        if ($action == Url::getFromRoute('/table/create')) {
            $form_params['reload'] = 1;
        } else {
            if ($action == Url::getFromRoute('/table/add-field')) {
                $form_params = array_merge(
                    $form_params,
                    [
                        'field_where' => Util::getValueByKey($_POST, 'field_where'),
                    ]
                );
                if (isset($_POST['field_where'])) {
                    $form_params['after_field'] = $_POST['after_field'];
                }
            }
            $form_params['table'] = $table;
        }

        $form_params['orig_num_fields'] = $num_fields;

        $form_params = array_merge(
            $form_params,
            [
                'orig_field_where' => Util::getValueByKey($_POST, 'field_where'),
                'orig_after_field' => Util::getValueByKey($_POST, 'after_field'),
            ]
        );

        if (isset($selected) && is_array($selected)) {
            foreach ($selected as $o_fld_nr => $o_fld_val) {
                $form_params['selected[' . $o_fld_nr . ']'] = $o_fld_val;
            }
        }

        $is_backup = ($action != Url::getFromRoute('/table/create')
            && $action != Url::getFromRoute('/table/add-field'));

        $cfgRelation = $relation->getRelationsParam();

        $comments_map = $relation->getComments($db, $table);

        $move_columns = [];
        if (isset($fields_meta)) {
            $move_columns = $dbi->getTable($db, $table)->getColumnsMeta();
        }

        $available_mime = [];
        if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
            $mime_map = $transformations->getMime($db, $table);
            $available_mime = $transformations->getAvailableMimeTypes();
        }

        $mime_types = [
            'input_transformation',
            'transformation',
        ];
        foreach ($mime_types as $mime_type) {
            if (! isset($available_mime[$mime_type]) || ! is_iterable($available_mime[$mime_type])) {
                continue;
            }

            foreach ($available_mime[$mime_type] as $mimekey => $transform) {
                $available_mime[$mime_type . '_file_quoted'][$mimekey] = preg_quote(
                    $available_mime[$mime_type . '_file'][$mimekey],
                    '@'
                );
            }
        }

        //  workaround for field_fulltext, because its submitted indices contain
        //  the index as a value, not a key. Inserted here for easier maintenance
        //  and less code to change in existing files.
        if (isset($field_fulltext) && is_array($field_fulltext)) {
            foreach ($field_fulltext as $fulltext_nr => $fulltext_indexkey) {
                $submit_fulltext[$fulltext_indexkey] = $fulltext_indexkey;
            }
        }
        if (isset($_POST['submit_num_fields'])
            || isset($_POST['submit_partition_change'])
        ) {
            //if adding new fields, set regenerate to keep the original values
            $regenerate = 1;
        }

         $foreigners = $relation->getForeigners($db, $table, '', 'foreign');
        $child_references = null;
        // From MySQL 5.6.6 onwards columns with foreign keys can be renamed.
        // Hence, no need to get child references
        if ($dbi->getVersion() < 50606) {
            $child_references = $relation->getChildReferences($db, $table);
        }

        for ($columnNumber = 0; $columnNumber < $num_fields; $columnNumber++) {
            $type = '';
            $length = '';
            $columnMeta = [];
            $submit_attribute = null;
            $extracted_columnspec = [];

            if (! empty($regenerate)) {
                $columnMeta = array_merge(
                    $columnMeta,
                    [
                        'Field'        => Util::getValueByKey(
                            $_POST,
                            "field_name.${columnNumber}",
                            null
                        ),
                        'Type'         => Util::getValueByKey(
                            $_POST,
                            "field_type.${columnNumber}",
                            null
                        ),
                        'Collation'    => Util::getValueByKey(
                            $_POST,
                            "field_collation.${columnNumber}",
                            ''
                        ),
                        'Null'         => Util::getValueByKey(
                            $_POST,
                            "field_null.${columnNumber}",
                            ''
                        ),
                        'DefaultType'  => Util::getValueByKey(
                            $_POST,
                            "field_default_type.${columnNumber}",
                            'NONE'
                        ),
                        'DefaultValue' => Util::getValueByKey(
                            $_POST,
                            "field_default_value.${columnNumber}",
                            ''
                        ),
                        'Extra'        => Util::getValueByKey(
                            $_POST,
                            "field_extra.${columnNumber}",
                            null
                        ),
                        'Virtuality'   => Util::getValueByKey(
                            $_POST,
                            "field_virtuality.${columnNumber}",
                            ''
                        ),
                        'Expression'   => Util::getValueByKey(
                            $_POST,
                            "field_expression.${columnNumber}",
                            ''
                        ),
                    ]
                );

                $columnMeta['Key'] = '';
                $parts = explode(
                    '_',
                    Util::getValueByKey($_POST, "field_key.${columnNumber}", ''),
                    2
                );
                if (count($parts) === 2 && $parts[1] == $columnNumber) {
                    $columnMeta['Key'] = Util::getValueByKey(
                        [
                            'primary' => 'PRI',
                            'index' => 'MUL',
                            'unique' => 'UNI',
                            'fulltext' => 'FULLTEXT',
                            'spatial' => 'SPATIAL',
                        ],
                        $parts[0],
                        ''
                    );
                }

                $columnMeta['Comment']
                    = isset($submit_fulltext[$columnNumber])
                && ($submit_fulltext[$columnNumber] == $columnNumber)
                    ? 'FULLTEXT' : false;

                switch ($columnMeta['DefaultType']) {
                    case 'NONE':
                        $columnMeta['Default'] = null;
                        break;
                    case 'USER_DEFINED':
                        $columnMeta['Default'] = $columnMeta['DefaultValue'];
                        break;
                    case 'NULL':
                    case 'CURRENT_TIMESTAMP':
                    case 'current_timestamp()':
                        $columnMeta['Default'] = $columnMeta['DefaultType'];
                        break;
                }

                $length = Util::getValueByKey($_POST, "field_length.${columnNumber}", $length);
                $submit_attribute = Util::getValueByKey(
                    $_POST,
                    "field_attribute.${columnNumber}",
                    false
                );
                $comments_map[$columnMeta['Field']] = Util::getValueByKey(
                    $_POST,
                    "field_comments.${columnNumber}"
                );

                $mime_map[$columnMeta['Field']] = array_merge(
                    $mime_map[$columnMeta['Field']] ?? [],
                    [
                        'mimetype' => Util::getValueByKey($_POST, "field_mimetype.${columnNumber}"),
                        'transformation' => Util::getValueByKey(
                            $_POST,
                            "field_transformation.${columnNumber}"
                        ),
                        'transformation_options' => Util::getValueByKey(
                            $_POST,
                            "field_transformation_options.${columnNumber}"
                        ),
                    ]
                );
            } elseif (isset($fields_meta[$columnNumber])) {
                $columnMeta = $fields_meta[$columnNumber];
                $virtual = [
                    'VIRTUAL',
                    'PERSISTENT',
                    'VIRTUAL GENERATED',
                    'STORED GENERATED',
                ];
                if (in_array($columnMeta['Extra'], $virtual)) {
                    $tableObj = new Table($table, $db);
                    $expressions = $tableObj->getColumnGenerationExpression(
                        $columnMeta['Field']
                    );
                    $columnMeta['Expression'] = is_array($expressions) ? $expressions[$columnMeta['Field']] : null;
                }
                switch ($columnMeta['Default']) {
                    case null:
                        if ($columnMeta['Default'] === null) {
                            if ($columnMeta['Null'] === 'YES') {
                                $columnMeta['DefaultType'] = 'NULL';
                                $columnMeta['DefaultValue'] = '';
                            } else {
                                $columnMeta['DefaultType'] = 'NONE';
                                $columnMeta['DefaultValue'] = '';
                            }
                        } else { // empty
                            $columnMeta['DefaultType'] = 'USER_DEFINED';
                            $columnMeta['DefaultValue'] = $columnMeta['Default'];
                        }
                        break;
                    case 'CURRENT_TIMESTAMP':
                    case 'current_timestamp()':
                        $columnMeta['DefaultType'] = 'CURRENT_TIMESTAMP';
                        $columnMeta['DefaultValue'] = '';
                        break;
                    default:
                        $columnMeta['DefaultType'] = 'USER_DEFINED';
                        $columnMeta['DefaultValue'] = $columnMeta['Default'];

                        if (substr($columnMeta['Type'], -4) === 'text') {
                            $textDefault = substr($columnMeta['Default'], 1, -1);
                            $columnMeta['Default'] = stripcslashes($textDefault);
                        }

                        break;
                }
            }

            if (isset($columnMeta['Type'])) {
                $extracted_columnspec = Util::extractColumnSpec(
                    $columnMeta['Type']
                );
                if ($extracted_columnspec['type'] === 'bit') {
                    $columnMeta['Default']
                        = Util::convertBitDefaultValue($columnMeta['Default']);
                }
                $type = $extracted_columnspec['type'];
                if ($length == '') {
                    $length = $extracted_columnspec['spec_in_brackets'];
                }
            } else {
                // creating a column
                $columnMeta['Type'] = '';
            }

            // Variable tell if current column is bound in a foreign key constraint or not.
            // MySQL version from 5.6.6 allow renaming columns with foreign keys
            if (isset($columnMeta['Field'], $form_params['table']) && $dbi->getVersion() < 50606) {
                $columnMeta['column_status'] = $relation->checkChildForeignReferences(
                    $form_params['db'],
                    $form_params['table'],
                    $columnMeta['Field'],
                    $foreigners,
                    $child_references
                );
            }

            // some types, for example longtext, are reported as
            // "longtext character set latin7" when their charset and / or collation
            // differs from the ones of the corresponding database.
            // rtrim the type, for cases like "float unsigned"
            $type = rtrim(
                preg_replace('/[\s]character set[\s][\S]+/', '', $type)
            );

            /**
             * old column attributes
             */
            if ($is_backup) {
                // old column name
                if (isset($columnMeta['Field'])) {
                    $form_params['field_orig[' . $columnNumber . ']']
                        = $columnMeta['Field'];
                    if (isset($columnMeta['column_status'])
                        && ! $columnMeta['column_status']['isEditable']
                    ) {
                        $form_params['field_name[' . $columnNumber . ']']
                            = $columnMeta['Field'];
                    }
                } else {
                    $form_params['field_orig[' . $columnNumber . ']'] = '';
                }

                // old column type
                if (isset($columnMeta['Type'])) {
                    // keep in uppercase because the new type will be in uppercase
                    $form_params['field_type_orig[' . $columnNumber . ']'] = mb_strtoupper($type);
                    if (isset($columnMeta['column_status'])
                        && ! $columnMeta['column_status']['isEditable']
                    ) {
                        $form_params['field_type[' . $columnNumber . ']'] = mb_strtoupper($type);
                    }
                } else {
                    $form_params['field_type_orig[' . $columnNumber . ']'] = '';
                }

                // old column length
                $form_params['field_length_orig[' . $columnNumber . ']'] = $length;

                // old column default
                $form_params = array_merge(
                    $form_params,
                    [
                        "field_default_value_orig[${columnNumber}]" => Util::getValueByKey(
                            $columnMeta,
                            'Default',
                            ''
                        ),
                        "field_default_type_orig[${columnNumber}]"  => Util::getValueByKey(
                            $columnMeta,
                            'DefaultType',
                            ''
                        ),
                        "field_collation_orig[${columnNumber}]"     => Util::getValueByKey(
                            $columnMeta,
                            'Collation',
                            ''
                        ),
                        "field_attribute_orig[${columnNumber}]"     => trim(
                            Util::getValueByKey($extracted_columnspec, 'attribute', '')
                        ),
                        "field_null_orig[${columnNumber}]"          => Util::getValueByKey(
                            $columnMeta,
                            'Null',
                            ''
                        ),
                        "field_extra_orig[${columnNumber}]"         => Util::getValueByKey(
                            $columnMeta,
                            'Extra',
                            ''
                        ),
                        "field_comments_orig[${columnNumber}]"      => Util::getValueByKey(
                            $columnMeta,
                            'Comment',
                            ''
                        ),
                        "field_virtuality_orig[${columnNumber}]"    => Util::getValueByKey(
                            $columnMeta,
                            'Virtuality',
                            ''
                        ),
                        "field_expression_orig[${columnNumber}]"    => Util::getValueByKey(
                            $columnMeta,
                            'Expression',
                            ''
                        ),
                    ]
                );
            }

            $default_value = '';
            $type_upper = mb_strtoupper($type);
            $default_function = $GLOBALS['dbi']->types->getFunctions($type_upper);

            // For a TIMESTAMP, do not show the string "CURRENT_TIMESTAMP" as a default value
            if (isset($columnMeta['DefaultValue'])) {
                $default_value = $columnMeta['DefaultValue'];
            }
            if ($type_upper === 'BIT') {
                $default_value = Util::convertBitDefaultValue($columnMeta['DefaultValue']);
            } elseif ($type_upper === 'BINARY' || $type_upper === 'VARBINARY') {
                $default_value = bin2hex($columnMeta['DefaultValue']);
            }
            $default_function = $GLOBALS['dbi']->types->getAllFunctions();


            $content_cells[$columnNumber] = [
                'column_number' => $columnNumber,
                'column_meta' => $columnMeta,
                'type_upper' => $type_upper,
                'default_value' => $default_value,
                'default_function' => $default_function,
                'length_values_input_size' => $length_values_input_size,
                'length' => $length,
                'extracted_columnspec' => $extracted_columnspec,
                'submit_attribute' => $submit_attribute,
                'comments_map' => $comments_map,
                'fields_meta' => $fields_meta ?? null,
                'is_backup' => $is_backup,
                'move_columns' => $move_columns,
                'cfg_relation' => $cfgRelation,
                'available_mime' => $available_mime,
                'mime_map' => $mime_map ?? [],
            ];
        }

        $partitionDetails = TablePartitionDefinition::getDetails();

        $charsets = Charsets::getCharsets($dbi, $cfg['Server']['DisableIS']);
        $collations = Charsets::getCollations($dbi, $cfg['Server']['DisableIS']);
        $charsetsList = [];
        /** @var Charset $charset */
        foreach ($charsets as $charset) {
            $collationsList = [];
            /** @var Collation $collation */
            foreach ($collations[$charset->getName()] as $collation) {
                $collationsList[] = [
                    'name' => $collation->getName(),
                    'description' => $collation->getDescription(),
                ];
            }
            $charsetsList[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'collations' => $collationsList,
            ];
        }

        $storageEngines = StorageEngine::getArray();

        return [
            'is_backup' => $is_backup,
            'fields_meta' => $fields_meta ?? null,
            'mimework' => $cfgRelation['mimework'],
            'action' => $action,
            'form_params' => $form_params,
            'content_cells' => $content_cells,
            'partition_details' => $partitionDetails,
            'primary_indexes' => $_POST['primary_indexes'] ?? null,
            'unique_indexes' => $_POST['unique_indexes'] ?? null,
            'indexes' => $_POST['indexes'] ?? null,
            'fulltext_indexes' => $_POST['fulltext_indexes'] ?? null,
            'spatial_indexes' => $_POST['spatial_indexes'] ?? null,
            'table' => $_POST['table'] ?? null,
            'comment' => $_POST['comment'] ?? null,
            'tbl_collation' => $_POST['tbl_collation'] ?? null,
            'charsets' => $charsetsList,
            'tbl_storage_engine' => $_POST['tbl_storage_engine'] ?? null,
            'storage_engines' => $storageEngines,
            'connection' => $_POST['connection'] ?? null,
            'change_column' => $_POST['change_column'] ?? $_GET['change_column'] ?? null,
            'is_virtual_columns_supported' => Util::isVirtualColumnsSupported(),
            'browse_mime' => $cfg['BrowseMIME'] ?? null,
            'server_type' => Util::getServerType(),
            'server_version' => $dbi->getVersion(),
            'max_rows' => intval($cfg['MaxRows']),
            'char_editing' => $cfg['CharEditing'] ?? null,
            'attribute_types' => $dbi->types->getAttributes(),
            'privs_available' => ($col_priv ?? false) && ($is_reload_priv ?? false),
            'max_length' => $dbi->getVersion() >= 50503 ? 1024 : 255,
            'have_partitioning' => Partition::havePartitioning(),
            'dbi' => $dbi,
            'disable_is' => $cfg['Server']['DisableIS'],
        ];
    }
}
