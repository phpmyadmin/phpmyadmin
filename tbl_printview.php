<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Print view for table
 *
 * @package PhpMyAdmin
 */

/**
 * include the common file
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$response->getHeader()->enablePrintView();

require 'libraries/tbl_common.inc.php';

// Check parameters

if (! isset($the_tables) || ! is_array($the_tables)) {
    $the_tables = array();
}

/**
 * Gets the relations settings
 */
require_once 'libraries/transformations.lib.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/tbl_printview.lib.php';

$cfgRelation = PMA_getRelationsParam();

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (/*overload*/mb_strlen($table)) {
    $err_url = 'tbl_sql.php?' . PMA_URL_getCommon(
        array('db' => $db, 'table' => $table)
    );
} else {
    $err_url = 'db_sql.php' . PMA_URL_getCommon(array('db' => $db));
}


/**
 * Selects the database
 */
$GLOBALS['dbi']->selectDb($db);

/**
 * Multi-tables printview
 */
if (isset($_POST['selected_tbl']) && is_array($_POST['selected_tbl'])) {
    $the_tables   = $_POST['selected_tbl'];
} elseif (/*overload*/mb_strlen($table)) {
    $the_tables[] = $table;
}

$response->addHTML(PMA_getHtmlForTablesInfo($the_tables));
$response->addHTML(
    PMA_getHtmlForTablesDetail(
        $the_tables, $db, $cfg, $cfgRelation,
        $cell_align_left
    )
);

/**
 * Displays the footer
 */
$response->addHTML(PMA_getHtmlForPrintViewFooter());

exit;
?>
