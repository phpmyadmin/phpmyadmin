<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets tables informations and displays top links
 */
require('./tbl_properties_common.php');
$url_query .= '&amp;goto=tbl_properties_export.php&amp;back=tbl_properties_export.php';
require('./tbl_properties_table_info.php');
?>

<!-- Dump of a table -->
<h2>
    <?php echo $strViewDump . "\n"; ?>
</h2>

<?php
if (isset($sql_query)) {
    // I don't want the LIMIT clause, so I use the analyzer
    // to reconstruct the query with only some parts
    // because the LIMIT clause may come from us (sql.php, sql_limit_to_append
    // or may come from the user.
    // Then, the limits set in the form will be added.
    // TODO: do we need some other parts here, like PROCEDURE or FOR UPDATE?

    $parsed_sql = PMA_SQP_parse($sql_query);
    $analyzed_sql = PMA_SQP_analyze($parsed_sql);
    $sql_query = 'SELECT ';

    if (isset($analyzed_sql[0]['queryflags']['distinct'])) {
        $sql_query .= ' DISTINCT ';
    }

    $sql_query .= $analyzed_sql[0]['select_expr_clause'];

    if (!empty($analyzed_sql[0]['from_clause'])) {
        $sql_query .= ' FROM ' . $analyzed_sql[0]['from_clause'];
    }
    if (!empty($analyzed_sql[0]['where_clause'])) {
        $sql_query .= ' WHERE ' . $analyzed_sql[0]['where_clause'];
    }
    if (!empty($analyzed_sql[0]['group_by_clause'])) {
        $sql_query .= ' GROUP BY ' . $analyzed_sql[0]['group_by_clause'];
    }
    if (!empty($analyzed_sql[0]['having_clause'])) {
        $sql_query .= ' HAVING ' . $analyzed_sql[0]['having_clause'];
    }
    if (!empty($analyzed_sql[0]['order_by_clause'])) {
        $sql_query .= ' ORDER BY ' . $analyzed_sql[0]['order_by_clause'];
    }

    // TODO: can we avoid reparsing the query here?
    PMA_showMessage($GLOBALS['strSQLQuery']);
}

$export_type = 'table';
require_once('./libraries/display_export.lib.php');


/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
