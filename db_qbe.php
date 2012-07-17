<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * query by example the whole database
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/db_qbe.lib.php';

/**
 * Sets globals from $_POST patterns, for Or* variables 
 * (additional criteria lines)
 */

$post_patterns = array(
    '/^Or/i'
);
foreach (array_keys($_POST) as $post_key) {
    foreach ($post_patterns as $one_post_pattern) {
        if (preg_match($one_post_pattern, $post_key)) {
            $GLOBALS[$post_key] = $_POST[$post_key];
        }
    }
}
/**
 * Initialize some more global variables
 */
$GLOBALS['curField'] = array();
$GLOBALS['curSort'] = array();
$GLOBALS['curShow'] = array();
$GLOBALS['curCriteria'] = array();
$GLOBALS['curAndOrRow'] = array();
$GLOBALS['curAndOrCol'] = array();

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

$common_functions = PMA_CommonFunctions::getInstance();

/**
 * A query has been submitted -> (maybe) execute it
 */
$message_to_display = false;
if (isset($_REQUEST['submit_sql']) && ! empty($sql_query)) {
    if (! preg_match('@^SELECT@i', $sql_query)) {
        $message_to_display = true;
    } else {
        $goto      = 'db_sql.php';
        include 'sql.php';
        exit;
    }
}

$sub_part  = '_qbe';
require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_qbe.php';
$url_params['goto'] = 'db_qbe.php';
require 'libraries/db_info.inc.php';

if ($message_to_display) {
    PMA_Message::error(__('You have to choose at least one column to display'))->display();
}
unset($message_to_display);

/**
 * Initialize some variables
 */
$criteriaColumnCount = PMA_ifSetOr($_REQUEST['criteriaColumnCount'], 3, 'numeric');
$criteriaColumnAdd = PMA_ifSetOr($_REQUEST['criteriaColumnAdd'], 0, 'numeric');
$criteriaRowAdd = PMA_ifSetOr($_REQUEST['criteriaRowAdd'], 0, 'numeric');

$rows    = PMA_ifSetOr($_REQUEST['rows'],    0, 'numeric');
$criteriaColumnInsert = PMA_ifSetOr($_REQUEST['criteriaColumnInsert'], null, 'array');
$criteriaColumnDelete = PMA_ifSetOr($_REQUEST['criteriaColumnDelete'], null, 'array');

$prev_criteria = isset($_REQUEST['prev_criteria'])
    ? $_REQUEST['prev_criteria']
    : array();
$criteria = isset($_REQUEST['criteria'])
    ? $_REQUEST['criteria']
    : array_fill(0, $criteriaColumnCount, '');

$criteriaRowInsert = isset($_REQUEST['criteriaRowInsert'])
    ? $_REQUEST['criteriaRowInsert']
    : array_fill(0, $criteriaColumnCount, '');
$criteriaRowDelete = isset($_REQUEST['criteriaRowDelete'])
    ? $_REQUEST['criteriaRowDelete']
    : array_fill(0, $criteriaColumnCount, '');
$criteriaAndOrRow = isset($_REQUEST['criteriaAndOrRow'])
    ? $_REQUEST['criteriaAndOrRow']
    : array_fill(0, $criteriaColumnCount, '');
$criteriaAndOrColumn = isset($_REQUEST['criteriaAndOrColumn'])
    ? $_REQUEST['criteriaAndOrColumn']
    : array_fill(0, $criteriaColumnCount, '');

// minimum width
$form_column_width = 12;
$criteria_column_count = max($criteriaColumnCount + $criteriaColumnAdd, 0);
$criteria_row_count = max($rows + $criteriaRowAdd, 0);


// The tables list sent by a previously submitted form
if (PMA_isValid($_REQUEST['TableList'], 'array')) {
    foreach ($_REQUEST['TableList'] as $each_table) {
        $tbl_names[$each_table] = ' selected="selected"';
    }
} // end if


// this was a work in progress, deactivated for now
//$columns = PMA_DBI_get_columns_full($GLOBALS['db']);
//$tables  = PMA_DBI_get_columns_full($GLOBALS['db']);


/**
 * Prepares the form
 */
$tbl_result = PMA_DBI_query(
    'SHOW TABLES FROM ' . $common_functions->backquote($db) . ';',
    null, PMA_DBI_QUERY_STORE
);
$tbl_result_cnt = PMA_DBI_num_rows($tbl_result);
if (0 == $tbl_result_cnt) {
    PMA_Message::error(__('No tables found in database.'))->display();
    exit;
}

// The tables list gets from MySQL
while (list($tbl) = PMA_DBI_fetch_row($tbl_result)) {
    $fld_results = PMA_DBI_get_columns($db, $tbl);

    if (empty($tbl_names[$tbl]) && ! empty($_REQUEST['TableList'])) {
        $tbl_names[$tbl] = '';
    } else {
        $tbl_names[$tbl] = ' selected="selected"';
    } //  end if

    // The fields list per selected tables
    if ($tbl_names[$tbl] == ' selected="selected"') {
        $each_table = $common_functions->backquote($tbl);
        $fld[]  = $each_table . '.*';
        foreach ($fld_results as $each_field) {
            $each_field = $each_table . '.' . $common_functions->backquote($each_field['Field']);
            $fld[] = $each_field;

            // increase the width if necessary
            $form_column_width = max(strlen($each_field), $form_column_width);
        } // end foreach
    } // end if
} // end while
PMA_DBI_free_result($tbl_result);

// largest width found
$realwidth = $form_column_width . 'ex';

/**
 * Displays the Query by example form
 */

if ($cfgRelation['designerwork']) {
    $url = 'pmd_general.php' . PMA_generate_common_url(
        array_merge(
            $url_params,
            array('query' => 1)
        )
    );
    PMA_Message::notice(
        sprintf(
            __('Switch to %svisual builder%s'),
            '<a href="' . $url . '">',
            '</a>'
        )
    )->display();
}
?>
<form action="db_qbe.php" method="post">
<fieldset>
<table class="data" style="width: 100%;">
<?php
echo PMA_dbQbegetColumnNamesRow(
    $criteria_column_count, $fld, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbegetSortRow(
    $criteria_column_count, $realwidth, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbegetShowRow(
    $criteria_column_count, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbegetCriteriaInputboxRow(
    $criteria_column_count, $realwidth, $criteria, $prev_criteria, $criteriaColumnInsert, $criteriaColumnDelete
);
echo PMA_dbQbeGetInsDelAndOrCriteriaRows($criteria_row_count, $criteria_column_count, $realwidth,
    $criteriaColumnInsert, $criteriaColumnDelete, $criteriaAndOrRow
);
echo PMA_dbQbeGetModifyColumnsRow(
    $criteria_column_count, $criteriaAndOrColumn, $criteriaColumnInsert, $criteriaColumnDelete 
);
?>
</table>
<?php
$new_row_count--;
$url_params['db']       = $db;
$url_params['criteriaColumnCount']  = $new_column_count;
$url_params['rows']     = $new_row_count;
echo PMA_generate_common_hidden_inputs($url_params);
?>
</fieldset>

<?php
echo PMA_dbQbeGetTableFooters();
echo PMA_dbQbeGetTablesList($tbl_names);
?>

<div class="floatleft">
    <fieldset>
        <legend><?php echo sprintf(__('SQL query on database <b>%s</b>:'), $common_functions->getDbLink($db)); ?>
            </legend>
        <textarea cols="80" name="sql_query" id="textSqlquery"
            rows="<?php echo ($numTableListOptions > 30) ? '15' : '7'; ?>"
            dir="<?php echo $text_dir; ?>">
<?php
echo PMA_dbQbeGetSQLQuery(
    $criteria_column_count, $criteria_row_count, $criteria, $cfgRelation
);
?>
    </textarea>
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" name="submit_sql" value="<?php echo __('Submit Query'); ?>" />
    </fieldset>
</div>
</form>

