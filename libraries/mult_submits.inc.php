<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Prepares the work and runs some other scripts if required
 */
if (! empty($submit_mult)
 && $submit_mult != $strWithChecked
 && (! empty($selected_db)
  || ! empty($selected_tbl)
  || ! empty($selected_fld)
  || ! empty($rows_to_delete))) {
    define('PMA_SUBMIT_MULT', 1);
    if (isset($selected_db) && !empty($selected_db)) {
        // coming from server database view - do something with selected databases
        $selected     = $selected_db;
        $what         = 'drop_db';
    } elseif (isset($selected_tbl) && !empty($selected_tbl)) {
        // coming from database structure view - do something with selected tables
        if ($submit_mult == $strPrintView) {
            require './tbl_printview.php';
        } else {
           $selected = $selected_tbl;
           switch ($submit_mult) {
               case 'drop_db':
                   $what = 'drop_db';
                   break;
               case $strDrop:
                   $what = 'drop_tbl';
                   break;
               case $strEmpty:
                   $what = 'empty_tbl';
                   break;
               case $strCheckTable:
                   unset($submit_mult);
                   $query_type = 'check_tbl';
                   $mult_btn   = $strYes;
                   break;
               case $strOptimizeTable:
                   unset($submit_mult);
                   $query_type = 'optimize_tbl';
                   $mult_btn   = $strYes;
                   break;
               case $strRepairTable:
                   unset($submit_mult);
                   $query_type = 'repair_tbl';
                   $mult_btn   = $strYes;
                   break;
               case $strAnalyzeTable:
                   unset($submit_mult);
                   $query_type = 'analyze_tbl';
                   $mult_btn   = $strYes;
                   break;
           } // end switch
        }
    } elseif (isset($selected_fld) && !empty($selected_fld)) {
        // coming from table structure view - do something with selected columns/fileds
        $selected     = $selected_fld;
        switch ($submit_mult) {
            case $strDrop:
                $what     = 'drop_fld';
                break;
            case $strPrimary:
                // Gets table primary key
                PMA_DBI_select_db($db);
                $result      = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($table) . ';');
                $primary     = '';
                while ($row = PMA_DBI_fetch_assoc($result)) {
                    // Backups the list of primary keys
                    if ($row['Key_name'] == 'PRIMARY') {
                        $primary .= $row['Column_name'] . ', ';
                    }
                } // end while
                PMA_DBI_free_result($result);
                if (empty($primary)) {
                    // no primary key, so we can safely create new
                    unset($submit_mult);
                    $query_type = 'primary_fld';
                    $mult_btn   = $strYes;
                } else {
                    // primary key exists, so lets as user
                    $what = 'primary_fld';
                }
                break;
            case $strIndex:
                unset($submit_mult);
                $query_type = 'index_fld';
                $mult_btn   = $strYes;
                break;
            case $strUnique:
                unset($submit_mult);
                $query_type = 'unique_fld';
                $mult_btn   = $strYes;
                break;
            case $strIdxFulltext:
                unset($submit_mult);
                $query_type = 'fulltext_fld';
                $mult_btn   = $strYes;
                break;
            case $strChange:
                require './tbl_alter.php';
                break;
            case $strBrowse:
                // this should already be handled by tbl_structure.php
        }
    } else {
        // coming from borwsing - do something with selected rows
        $what = 'row_delete';
        $selected = $rows_to_delete;
    }
} // end if


/**
 * Displays the confirmation form if required
 */
if (!empty($submit_mult) && !empty($what)) {
    $GLOBALS['js_include'][] = 'functions.js';
    unset($message);

    require_once './libraries/header.inc.php';
    if (strlen($table)) {
        require './libraries/tbl_common.php';
        $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
        require './libraries/tbl_info.inc.php';
        require_once './libraries/tbl_links.inc.php';
    } elseif (strlen($db)) {
        require './libraries/db_common.inc.php';
        require './libraries/db_info.inc.php';
    } else {
        require_once './libraries/server_common.inc.php';
        require_once './libraries/server_links.inc.php';
    }

    // Builds the query
    $full_query     = '';
    if ($what == 'drop_tbl') {
        $full_query_views = '';
    }
    $selected_cnt   = count($selected);
    $i = 0;
    foreach ($selected AS $idx => $sval) {
        switch ($what) {
            case 'row_delete':
                $full_query .= htmlspecialchars(urldecode($sval))
                    . ';<br />';
                break;
            case 'drop_db':
                $full_query .= 'DROP DATABASE '
                    . PMA_backquote(htmlspecialchars($sval))
                    . ';<br />';
                $reload = 1;
                break;

            case 'drop_tbl':
                $current = urldecode($sval);
                if (!empty($views) && in_array($current, $views)) {
                    $full_query_views .= (empty($full_query_views) ? 'DROP VIEW ' : ', ')
                        . PMA_backquote(htmlspecialchars($current));
                } else {
                    $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                        . PMA_backquote(htmlspecialchars($current));
                }
                break;

            case 'empty_tbl':
                $full_query .= 'TRUNCATE ';
                $full_query .= PMA_backquote(htmlspecialchars(urldecode($sval)))
                            . ';<br />';
                break;

            case 'primary_fld':
                if ($full_query == '') {
                    $full_query .= 'ALTER TABLE '
                        . PMA_backquote(htmlspecialchars($table))
                        . '<br />&nbsp;&nbsp;DROP PRIMARY KEY,'
                        . '<br />&nbsp;&nbsp; ADD PRIMARY KEY('
                        . '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                        . PMA_backquote(htmlspecialchars(urldecode($sval)))
                        . ',';
                } else {
                    $full_query .= '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                        . PMA_backquote(htmlspecialchars(urldecode($sval)))
                        . ',';
                }
                if ($i == $selected_cnt-1) {
                    $full_query = preg_replace('@,$@', ');<br />', $full_query);
                }
                break;

            case 'drop_fld':
                if ($full_query == '') {
                    $full_query .= 'ALTER TABLE '
                        . PMA_backquote(htmlspecialchars($table));
                }
                $full_query .= '<br />&nbsp;&nbsp;DROP '
                    . PMA_backquote(htmlspecialchars(urldecode($sval)))
                    . ',';
                if ($i == $selected_cnt - 1) {
                    $full_query = preg_replace('@,$@', ';<br />', $full_query);
                }
                break;
        } // end switch
        $i++;
    }
    if ($what == 'drop_tbl') {
        if (!empty($full_query)) {
            $full_query .= ';<br />' . "\n";
        }
        if (!empty($full_query_views)) {
            $full_query .= $full_query_views . ';<br />' . "\n";
        }
        unset($full_query_views);
    }

    // Displays the confirmation form
    $_url_params = array(
        'query_type' => $what,
        'reload' => (! empty($reload) ? 1 : 0),
    );
    if (strpos(' ' . $action, 'db_') == 1) {
        $_url_params['db']= $db;
    } elseif (strpos(' ' . $action, 'tbl_') == 1 || $what == 'row_delete') {
        $_url_params['db']= $db;
        $_url_params['table']= $table;
    }
    foreach ($selected as $idx => $sval) {
        $_url_params['selected'][] = $sval;
    }
    if ($what == 'drop_tbl' && !empty($views)) {
        foreach ($views as $current) {
            $_url_params['views'][] = $current;
       }
    }
    if ($what == 'row_delete') {
        $_url_params['original_sql_query'] = $original_sql_query;
        $_url_params['original_url_query'] = $original_url_query;
    }
    ?>
<form action="<?php echo $action; ?>" method="post">
    <?php
    echo PMA_generate_common_hidden_inputs($_url_params);
    ?>
<fieldset class="confirmation">
    <legend><?php echo ($what == 'drop_db' ? $strDropDatabaseStrongWarning . '&nbsp;' : '') . $strDoYouReally; ?>:</legend>
    <tt><?php echo $full_query; ?></tt>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="mult_btn" value="<?php echo $strYes; ?>" id="buttonYes" />
    <input type="submit" name="mult_btn" value="<?php echo $strNo; ?>" id="buttonNo" />
</fieldset>
    <?php
    require_once './libraries/footer.inc.php';
} // end if


/**
 * Executes the query - dropping rows, columns/fields, tables or dbs
 */
elseif ($mult_btn == $strYes) {

    if ($query_type == 'drop_db' || $query_type == 'drop_tbl' || $query_type == 'drop_fld') {
        require_once './libraries/relation_cleanup.lib.php';
    }

    $sql_query      = '';
    if ($query_type == 'drop_tbl') {
        $sql_query_views = '';
    }
    $selected_cnt   = count($selected);
    $run_parts      = FALSE; // whether to run query after each pass
    $use_sql        = FALSE; // whether to include sql.php at the end (to display results)

    if ($query_type == 'primary_fld') {
        // Gets table primary key
        PMA_DBI_select_db($db);
        $result      = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($table) . ';');
        $primary     = '';
        while ($row = PMA_DBI_fetch_assoc($result)) {
            // Backups the list of primary keys
            if ($row['Key_name'] == 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
            }
        } // end while
        PMA_DBI_free_result($result);
    }

    $rebuild_database_list = false;

    for ($i = 0; $i < $selected_cnt; $i++) {
        switch ($query_type) {
            case 'row_delete':
                $a_query = urldecode($selected[$i]);
                $run_parts = TRUE;
                break;

            case 'drop_db':
                PMA_relationsCleanupDatabase($selected[$i]);
                $a_query   = 'DROP DATABASE '
                           . PMA_backquote($selected[$i]);
                $reload    = 1;
                $run_parts = TRUE;
                $rebuild_database_list = true;
                break;

            case 'drop_tbl':
                PMA_relationsCleanupTable($db, $selected[$i]);
                $current = urldecode($selected[$i]);
                if (!empty($views) && in_array($current, $views)) {
                    $sql_query_views .= (empty($sql_query_views) ? 'DROP VIEW ' : ', ')
                              . PMA_backquote($current);
                } else {
                    $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ')
                               . PMA_backquote($current);
                }
                $reload    = 1;
                break;

            case 'check_tbl':
                $sql_query .= (empty($sql_query) ? 'CHECK TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]));
                $use_sql    = TRUE;
                break;

            case 'optimize_tbl':
                $sql_query .= (empty($sql_query) ? 'OPTIMIZE TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]));
                $use_sql    = TRUE;
                break;

            case 'analyze_tbl':
                $sql_query .= (empty($sql_query) ? 'ANALYZE TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]));
                $use_sql    = TRUE;
                break;

            case 'repair_tbl':
                $sql_query .= (empty($sql_query) ? 'REPAIR TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]));
                $use_sql    = TRUE;
                break;

            case 'empty_tbl':
                $a_query = 'TRUNCATE ';
                $a_query .= PMA_backquote(htmlspecialchars(urldecode($selected[$i])));
                $run_parts = TRUE;
                break;

            case 'drop_fld':
                PMA_relationsCleanupColumn($db, $table, $selected[$i]);
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) : ',')
                           . ' DROP ' . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ';' : '');
                break;

            case 'primary_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . (empty($primary) ? '' : ' DROP PRIMARY KEY,') . ' ADD PRIMARY KEY( ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

            case 'index_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX( ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

            case 'unique_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE( ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

            case 'fulltext_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT( ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;
        } // end switch

        // All "DROP TABLE", "DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
        // statements will be run at once below
        if ($run_parts) {
            $sql_query .= $a_query . ';' . "\n";
            if ($query_type != 'drop_db') {
                PMA_DBI_select_db($db);
            }
            $result = PMA_DBI_query($a_query);
        } // end if
    } // end for

    if ($query_type == 'drop_tbl') {
        if (!empty($sql_query)) {
        $sql_query .= ';';
    } elseif (!empty($sql_query_views)) {
        $sql_query = $sql_query_views . ';';
            unset($sql_query_views);
        }
    }

    if ($use_sql) {
        require './sql.php';
    } elseif (!$run_parts) {
        PMA_DBI_select_db($db);
        $result = PMA_DBI_try_query($sql_query);
        if ($result && !empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            $result = PMA_DBI_try_query($sql_query_views);
            unset($sql_query_views);
        }

        if (! $result) {
            $message = PMA_Message::error(PMA_DBI_getError());
        }
    }
    if ($rebuild_database_list) {
        // avoid a problem with the database list navigator
        // when dropping a db from server_databases
        $GLOBALS['PMA_List_Database']->build();
    }
}
?>
