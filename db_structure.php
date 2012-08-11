<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

/**
 * Function implementations for this script
 */
require_once 'libraries/structure.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('db_structure.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('jquery/timepicker.js');
$common_functions = PMA_CommonFunctions::getInstance();

/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is true)
 */
if (empty($_POST['is_info'])) {
    // Drops/deletes/etc. multiple tables if required
    if ((!empty($_POST['submit_mult']) && isset($_POST['selected_tbl']))
        || isset($_POST['mult_btn'])
    ) {
        $action = 'db_structure.php';
        $err_url = 'db_structure.php?'. PMA_generate_common_url($db);

        // see bug #2794840; in this case, code path is:
        // db_structure.php -> libraries/mult_submits.inc.php -> sql.php
        // -> db_structure.php and if we got an error on the multi submit,
        // we must display it here and not call again mult_submits.inc.php
        if (! isset($_POST['error']) || false === $_POST['error']) {
            include 'libraries/mult_submits.inc.php';
        }
        if (empty($_POST['message'])) {
            $_POST['message'] = PMA_Message::success();
        }
    }
    include 'libraries/db_common.inc.php';
    $url_query .= '&amp;goto=db_structure.php';

    // Gets the database structure
    $sub_part = '_structure';
    include 'libraries/db_info.inc.php';

    if (!PMA_DRIZZLE) {
        include_once 'libraries/replication.inc.php';
    } else {
        $server_slave_status = false;
    }
}

require_once 'libraries/bookmark.lib.php';

require_once 'libraries/mysql_charsets.lib.php';
$db_collation = PMA_getDbCollation($db);

$titles = $common_functions->buildActionTitles();

// 1. No tables

if ($num_tables == 0) {
    $response->addHTML(
        '<p>' . __('No tables found in database') . '</p>' . "\n"
    );
    if (empty($db_is_information_schema)) {
        ob_start();
        include 'libraries/display_create_table.lib.php';
        $content = ob_get_contents();
        ob_end_clean();
        $response->addHTML($content);
        unset($content);
    } // end if (Create Table dialog)
    exit;
}

// else
// 2. Shows table informations

/**
 * Displays the tables list
 */
$response->addHTML('<div id="tableslistcontainer">');
$_url_params = array(
    'pos' => $pos,
    'db'  => $db);

// Add the sort options if they exists
if (isset($_REQUEST['sort'])) {
    $_url_params['sort'] = $_REQUEST['sort'];
}

if (isset($_REQUEST['sort_order'])) {
    $_url_params['sort_order'] = $_REQUEST['sort_order'];
}

$response->addHTML($common_functions->getListNavigator(
        $total_num_tables, $pos, $_url_params, 'db_structure.php',
        'frame_content', $GLOBALS['cfg']['MaxTableList']
    )
);

// tables form
$response->addHTML(
    '<form method="post" action="db_structure.php" '
        . 'name="tablesForm" id="tablesForm">'
);

$response->addHTML(PMA_generate_common_hidden_inputs($db));

$response->addHTML(
    PMA_TableHeader($db_is_information_schema, $server_slave_status)
);

$i = $sum_entries = 0;
$overhead_check = '';
$create_time_all = '';
$update_time_all = '';
$check_time_all = '';
$checked        = !empty($checkall) ? ' checked="checked"' : '';
$num_columns    = $cfg['PropertiesNumColumns'] > 1
    ? ceil($num_tables / $cfg['PropertiesNumColumns']) + 1
    : 0;
$row_count      = 0;


$hidden_fields = array();
$odd_row       = true;
$sum_row_count_pre = '';

foreach ($tables as $keyname => $each_table) {
    // Get valid statistics whatever is the table type

    $table_is_view = false;
    $table_encoded = urlencode($each_table['TABLE_NAME']);
    // Sets parameters for links
    $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
    // do not list the previous table's size info for a view
    
    list($each_table, $formatted_size, $unit, $formatted_overhead,
        $overhead_unit, $overhead_size, $table_is_view)
            = PMA_getStuffForEnginetable($each_table, $db_is_information_schema,
                $is_show_stats
            );

    if (! PMA_Table::isMerge($db, $each_table['TABLE_NAME'])) {
        $sum_entries += $each_table['TABLE_ROWS'];
    }

    if (isset($each_table['Collation'])) {
        $collation = '<dfn title="'
            . PMA_getCollationDescr($each_table['Collation']) . '">'
            . $each_table['Collation'] . '</dfn>';
    } else {
        $collation = '---';
    }

    if ($is_show_stats) {
        if (isset($formatted_overhead)) {
            $overhead = '<a href="tbl_structure.php?'
                . $tbl_url_query . '#showusage"><span>' . $formatted_overhead
                . '</span> <span class="unit">' . $overhead_unit . '</span></a>' . "\n";
            unset($formatted_overhead);
            $overhead_check .=
                "markAllRows('row_tbl_" . ($i + 1) . "');";
        } else {
            $overhead = '-';
        }
    } // end if

    unset($showtable);

    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        list($create_time, $create_time_all) = PMA_getTimeForCreateUpdateCheck(
            $each_table, 'Create_time', $create_time_all
        );
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        // $showtable might already be set from ShowDbStructureCreation, see above
        list($update_time, $update_time_all) = PMA_getTimeForCreateUpdateCheck(
            $each_table, 'Update_time', $update_time_all
        );
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        // $showtable might already be set from ShowDbStructureCreation, see above
        list($check_time, $check_time_all) = PMA_getTimeForCreateUpdateCheck(
            $each_table, 'Check_time', $check_time_all
        );
    }

    list($alias, $truename) = PMA_getAliasAndTruename(
        $tooltip_aliasname, $each_table, $tooltip_truename
    );

    $i++;

    $row_count++;
    if ($table_is_view) {
        $hidden_fields[] = '<input type="hidden" name="views[]" value="'
            .  htmlspecialchars($each_table['TABLE_NAME']) . '" />';
    }

    /*
     * Always activate links for Browse, Search and Empty, even if
     * the icons are greyed, because
     * 1. for views, we don't know the number of rows at this point
     * 2. for tables, another source could have populated them since the
     *    page was generated
     *
     * I could have used the PHP ternary conditional operator but I find
     * the code easier to read without this operator.
     */
    list($browse_table, $search_table, $browse_table_label, $empty_table,
        $tracking_icon
    ) = PMA_getHtmlForActionLinks(
            $each_table, $table_is_view, $tbl_url_query,
            $titles, $truename, $db_is_information_schema, $url_query
        );
    
    if (! $db_is_information_schema) {
        list($drop_query, $drop_message) 
            = PMA_getTableDropQueryAndMessage($table_is_view, $each_table);
    }

    if ($num_columns > 0
        && $num_tables > $num_columns
        && ($row_count % $num_columns) == 0
    ) {
        $row_count = 1;
        $odd_row = true;

        $response->addHTML('</tr>'
            . '</tbody>'
            . '</table>');

        $response->addHTML(PMA_TableHeader(false, $server_slave_status));
    }

    list($do, $ignored) = PMA_getServerSlaveStatus(
        $server_slave_status, $truename
    );
    
    $response->addHTML(
        PMA_getHtmlForStructureTableRow(
            $i, $odd_row, $table_is_view, $each_table, $checked,
            $browse_table_label, $tracking_icon,$server_slave_status,
            $browse_table, $tbl_url_query, $search_table, $db_is_information_schema,
            $titles, $empty_table,$drop_query, $drop_message, $collation,
            $formatted_size, $unit, $overhead,
            (isset ($create_time) ? $create_time : ''),
            (isset ($update_time) ? $update_time : ''),
            (isset ($check_time) ? $check_time : ''),
            $is_show_stats, $ignored, $do, $colspan_for_structure
        )
    );
    
} // end foreach

// Show Summary
$response->addHTML('</tbody>');
$response->addHTML(
    PMA_getHtmlBodyForTableSummery(
        $num_tables, $server_slave_status, $db_is_information_schema, $sum_entries,
        $db_collation, $is_show_stats, $sum_size, $overhead_size, $create_time_all,
        $update_time_all, $check_time_all, $sum_row_count_pre
    )
);
$response->addHTML('</table>');
//check all
$response->addHTML(
    PMA_getHtmlForCheckAllTables($pmaThemeImage, $text_dir, 
        $overhead_check, $db_is_information_schema, $hidden_fields
    )
);
$response->addHTML('</form>'); //end of form

// display again the table list navigator
$response->addHTML(
    $common_functions->getListNavigator(
        $total_num_tables, $pos, $_url_params, 'db_structure.php',
        'frame_content', $GLOBALS['cfg']['MaxTableList']
    )
);

$response->addHTML('</div><hr />');

/**
 * Work on the database
 */
/* DATABASE WORK */
/* Printable view of a table */
$response->addHTML(PMA_getHtmlForPrintViewAndDataDictionaryLinks($url_query));

if (empty($db_is_information_schema)) {
    ob_start();
    include 'libraries/display_create_table.lib.php';
    $content = ob_get_contents();
    ob_end_clean();
    $response->addHTML($content);
} // end if (Create Table dialog)

?>
