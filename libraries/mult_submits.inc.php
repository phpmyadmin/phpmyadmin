<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
        $selected     = $selected_db;
        $what         = 'drop_db';
    } elseif (isset($selected_tbl) && !empty($selected_tbl)) {
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
                $sql_query = '';
                foreach ($selected AS $idx => $sval) {
                    if ($sql_query == '') {
                        $sql_query .= 'SELECT ' . PMA_backquote(urldecode($sval));
                    } else {
                        $sql_query .=  ', ' . PMA_backquote(urldecode($sval));
                    }
                }
                $sql_query .= ' FROM ' . PMA_backquote(htmlspecialchars($table));
                require './sql.php';
                break;
        }
    } else {
        $what = 'row_delete';
        $selected = $rows_to_delete;
    }
} // end if


/**
 * Displays the confirmation form if required
 */
if (!empty($submit_mult) && !empty($what)) {
    $js_to_run = 'functions.js';
    unset($message);
    if (strlen($table)) {
        require './libraries/tbl_common.php';
        $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
        require './libraries/tbl_info.inc.php';
    } elseif (strlen($db)) {
        require './libraries/db_common.inc.php';
        require './libraries/db_info.inc.php';
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
                    . PMA_backquote(htmlspecialchars(urldecode($sval)))
                    . ';<br />';
                $reload = 1;
                break;

            case 'drop_tbl':
                $current = urldecode($sval);
                // here we must compare with the value before urldecode()
                // because $views has been treated with htmlspecialchars()
                if (!empty($views) && in_array($sval, $views)) {
                    $full_query_views .= (empty($full_query_views) ? 'DROP VIEW ' : ', ')
                        . PMA_backquote(htmlspecialchars($current));
                } else {
                    $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                        . PMA_backquote(htmlspecialchars($current));
                }
                break;

            case 'empty_tbl':
                if (PMA_MYSQL_INT_VERSION >= 40000) {
                    $full_query .= 'TRUNCATE ';
                } else {
                    $full_query .= 'DELETE FROM ';
                }
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
                        . PMA_backquote(htmlspecialchars($table))
                        . '<br />&nbsp;&nbsp;DROP '
                        . PMA_backquote(htmlspecialchars(urldecode($sval)))
                        . ',';
                } else {
                    $full_query .= '<br />&nbsp;&nbsp;DROP '
                        . PMA_backquote(htmlspecialchars(urldecode($sval)))
                        . ',';
                }
                if ($i == $selected_cnt-1) {
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

    // Displays the form
    ?>
<!-- Do it really ? -->
<form action="<?php echo $action; ?>" method="post">
<input type="hidden" name="query_type" value="<?php echo $what; ?>" />
    <?php
    if (strpos(' ' . $action, 'db_') == 1) {
        echo PMA_generate_common_hidden_inputs($db);
    } elseif (strpos(' ' . $action, 'tbl_') == 1
              || $what == 'row_delete') {
        echo PMA_generate_common_hidden_inputs($db, $table);
    } else  {
        echo PMA_generate_common_hidden_inputs();
    }
?>
<input type="hidden" name="reload" value="<?php echo isset($reload) ? PMA_sanitize($reload) : 0; ?>" />
<?php
    foreach ($selected as $idx => $sval) {
        echo '<input type="hidden" name="selected[]" value="' . htmlspecialchars($sval) . '" />' . "\n";
    }
    if ($what == 'drop_tbl' && !empty($views)) {
        foreach ($views as $current) {
           echo '<input type="hidden" name="views[]" value="' . htmlspecialchars($current) . '" />' . "\n";
       }
    }
    if ($what == 'row_delete') {
        echo '<input type="hidden" name="original_sql_query" value="' . htmlspecialchars($original_sql_query) . '" />' . "\n";
        echo '<input type="hidden" name="original_url_query" value="' . htmlspecialchars($original_url_query) . '" />' . "\n";
    }
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
 * Executes the query
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
                           . PMA_backquote(urldecode($selected[$i]));
                $reload    = 1;
                $run_parts = TRUE;
                $rebuild_database_list = true;
                break;

            case 'drop_tbl':
                PMA_relationsCleanupTable($db, $selected[$i]);
                $current = urldecode($selected[$i]);
                // here we must compare with the value before urldecode()
                // because $views has been treated with htmlspecialchars()
                if (!empty($views) && in_array($selected[$i], $views)) {
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
                if (PMA_MYSQL_INT_VERSION >= 40000) {
                    $a_query = 'TRUNCATE ';
                } else {
                    $a_query = 'DELETE FROM ';
                }
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
            $result = @PMA_DBI_query($a_query) or PMA_mysqlDie('', $a_query, FALSE, $err_url);
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
        $result = PMA_DBI_query($sql_query);
        if (!empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            PMA_DBI_query($sql_query_views);
            unset($sql_query_views);
        }
    }
    if ($rebuild_database_list) {
        // avoid a problem with the database list navigator
        // when dropping a db from server_databases
        $GLOBALS['PMA_List_Database']->build();
    }
}
?>
