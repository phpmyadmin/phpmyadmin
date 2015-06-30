<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display form for changing/adding table fields/columns.
 * Included by tbl_addfield.php and tbl_create.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check parameters
 */
require_once './libraries/Util.class.php';
require_once './libraries/Template.class.php';

PMA_Util::checkParameters(array('server', 'db', 'table', 'action', 'num_fields'));

/**
 * Initialize to avoid code execution path warnings
 */

if (! isset($num_fields)) {
    $num_fields = 0;
}
if (! isset($mime_map)) {
    $mime_map = null;
}
if (! isset($columnMeta)) {
    $columnMeta = array();
}


// Get available character sets and storage engines
require_once './libraries/mysql_charsets.inc.php';
require_once './libraries/StorageEngine.class.php';

/**
 * Class for partition management
 */
require_once './libraries/Partition.class.php';

/** @var PMA_String $pmaString */
$pmaString = $GLOBALS['PMA_String'];

$length_values_input_size = 8;

$content_cells = array();

$form_params = array(
    'db' => $db
);

if ($action == 'tbl_create.php') {
    $form_params['reload'] = 1;
} elseif ($action == 'tbl_addfield.php') {
    if (isset($_REQUEST['field_where'])) {
        $form_params['field_where'] = $_REQUEST['field_where'];
    }
    if (isset($_REQUEST['field_where'])) {
        $form_params['after_field'] = $_REQUEST['after_field'];
    }
    $form_params['table'] = $table;
} else {
    $form_params['table'] = $table;
}

if (isset($num_fields)) {
    $form_params['orig_num_fields'] = $num_fields;
}

if (isset($_REQUEST['field_where'])) {
    $form_params['orig_field_where'] = $_REQUEST['field_where'];
}

if (isset($_REQUEST['after_field'])) {
    $form_params['orig_after_field'] = $_REQUEST['after_field'];
}

if (isset($selected) && is_array($selected)) {
    foreach ($selected as $o_fld_nr => $o_fld_val) {
        $form_params['selected[' . $o_fld_nr . ']'] = $o_fld_val;
    }
}

$is_backup = ($action != 'tbl_create.php' && $action != 'tbl_addfield.php');

require_once './libraries/transformations.lib.php';
$cfgRelation = PMA_getRelationsParam();


$comments_map = PMA_getComments($db, $table);

$move_columns = array();
if (isset($fields_meta)) {
    $move_columns = $GLOBALS['dbi']->getTable($db, $table)->getColumnsMeta();
}

$available_mime = array();
if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
    $mime_map = PMA_getMIME($db, $table);
    $available_mime = PMA_getAvailableMIMEtypes();
}

//  workaround for field_fulltext, because its submitted indices contain
//  the index as a value, not a key. Inserted here for easier maintenance
//  and less code to change in existing files.
if (isset($field_fulltext) && is_array($field_fulltext)) {
    foreach ($field_fulltext as $fulltext_nr => $fulltext_indexkey) {
        $submit_fulltext[$fulltext_indexkey] = $fulltext_indexkey;
    }
}
if (isset($_REQUEST['submit_num_fields'])) {
    //if adding new fields, set regenerate to keep the original values
    $regenerate = 1;
}

$foreigners = PMA_getForeigners($db, $table, '', 'foreign');
$child_references = null;
// From MySQL 5.6.6 onwards columns with foreign keys can be renamed.
// Hence, no need to get child references
if (PMA_MYSQL_INT_VERSION < 50606) {
    $child_references = PMA_getChildReferences($db, $table);
}

for ($columnNumber = 0; $columnNumber < $num_fields; $columnNumber++) {

    $type = '';
    $length = '';
    $columnMeta = array();
    $submit_attribute = null;
    $extracted_columnspec = array();

    if (!empty($regenerate)) {
        $columnMeta['Field'] = isset($_REQUEST['field_name'][$columnNumber])
            ? $_REQUEST['field_name'][$columnNumber]
            : false;
        $columnMeta['Type'] = isset($_REQUEST['field_type'][$columnNumber])
            ? $_REQUEST['field_type'][$columnNumber]
            : false;
        $columnMeta['Collation'] = isset($_REQUEST['field_collation'][$columnNumber])
            ? $_REQUEST['field_collation'][$columnNumber]
            : '';
        $columnMeta['Null'] = isset($_REQUEST['field_null'][$columnNumber])
            ? $_REQUEST['field_null'][$columnNumber]
            : '';

        $columnMeta['Key'] = '';
        if (isset($_REQUEST['field_key'][$columnNumber])) {
            $parts = explode('_', $_REQUEST['field_key'][$columnNumber], 2);
            if (count($parts) == 2 && $parts[1] == $columnNumber) {
                switch ($parts[0]) {
                    case 'primary':
                        $columnMeta['Key'] = 'PRI';
                        break;
                    case 'index':
                        $columnMeta['Key'] = 'MUL';
                        break;
                    case 'unique':
                        $columnMeta['Key'] = 'UNI';
                        break;
                    case 'fulltext':
                        $columnMeta['Key'] = 'FULLTEXT';
                        break;
                    case 'spatial':
                        $columnMeta['Key'] = 'SPATIAL';
                        break;
                }
            }
        }

        // put None in the drop-down for Default, when someone adds a field
        $columnMeta['DefaultType']
            = isset($_REQUEST['field_default_type'][$columnNumber])
            ? $_REQUEST['field_default_type'][$columnNumber]
            : 'NONE';
        $columnMeta['DefaultValue']
            = isset($_REQUEST['field_default_value'][$columnNumber])
            ? $_REQUEST['field_default_value'][$columnNumber]
            : '';

        switch ($columnMeta['DefaultType']) {
            case 'NONE':
                $columnMeta['Default'] = null;
                break;
            case 'USER_DEFINED':
                $columnMeta['Default'] = $columnMeta['DefaultValue'];
                break;
            case 'NULL':
            case 'CURRENT_TIMESTAMP':
                $columnMeta['Default'] = $columnMeta['DefaultType'];
                break;
        }

        $columnMeta['Extra']
            = (isset($_REQUEST['field_extra'][$columnNumber])
            ? $_REQUEST['field_extra'][$columnNumber]
            : false);
        $columnMeta['Comment']
            = (isset($submit_fulltext[$columnNumber])
                && ($submit_fulltext[$columnNumber] == $columnNumber)
            ? 'FULLTEXT'
            : false);

        $length
            = (isset($_REQUEST['field_length'][$columnNumber])
            ? $_REQUEST['field_length'][$columnNumber]
            : $length);

        $submit_attribute
            = (isset($_REQUEST['field_attribute'][$columnNumber])
            ? $_REQUEST['field_attribute'][$columnNumber]
            : false);

        if (isset($_REQUEST['field_comments'][$columnNumber])) {
            $comments_map[$columnMeta['Field']]
                = $_REQUEST['field_comments'][$columnNumber];
        }

        if (isset($_REQUEST['field_mimetype'][$columnNumber])) {
            $mime_map[$columnMeta['Field']]['mimetype']
                = $_REQUEST['field_mimetype'][$columnNumber];
        }

        if (isset($_REQUEST['field_transformation'][$columnNumber])) {
            $mime_map[$columnMeta['Field']]['transformation']
                = $_REQUEST['field_transformation'][$columnNumber];
        }

        if (isset($_REQUEST['field_transformation_options'][$columnNumber])) {
            $mime_map[$columnMeta['Field']]['transformation_options']
                = $_REQUEST['field_transformation_options'][$columnNumber];
        }

    }
    elseif (isset($fields_meta[$columnNumber]))
    {
        $columnMeta = $fields_meta[$columnNumber];
        switch ($columnMeta['Default']) {
            case null:
                if (is_null($columnMeta['Default'])) { // null
                    if ($columnMeta['Null'] == 'YES') {
                        $columnMeta['DefaultType']  = 'NULL';
                        $columnMeta['DefaultValue'] = '';
                    } else {
                        $columnMeta['DefaultType']  = 'NONE';
                        $columnMeta['DefaultValue'] = '';
                    }
                } else { // empty
                    $columnMeta['DefaultType']  = 'USER_DEFINED';
                    $columnMeta['DefaultValue'] = $columnMeta['Default'];
                }
                break;
            case 'CURRENT_TIMESTAMP':
                $columnMeta['DefaultType']  = 'CURRENT_TIMESTAMP';
                $columnMeta['DefaultValue'] = '';
                break;
            default:
                $columnMeta['DefaultType']  = 'USER_DEFINED';
                $columnMeta['DefaultValue'] = $columnMeta['Default'];
                break;
        }
    }

    if (isset($columnMeta['Type'])) {
        $extracted_columnspec = PMA_Util::extractColumnSpec($columnMeta['Type']);
        if ($extracted_columnspec['type'] == 'bit') {
            $columnMeta['Default']
                = PMA_Util::convertBitDefaultValue($columnMeta['Default']);
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
        && PMA_MYSQL_INT_VERSION < 50606
    ) {
        $columnMeta['column_status'] = PMA_checkChildForeignReferences(
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
    $type = rtrim(mb_ereg_replace(
        '[\w\W]character set[\w\W]*', '', $type
    ));

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
            $form_params['field_type_orig[' . $columnNumber . ']']
                = /*overload*/mb_strtoupper($type);
            if (isset($columnMeta['column_status'])
                && !$columnMeta['column_status']['isEditable']
            ) {
                $form_params['field_type[' . $columnNumber . ']']
                    = /*overload*/mb_strtoupper($type);
            }
        } else {
            $form_params['field_type_orig[' . $columnNumber . ']'] = '';
        }

        // old column length
        $form_params['field_length_orig[' . $columnNumber . ']'] = $length;

        // old column default
        $form_params['field_default_value_orig[' . $columnNumber . ']']
            = (isset($columnMeta['Default']) ? $columnMeta['Default'] : '');
        $form_params['field_default_type_orig[' . $columnNumber . ']']
            = (isset($columnMeta['DefaultType']) ? $columnMeta['DefaultType'] : '');

        // old column collation
        if (isset($columnMeta['Collation'])) {
            $form_params['field_collation_orig[' . $columnNumber . ']']
                = $columnMeta['Collation'];
        } else {
            $form_params['field_collation_orig[' . $columnNumber . ']'] = '';
        }

        // old column attribute
        if (isset($extracted_columnspec['attribute'])) {
            $form_params['field_attribute_orig[' . $columnNumber . ']']
                = trim($extracted_columnspec['attribute']);
        } else {
            $form_params['field_attribute_orig[' . $columnNumber . ']'] = '';
        }

        // old column null
        if (isset($columnMeta['Null'])) {
            $form_params['field_null_orig[' . $columnNumber . ']']
                = $columnMeta['Null'];
        } else {
            $form_params['field_null_orig[' . $columnNumber . ']'] = '';
        }

        // old column extra (for auto_increment)
        if (isset($columnMeta['Extra'])) {
            $form_params['field_extra_orig[' . $columnNumber . ']']
                = $columnMeta['Extra'];
        } else {
            $form_params['field_extra_orig[' . $columnNumber . ']'] = '';
        }

        // old column comment
        if (isset($columnMeta['Comment'])) {
            $form_params['field_comments_orig[' . $columnNumber . ']']
                = $columnMeta['Comment'];
        } else {
            $form_params['field_comment_orig[' . $columnNumber . ']'] = '';
        }
    }

    $content_cells[$columnNumber] = array(
        'columnNumber' => $columnNumber,
        'columnMeta' => $columnMeta,
        'type_upper' => /*overload*/mb_strtoupper($type),
        'length_values_input_size' => $length_values_input_size,
        'length' => $length,
        'extracted_columnspec' => $extracted_columnspec,
        'submit_attribute' => $submit_attribute,
        'comments_map' => $comments_map,
        'fields_meta' => isset($fields_meta) ? $fields_meta : null,
        'is_backup' => $is_backup,
        'move_columns' => $move_columns,
        'cfgRelation' => $cfgRelation,
        'available_mime' => $available_mime,
        'mime_map' => isset($mime_map) ? $mime_map : array()
    );
} // end for

$html = PMA\Template::get('columns_definitions/column_definitions_form')
    ->render(array(
        'is_backup' => $is_backup,
        'fields_meta' => isset($fields_meta) ? $fields_meta : null,
        'mimework' => $cfgRelation['mimework'],
        'action' => $action,
        'form_params' => $form_params,
        'content_cells' => $content_cells,
        'privs_available' => $privs_available
    ));

unset($form_params);

$response = PMA_Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('jquery/jquery.uitablefilter.js');
$scripts->addFile('indexes.js');
$response->addHTML($html);
?>
