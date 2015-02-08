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
if (! isset($content_cells)) {
    $content_cells = array();
}


// Get available character sets and storage engines
require_once './libraries/mysql_charsets.inc.php';
require_once './libraries/StorageEngine.class.php';

/**
 * Class for partition management
 */
require_once './libraries/Partition.class.php';

require_once './libraries/tbl_columns_definition_form.lib.php';

/** @var PMA_String $pmaString */
$pmaString = $GLOBALS['PMA_String'];

$length_values_input_size = 8;

$_form_params = PMA_getFormsParameters(
    $db, $table, $action, isset($num_fields) ? $num_fields : null,
    isset($selected) ? $selected : null
);

$is_backup = ($action != 'tbl_create.php' && $action != 'tbl_addfield.php');

require_once './libraries/transformations.lib.php';
$cfgRelation = PMA_getRelationsParam();


$comments_map = PMA_getComments($db, $table);

if (isset($fields_meta)) {
    $move_columns = PMA_getMoveColumns($db, $table);
}

if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
    $mime_map = PMA_getMIME($db, $table);
    $available_mime = PMA_getAvailableMIMEtypes();
}

$header_cells = PMA_getHeaderCells(
    $is_backup, isset($fields_meta) ? $fields_meta : null,
    $cfgRelation['mimework'], $db, $table
);

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
    if (! empty($regenerate)) {
        list($columnMeta, $submit_length, $submit_attribute,
            $submit_default_current_timestamp, $comments_map, $mime_map)
                = PMA_handleRegeneration(
                    $columnNumber,
                    isset($available_mime) ? $mime_map : null,
                    $comments_map, $mime_map
                );
    } elseif (isset($fields_meta[$columnNumber])) {
        $columnMeta = PMA_getColumnMetaForDefault(
            $fields_meta[$columnNumber],
            isset($analyzed_sql[0]['create_table_fields']
            [$fields_meta[$columnNumber]['Field']]['default_value'])
        );
    }

    if (isset($columnMeta['Type'])) {
        $extracted_columnspec = PMA_Util::extractColumnSpec($columnMeta['Type']);
        if ($extracted_columnspec['type'] == 'bit') {
            $columnMeta['Default']
                = PMA_Util::convertBitDefaultValue($columnMeta['Default']);
        }
        $type = $extracted_columnspec['type'];
        $length = $extracted_columnspec['spec_in_brackets'];
    } else {
        // creating a column
        $columnMeta['Type'] = '';
        $type        = '';
        $length = '';
        $extracted_columnspec = array();
    }

    // some types, for example longtext, are reported as
    // "longtext character set latin7" when their charset and / or collation
    // differs from the ones of the corresponding database.
    $tmp = /*overload*/mb_strpos($type, 'character set');
    if ($tmp) {
        $type = /*overload*/mb_substr($type, 0, $tmp - 1);
    }
    // rtrim the type, for cases like "float unsigned"
    $type = rtrim($type);


    if (isset($submit_length) && $submit_length != false) {
        $length = $submit_length;
    }

    // Variable tell if current column is bound in a foreign key constraint or not.
    // MySQL version from 5.6.6 allow renaming columns with foreign keys
    if (isset($columnMeta['Field'])
        && isset($_form_params['table'])
        && PMA_MYSQL_INT_VERSION < 50606
    ) {
        $columnMeta['column_status'] = PMA_checkChildForeignReferences(
            $_form_params['db'],
            $_form_params['table'],
            $columnMeta['Field'],
            $foreigners,
            $child_references
        );
    }
    // old column attributes
    if ($is_backup) {
        $_form_params = PMA_getFormParamsForOldColumn(
            $columnMeta, $length, $_form_params, $columnNumber, $type,
            $extracted_columnspec
        );
    }

    $content_cells[$columnNumber] = PMA_getHtmlForColumnAttributes(
        $columnNumber, isset($columnMeta) ? $columnMeta : array(),
        /*overload*/mb_strtoupper($type), $length_values_input_size, $length,
        isset($default_current_timestamp) ? $default_current_timestamp : null,
        isset($extracted_columnspec) ? $extracted_columnspec : null,
        isset($submit_attribute) ? $submit_attribute : null,
        isset($analyzed_sql) ? $analyzed_sql : null,
        $comments_map, isset($fields_meta) ? $fields_meta : null, $is_backup,
        isset($move_columns) ? $move_columns : array(), $cfgRelation,
        isset($available_mime) ? $available_mime : array(),
        isset($mime_map) ? $mime_map : array()
    );
} // end for
$html = PMA_getHtmlForTableCreateOrAddField(
    $action, $_form_params, $content_cells, $header_cells
);

unset($_form_params);
$response = PMA_Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('jquery/jquery.uitablefilter.js');
$scripts->addFile('indexes.js');
$response->addHTML($html);
?>
