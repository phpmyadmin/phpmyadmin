<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display form for changing/adding table fields/columns.
 * Included by tbl_addfield.php and tbl_create.php
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Partition;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check parameters
 */
Util::checkParameters(
    array('server', 'db', 'table', 'action', 'num_fields')
);

global $db, $table;

$relation = new Relation();

/**
 * Initialize to avoid code execution path warnings
 */

if (!isset($num_fields)) {
    $num_fields = 0;
}
if (!isset($mime_map)) {
    $mime_map = null;
}
if (!isset($columnMeta)) {
    $columnMeta = array();
}

$length_values_input_size = 8;

$content_cells = array();

/** @var string $db */
$form_params = array(
    'db' => $db
);

if ($action == 'tbl_create.php') {
    $form_params['reload'] = 1;
} else {
    if ($action == 'tbl_addfield.php') {
        $form_params = array_merge(
            $form_params, array(
            'field_where' => Util::getValueByKey($_POST, 'field_where'))
        );
        if (isset($_POST['field_where'])) {
            $form_params['after_field'] = $_POST['after_field'];
        }
    }
    $form_params['table'] = $table;
}

if (isset($num_fields)) {
    $form_params['orig_num_fields'] = $num_fields;
}

$form_params = array_merge(
    $form_params,
    array(
        'orig_field_where' => Util::getValueByKey($_POST, 'field_where'),
        'orig_after_field' => Util::getValueByKey($_POST, 'after_field'),
    )
);

if (isset($selected) && is_array($selected)) {
    foreach ($selected as $o_fld_nr => $o_fld_val) {
        $form_params['selected[' . $o_fld_nr . ']'] = $o_fld_val;
    }
}

$is_backup = ($action != 'tbl_create.php' && $action != 'tbl_addfield.php');

$cfgRelation = $relation->getRelationsParam();

$comments_map = $relation->getComments($db, $table);

$move_columns = array();
if (isset($fields_meta)) {
    /** @var PhpMyAdmin\DatabaseInterface $dbi */
    $dbi = Container::getDefaultContainer()->get('dbi');
    $move_columns = $dbi->getTable($db, $table)->getColumnsMeta();
}

$available_mime = array();
if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
    $mime_map = Transformations::getMIME($db, $table);
    $available_mime = Transformations::getAvailableMIMEtypes();
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
if ($GLOBALS['dbi']->getVersion() < 50606) {
    $child_references = $relation->getChildReferences($db, $table);
}

for ($columnNumber = 0; $columnNumber < $num_fields; $columnNumber++) {

    $type = '';
    $length = '';
    $columnMeta = array();
    $submit_attribute = null;
    $extracted_columnspec = array();

    if (!empty($regenerate)) {

        $columnMeta = array_merge(
            $columnMeta,
            array(
                'Field'        => Util::getValueByKey(
                    $_POST, "field_name.${columnNumber}", false
                ),
                'Type'         => Util::getValueByKey(
                    $_POST, "field_type.${columnNumber}", false
                ),
                'Collation'    => Util::getValueByKey(
                    $_POST, "field_collation.${columnNumber}", ''
                ),
                'Null'         => Util::getValueByKey(
                    $_POST, "field_null.${columnNumber}", ''
                ),
                'DefaultType'  => Util::getValueByKey(
                    $_POST, "field_default_type.${columnNumber}", 'NONE'
                ),
                'DefaultValue' => Util::getValueByKey(
                    $_POST, "field_default_value.${columnNumber}", ''
                ),
                'Extra'        => Util::getValueByKey(
                    $_POST, "field_extra.${columnNumber}", false
                ),
                'Virtuality'   => Util::getValueByKey(
                    $_POST, "field_virtuality.${columnNumber}", ''
                ),
                'Expression'   => Util::getValueByKey(
                    $_POST, "field_expression.${columnNumber}", ''
                ),
            )
        );

        $columnMeta['Key'] = '';
        $parts = explode(
            '_', Util::getValueByKey($_POST, "field_key.${columnNumber}", ''), 2
        );
        if (count($parts) == 2 && $parts[1] == $columnNumber) {
            $columnMeta['Key'] = Util::getValueByKey(
                array(
                    'primary' => 'PRI',
                    'index' => 'MUL',
                    'unique' => 'UNI',
                    'fulltext' => 'FULLTEXT',
                    'spatial' => 'SPATIAL'
                ),
                $parts[0], ''
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
            $_POST, "field_attribute.${columnNumber}", false
        );
        $comments_map[$columnMeta['Field']] = Util::getValueByKey(
            $_POST, "field_comments.${columnNumber}"
        );

        $mime_map[$columnMeta['Field']] = array_merge(
            $mime_map[$columnMeta['Field']],
            array(
                'mimetype' => Util::getValueByKey($_POST, "field_mimetype.${$columnNumber}"),
                'transformation' => Util::getValueByKey(
                    $_POST, "field_transformation.${$columnNumber}"
                ),
                'transformation_options' => Util::getValueByKey(
                    $_POST, "field_transformation_options.${$columnNumber}"
                ),
            )
        );

    } elseif (isset($fields_meta[$columnNumber])) {
        $columnMeta = $fields_meta[$columnNumber];
        $virtual = array(
            'VIRTUAL', 'PERSISTENT', 'VIRTUAL GENERATED', 'STORED GENERATED'
        );
        if (in_array($columnMeta['Extra'], $virtual)) {
            $tableObj = new Table($GLOBALS['table'], $GLOBALS['db']);
            $expressions = $tableObj->getColumnGenerationExpression(
                $columnMeta['Field']
            );
            $columnMeta['Expression'] = $expressions[$columnMeta['Field']];
        }
        switch ($columnMeta['Default']) {
        case null:
            if (is_null($columnMeta['Default'])) { // null
                if ($columnMeta['Null'] == 'YES') {
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

            if ('text' === substr($columnMeta['Type'], -4)) {
                $textDefault = substr($columnMeta['Default'], 1, -1);
                $columnMeta['Default'] = stripcslashes($textDefault !== false ? $textDefault : $columnMeta['Default']);
            }

            $columnMeta['DefaultValue'] = $columnMeta['Default'];
            break;
        }
    }

    if (isset($columnMeta['Type'])) {
        $extracted_columnspec = Util::extractColumnSpec(
            $columnMeta['Type']
        );
        if ($extracted_columnspec['type'] == 'bit') {
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
    if (isset($columnMeta['Field'])
        && isset($form_params['table'])
        && $GLOBALS['dbi']->getVersion() < 50606
    ) {
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
                && !$columnMeta['column_status']['isEditable']
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
                && !$columnMeta['column_status']['isEditable']
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
            array(
                "field_default_value_orig[${columnNumber}]" => Util::getValueByKey(
                    $columnMeta, 'Default', ''
                ),
                "field_default_type_orig[${columnNumber}]"  => Util::getValueByKey(
                    $columnMeta, 'DefaultType', ''
                ),
                "field_collation_orig[${columnNumber}]"     => Util::getValueByKey(
                    $columnMeta, 'Collation', ''
                ),
                "field_attribute_orig[${columnNumber}]"     => trim(
                    Util::getValueByKey($extracted_columnspec, 'attribute', '')
                ),
                "field_null_orig[${columnNumber}]"          => Util::getValueByKey(
                    $columnMeta, 'Null', ''
                ),
                "field_extra_orig[${columnNumber}]"         => Util::getValueByKey(
                    $columnMeta, 'Extra', ''
                ),
                "field_comments_orig[${columnNumber}]"      => Util::getValueByKey(
                    $columnMeta, 'Comment', ''
                ),
                "field_virtuality_orig[${columnNumber}]"    => Util::getValueByKey(
                    $columnMeta, 'Virtuality', ''
                ),
                "field_expression_orig[${columnNumber}]"    => Util::getValueByKey(
                    $columnMeta, 'Expression', ''
                ),
            )
        );
    }

    $content_cells[$columnNumber] = array(
        'column_number' => $columnNumber,
        'column_meta' => $columnMeta,
        'type_upper' => mb_strtoupper($type),
        'length_values_input_size' => $length_values_input_size,
        'length' => $length,
        'extracted_columnspec' => $extracted_columnspec,
        'submit_attribute' => $submit_attribute,
        'comments_map' => $comments_map,
        'fields_meta' => isset($fields_meta) ? $fields_meta : null,
        'is_backup' => $is_backup,
        'move_columns' => $move_columns,
        'cfg_relation' => $cfgRelation,
        'available_mime' => $available_mime,
        'mime_map' => isset($mime_map) ? $mime_map : array()
    );
} // end for

include 'libraries/tbl_partition_definition.inc.php';
$html = Template::get('columns_definitions/column_definitions_form')->render([
    'is_backup' => $is_backup,
    'fields_meta' => isset($fields_meta) ? $fields_meta : null,
    'mimework' => $cfgRelation['mimework'],
    'action' => $action,
    'form_params' => $form_params,
    'content_cells' => $content_cells,
    'partition_details' => $partitionDetails,
    'primary_indexes' => isset($_POST['primary_indexes']) ? $_POST['primary_indexes'] : null,
    'unique_indexes' => isset($_POST['unique_indexes']) ? $_POST['unique_indexes'] : null,
    'indexes' => isset($_POST['indexes']) ? $_POST['indexes'] : null,
    'fulltext_indexes' => isset($_POST['fulltext_indexes']) ? $_POST['fulltext_indexes'] : null,
    'spatial_indexes' => isset($_POST['spatial_indexes']) ? $_POST['spatial_indexes'] : null,
    'table' => isset($_POST['table']) ? $_POST['table'] : null,
    'comment' => isset($_POST['comment']) ? $_POST['comment'] : null,
    'tbl_collation' => isset($_POST['tbl_collation']) ? $_POST['tbl_collation'] : null,
    'tbl_storage_engine' => isset($_POST['tbl_storage_engine']) ? $_POST['tbl_storage_engine'] : null,
    'connection' => isset($_POST['connection']) ? $_POST['connection'] : null,
    'change_column' => isset($_POST['change_column']) ? $_POST['change_column'] : null,
    'is_virtual_columns_supported' => Util::isVirtualColumnsSupported(),
    'browse_mime' => isset($GLOBALS['cfg']['BrowseMIME']) ? $GLOBALS['cfg']['BrowseMIME'] : null,
    'server_type' => Util::getServerType(),
    'max_rows' => intval($GLOBALS['cfg']['MaxRows']),
    'char_editing' => isset($GLOBALS['cfg']['CharEditing']) ? $GLOBALS['cfg']['CharEditing'] : null,
    'attribute_types' => $GLOBALS['dbi']->types->getAttributes(),
    'privs_available' => (isset($GLOBALS['col_priv']) ? $GLOBALS['col_priv'] : false
        && isset($GLOBALS['is_reload_priv']) ? $GLOBALS['is_reload_priv'] : false
    ),
    'max_length' => $GLOBALS['dbi']->getVersion() >= 50503 ? 1024 : 255,
    'have_partitioning' => Partition::havePartitioning(),
    'dbi' => $GLOBALS['dbi'],
    'disable_is' => $GLOBALS['cfg']['Server']['DisableIS'],
]);

unset($form_params);

$response = Response::getInstance();
$response->getHeader()->getScripts()->addFiles(
    array(
        'vendor/jquery/jquery.uitablefilter.js',
        'indexes.js'
    )
);
$response->addHTML($html);
