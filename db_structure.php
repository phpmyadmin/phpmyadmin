<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database structure manipulation
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

// Add/Remove favorite tables using Ajax request.
if ($GLOBALS['is_ajax_request'] && ! empty($_REQUEST['favorite_table'])) {
    $fav_instance = PMA_RecentFavoriteTable::getInstance('favorite');
    $favorite_tables = json_decode($_REQUEST['favorite_tables'], true);
    // Required to keep each user's preferences seperate.
    $user = sha1($GLOBALS['cfg']['Server']['user']);

    // Request for Synchronization of favorite tables.
    if (isset($_REQUEST['sync_favorite_tables'])) {

        $fav_instance_tables = $fav_instance->getTables();

        if (empty($fav_instance_tables)
            && isset($favorite_tables[$user])
        ) {
            foreach ($favorite_tables[$user] as $key => $value) {
                $fav_instance->add($value['db'], $value['table']);
            }
        }
        $favorite_tables[$user] = $fav_instance->getTables();

        $ajax_response = PMA_Response::getInstance();
        $ajax_response->addJSON(
            'favorite_tables',
            json_encode($favorite_tables)
        );
        $ajax_response->addJSON(
            'list',
            $fav_instance->getHtmlList()
        );
        $server_id = $GLOBALS['server'];
        // Set flag when localStorage and pmadb(if present) are in sync.
        $_SESSION['tmpval']['favorites_synced'][$server_id] = true;
        exit;
    }
    global $db;
    $changes = true;
    $msg = '';
    $titles = PMA_Util::buildActionTitles();
    $favorite_table = $_REQUEST['favorite_table'];
    $already_favorite = PMA_checkFavoriteTable($db, $favorite_table);

    if (isset($_REQUEST['remove_favorite'])) {
        if ($already_favorite) {
            // If already in favorite list, remove it.
            $fav_instance->remove($db, $favorite_table);
        }
    } elseif (isset($_REQUEST['add_favorite'])) {
        if (!$already_favorite) {
            if (count($fav_instance->getTables()) == $GLOBALS['cfg']['NumFavoriteTables']) {
                $changes = false;
                $msg = '<div class="error"><img src="themes/dot.gif" '
                    . 'title="" alt="" class="icon ic_s_error" />'
                    . __("Favorite List is full!")
                    . '</div>';
            } else {
                // Otherwise add to favorite list.
                $fav_instance->add($db, $favorite_table);
            }
        }
    }

    $favorite_tables[$user] = $fav_instance->getTables();
    $ajax_response = PMA_Response::getInstance();
    $ajax_response->addJSON(
        'changes',
        $changes
    );
    if ($changes) {
        $ajax_response->addJSON(
            'user',
            $user
        );
        $ajax_response->addJSON(
            'favorite_tables',
            json_encode($favorite_tables)
        );
        $ajax_response->addJSON(
            'list',
            $fav_instance->getHtmlList()
        );
        $ajax_response->addJSON(
            'anchor',
            PMA_getHtmlForFavoriteAnchor(
                $db, array('TABLE_NAME' => $favorite_table), $titles
            )
        );
    } else {
        $ajax_response->addJSON(
            'message',
            $msg
        );
    }
    exit;
}

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('db_structure.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');

// Drops/deletes/etc. multiple tables if required
if ((!empty($_POST['submit_mult']) && isset($_POST['selected_tbl']))
    || isset($_POST['mult_btn'])
) {
    $action = 'db_structure.php';
    $err_url = 'db_structure.php?' . PMA_URL_getCommon($db);

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

require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_structure.php';

// Gets the database structure
$sub_part = '_structure';
require 'libraries/db_info.inc.php';

if (!PMA_DRIZZLE) {
    include_once 'libraries/replication.inc.php';
} else {
    $server_slave_status = false;
}

require_once 'libraries/bookmark.lib.php';

require_once 'libraries/mysql_charsets.inc.php';
$db_collation = PMA_getDbCollation($db);

$titles = PMA_Util::buildActionTitles();

// 1. No tables

if ($num_tables == 0) {
    $response->addHTML(
        '<p>' . __('No tables found in database.') . '</p>' . "\n"
    );
    if (empty($db_is_system_schema)) {
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

$response->addHTML(
    PMA_Util::getListNavigator(
        $total_num_tables, $pos, $_url_params, 'db_structure.php',
        'frame_content', $GLOBALS['cfg']['MaxTableList']
    )
);

// tables form
$response->addHTML(
    '<form method="post" action="db_structure.php" '
    . 'name="tablesForm" id="tablesForm">'
);

$response->addHTML(PMA_URL_getHiddenInputs($db));

$response->addHTML(
    PMA_tableHeader($db_is_system_schema, $server_slave_status)
);

$i = $sum_entries = 0;
$overhead_check = '';
$create_time_all = '';
$update_time_all = '';
$check_time_all = '';
$num_columns    = $cfg['PropertiesNumColumns'] > 1
    ? ceil($num_tables / $cfg['PropertiesNumColumns']) + 1
    : 0;
$row_count      = 0;
$sum_size       = (double) 0;
$overhead_size  = (double) 0;

$hidden_fields = array();
$odd_row       = true;
$sum_row_count_pre = '';
// Instance of PMA_RecentFavoriteTable class.
$fav_instance = PMA_RecentFavoriteTable::getInstance('favorite');
foreach ($tables as $keyname => $current_table) {
    // Get valid statistics whatever is the table type

    $drop_query = '';
    $drop_message = '';
    $already_favorite = false;
    $overhead = '';

    $table_is_view = false;
    $table_encoded = urlencode($current_table['TABLE_NAME']);
    // Sets parameters for links
    $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
    // do not list the previous table's size info for a view

    list($current_table, $formatted_size, $unit, $formatted_overhead,
        $overhead_unit, $overhead_size, $table_is_view, $sum_size)
            = PMA_getStuffForEngineTypeTable(
                $current_table, $db_is_system_schema,
                $is_show_stats, $table_is_view, $sum_size, $overhead_size
            );

    if (! PMA_Table::isMerge($db, $current_table['TABLE_NAME'])) {
        $sum_entries += $current_table['TABLE_ROWS'];
    }

    if (isset($current_table['Collation'])) {
        $collation = '<dfn title="'
            . PMA_getCollationDescr($current_table['Collation']) . '">'
            . $current_table['Collation'] . '</dfn>';
    } else {
        $collation = '---';
    }

    if ($is_show_stats) {
        if ($formatted_overhead != '') {
            $overhead = '<a href="tbl_structure.php?'
                . $tbl_url_query . '#showusage">'
                . '<span>' . $formatted_overhead . '</span>'
                . '<span class="unit">' . $overhead_unit . '</span>'
                . '</a>' . "\n";
            $overhead_check .=
                "markAllRows('row_tbl_" . ($i + 1) . "');";
        } else {
            $overhead = '-';
        }
    } // end if

    unset($showtable);

    if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
        list($create_time, $create_time_all) = PMA_getTimeForCreateUpdateCheck(
            $current_table, 'Create_time', $create_time_all
        );
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
        // $showtable might already be set from ShowDbStructureCreation, see above
        list($update_time, $update_time_all) = PMA_getTimeForCreateUpdateCheck(
            $current_table, 'Update_time', $update_time_all
        );
    }

    if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
        // $showtable might already be set from ShowDbStructureCreation, see above
        list($check_time, $check_time_all) = PMA_getTimeForCreateUpdateCheck(
            $current_table, 'Check_time', $check_time_all
        );
    }

    list($alias, $truename) = PMA_getAliasAndTrueName(
        $tooltip_aliasname, $current_table, $tooltip_truename
    );

    $i++;

    $row_count++;
    if ($table_is_view) {
        $hidden_fields[] = '<input type="hidden" name="views[]" value="'
            .  htmlspecialchars($current_table['TABLE_NAME']) . '" />';
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
        $tracking_icon) = PMA_getHtmlForActionLinks(
            $current_table, $table_is_view, $tbl_url_query,
            $titles, $truename, $db_is_system_schema, $url_query
        );

    if (! $db_is_system_schema) {
        list($drop_query, $drop_message)
            = PMA_getTableDropQueryAndMessage($table_is_view, $current_table);
    }

    if ($num_columns > 0
        && $num_tables > $num_columns
        && ($row_count % $num_columns) == 0
    ) {
        $row_count = 1;
        $odd_row = true;

        $response->addHTML(
            '</tr></tbody></table>'
        );

        $response->addHTML(PMA_tableHeader(false, $server_slave_status));
    }

    list($do, $ignored) = PMA_getServerSlaveStatus(
        $server_slave_status, $truename
    );
    // Handle favorite table list. ----START----
    $already_favorite = PMA_checkFavoriteTable($db, $current_table['TABLE_NAME']);

    if (isset($_REQUEST['remove_favorite'])) {
        if ($already_favorite) {
            // If already in favorite list, remove it.
            $favorite_table = $_REQUEST['favorite_table'];
            $fav_instance->remove($db, $favorite_table);
        }
    }

    if (isset($_REQUEST['add_favorite'])) {
        if (!$already_favorite) {
            // Otherwise add to favorite list.
            $favorite_table = $_REQUEST['favorite_table'];
            $fav_instance->add($db, $favorite_table);
        }
    } // Handle favorite table list. ----ENDS----

    list($html_output, $odd_row) = PMA_getHtmlForStructureTableRow(
        $i, $odd_row, $table_is_view, $current_table,
        $browse_table_label, $tracking_icon, $server_slave_status,
        $browse_table, $tbl_url_query, $search_table, $db_is_system_schema,
        $titles, $empty_table, $drop_query, $drop_message, $collation,
        $formatted_size, $unit, $overhead,
        (isset ($create_time) ? $create_time : ''),
        (isset ($update_time) ? $update_time : ''),
        (isset ($check_time) ? $check_time : ''),
        $is_show_stats, $ignored, $do, $colspan_for_structure
    );
    $response->addHTML($html_output);

} // end foreach

// Show Summary
$response->addHTML('</tbody>');
$response->addHTML(
    PMA_getHtmlBodyForTableSummary(
        $num_tables, $server_slave_status, $db_is_system_schema, $sum_entries,
        $db_collation, $is_show_stats, $sum_size, $overhead_size, $create_time_all,
        $update_time_all, $check_time_all, $sum_row_count_pre
    )
);
$response->addHTML('</table>');
//check all
$response->addHTML(
    PMA_getHtmlForCheckAllTables(
        $pmaThemeImage, $text_dir, $overhead_check,
        $db_is_system_schema, $hidden_fields
    )
);
$response->addHTML('</form>'); //end of form

// display again the table list navigator
$response->addHTML(
    PMA_Util::getListNavigator(
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
$response->addHTML(
    PMA_getHtmlForTablePrintViewLink($url_query)
    . PMA_getHtmlForDataDictionaryLink($url_query)
);

if (empty($db_is_system_schema)) {
    ob_start();
    include 'libraries/display_create_table.lib.php';
    $content = ob_get_contents();
    ob_end_clean();
    $response->addHTML($content);
} // end if (Create Table dialog)

?>
