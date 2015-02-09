<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display table relations for viewing and editing
 *
 * includes phpMyAdmin relations and InnoDB relations
 *
 * @todo fix name handling: currently names with dots (.) are not properly handled
 * for internal relations (but foreign keys relations are correct)
 * @todo foreign key constraints require both fields being of equal type and size
 * @todo check foreign fields to be from same type and size, all other makes no sense
 * @todo if above todos are fullfilled we can add all fields meet requirements
 * in the select dropdown
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/index.lib.php';
require_once 'libraries/tbl_relation.lib.php';
require_once 'libraries/structure.lib.php';

$response = PMA_Response::getInstance();

// Send table of column names to populate corresponding dropdowns depending
// on the current selection
if (isset($_REQUEST['getDropdownValues'])
    && $_REQUEST['getDropdownValues'] === 'true'
) {
    PMA_sendHtmlForTableOrColumnDropdownList();
}

$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_relation.js');
$scripts->addFile('indexes.js');

/**
 * Gets tables informations
 */
require_once 'libraries/tbl_info.inc.php';

$options_array = array(
    'CASCADE'   => 'CASCADE',
    'SET_NULL'  => 'SET NULL',
    'NO_ACTION' => 'NO ACTION',
    'RESTRICT'  => 'RESTRICT',
);

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Updates
 */
if ($cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
    $existrel_foreign = PMA_getForeigners($db, $table, '', 'foreign');
}
if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
} else {
    $disp = '';
}

// will be used in the logic for internal relations and foreign keys:
$multi_edit_columns_name = isset($_REQUEST['fields_name'])
    ? $_REQUEST['fields_name']
    : null;


$html_output = '';

// u p d a t e s   f o r   I n t e r n a l    r e l a t i o n s
if (isset($_POST['destination_db']) && $cfgRelation['relwork']) {
    $html_output .= PMA_handleUpdatesForInternalRelations(
        $_POST['destination_db'], $multi_edit_columns_name,
        $_POST['destination_table'],
        $_POST['destination_column'], $cfgRelation, $db, $table,
        isset($existrel) ? $existrel : null
    );
} // end if (updates for internal relations)

$multi_edit_columns_name = isset($_REQUEST['foreign_key_fields_name'])
    ? $_REQUEST['foreign_key_fields_name']
    : null;

// u p d a t e s    f o r    f o r e i g n    k e y s
// (for now, one index name only; we keep the definitions if the
// foreign db is not the same)
if (isset($_POST['destination_foreign_db'])) {
    $html_output .= PMA_handleUpdatesForForeignKeys(
        $_POST['destination_foreign_db'],
        $multi_edit_columns_name, $_POST['destination_foreign_table'],
        $_POST['destination_foreign_column'], $options_array, $table,
        isset($existrel_foreign) ? $existrel_foreign['foreign_keys_data'] : null
    );
} // end if isset($destination_foreign)

// U p d a t e s   f o r   d i s p l a y   f i e l d
if ($cfgRelation['displaywork'] && isset($_POST['display_field'])) {
    $html_output .= PMA_handleUpdateForDisplayField(
        $disp, $_POST['display_field'], $db, $table, $cfgRelation
    );
} // end if

// If we did an update, refresh our data
if (isset($_POST['destination_db']) && $cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if (isset($_POST['destination_foreign_db'])
    && PMA_Util::isForeignKeySupported($tbl_storage_engine)
) {
    $existrel_foreign = PMA_getForeigners($db, $table, '', 'foreign');
}

if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}


// display secondary level tabs if necessary
$engine = PMA_Table::sGetStatusInfo($db, $table, 'ENGINE');
$response->addHTML(PMA_getStructureSecondaryTabs($engine));
$response->addHTML('<div id="structure_content">');

/**
 * Dialog
 */
// Now find out the columns of our $table
// need to use PMA_DatabaseInterface::QUERY_STORE with $GLOBALS['dbi']->numRows()
// in mysqli
$columns = $GLOBALS['dbi']->getColumns($db, $table);

// common form
$html_output .= PMA_getHtmlForCommonForm(
    $db, $table, $columns, $cfgRelation, $tbl_storage_engine,
    isset($existrel) ? $existrel : array(),
    isset($existrel_foreign) ? $existrel_foreign['foreign_keys_data'] : array(),
    $options_array
);

if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
    $html_output .= PMA_getHtmlForDisplayIndexes();
}
// Render HTML output
$response->addHTML($html_output);

$response->addHTML('</div>');
?>
