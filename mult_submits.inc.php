<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Prepares the work and runs some other scripts if required
 */
if (!empty($submit_mult)
    && ($submit_mult != $strWithChecked)
    && (  !empty($selected_db)
       || !empty($selected_tbl)
       || !empty($selected_fld)
       || !empty($rows_to_delete)
         )) {

    if (!empty($selected_db)) {
        $selected     = $selected_db;
        $what         = 'drop_db';
    } else if (!empty($selected_tbl)) {
        if ($submit_mult == $strPrintView) {
            require('./tbl_printview.php');
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
    } else if (!empty($selected_fld)) {
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
                require('./tbl_alter.php');
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
    if (!empty($table)) {
        require('./tbl_properties_common.php');
        $url_query .= '&amp;goto=tbl_properties.php&amp;back=tbl_properties.php';
        require('./tbl_properties_table_info.php');
    }
    elseif (!empty($db)) {
        require('./db_details_common.php');
        require('./db_details_db_info.php');
    }
    // Builds the query
    $full_query     = '';
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
                break;

            case 'drop_tbl':
                $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                            . PMA_backquote(htmlspecialchars(urldecode($sval)))
                            . (($i == $selected_cnt - 1) ? ';<br />' : '');
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

    // Displays the form
?>
<!-- Do it really ? -->
<table border="0" cellpadding="3" cellspacing="0">
    <tr>
        <th class="tblHeadError" align="left">
            <?php
    echo ($GLOBALS['cfg']['ErrorIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_really.png" border="0" hspace="2" width="11" height="11" valign="middle" />' : '');
    echo $strDoYouReally . ':&nbsp;' . "\n";
            ?>
        </th>
    </tr>
    <tr>
        <td bgcolor="<?php echo $GLOBALS['cfg']['BgcolorOne']; ?>">
           <?php
    echo '<tt>' . $full_query . '</tt>&nbsp;?<br/>' . "\n";
           ?>
        </td>
    </tr>
    <tr>
       <td align="right" nowrap="nowrap">
<form action="<?php echo $action; ?>" method="post">
    <?php
    echo "\n";
    if (strpos(' ' . $action, 'db_details') == 1) {
        echo PMA_generate_common_hidden_inputs($db);
    } else if (strpos(' ' . $action, 'tbl_properties') == 1
              || $what == 'row_delete') {
        echo PMA_generate_common_hidden_inputs($db,$table);
    } else  {
        echo PMA_generate_common_hidden_inputs();
    }
    foreach ($selected AS $idx => $sval) {
        echo '    <input type="hidden" name="selected[]" value="' . htmlspecialchars($sval) . '" />' . "\n";
    }
    ?>
    <input type="hidden" name="query_type" value="<?php echo $what; ?>" />
    <?php
    if ($what == 'row_delete') {
        echo '<input type="hidden" name="original_sql_query" value="' . htmlspecialchars($original_sql_query) . '" />' . "\n";
        echo '<input type="hidden" name="original_pos" value="' . $original_pos . '" />' . "\n";
        echo '<input type="hidden" name="original_url_query" value="' . htmlspecialchars($original_url_query) . '" />' . "\n";
    }
    ?>
    <input type="submit" name="mult_btn" value="<?php echo $strYes; ?>" id="buttonYes" />
    <input type="submit" name="mult_btn" value="<?php echo $strNo; ?>" id="buttonNo" />
</form>
        </td>
    </tr>
</table>
    <?php
    echo"\n";

    require_once('./footer.inc.php');
} // end if


/**
 * Executes the query
 */
else if ($mult_btn == $strYes) {

    if ($query_type == 'drop_db' || $query_type == 'drop_tbl' || $query_type == 'drop_fld') {
        require_once('./libraries/relation_cleanup.lib.php');
    }

    $sql_query      = '';
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
                break;

            case 'drop_tbl':
                PMA_relationsCleanupTable($db, $selected[$i]);
                $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ';' : '');
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
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ( empty($primary) ? '' : ' DROP PRIMARY KEY,') . ' ADD PRIMARY KEY( ' : ', ')
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

        // All "DROP TABLE","DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
        // statements will be run at once below
        if ($run_parts) { 
            $sql_query .= $a_query . ';' . "\n";
            if ($query_type != 'drop_db') {
                PMA_DBI_select_db($db);
            }
            $result = @PMA_DBI_query($a_query) or PMA_mysqlDie('', $a_query, FALSE, $err_url);
        } // end if
    } // end for

    if ($use_sql) {
        require('./sql.php');
    } elseif (!$run_parts) {
        PMA_DBI_select_db($db);
        $result = PMA_DBI_query($sql_query);
    }

}

?>
