<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Prepares the work and runs some other scripts if required
 */
if (!empty($submit_mult)
    && (!empty($selected_db) || !empty($selected_tbl) || !empty($selected_fld))) {

    if (!empty($selected_db)) {
        $selected     = $selected_db;
        $what         = 'drop_db';
    } else if (!empty($selected_tbl)) {
        if ($submit_mult == $strPrintView) {
            include('./tbl_printview.php3');
            exit();
        } else {
           $selected = $selected_tbl;
           switch ($submit_mult) {
               case $strDrop:
                   $what = 'drop_tbl';
                   break;
               case $strEmpty:
                   $what = 'empty_tbl';
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
           } // end switch
        }
    } else {
        $selected     = $selected_fld;
        if ($submit_mult == $strDrop) {
            $what     = 'drop_fld';
        } else {
            include('./tbl_alter.php3');
            exit();
        }
    }
} // end if


/**
 * Displays the confirmation form if required
 */
if (!empty($submit_mult) && !empty($what)) {
    // Builds the query
    $full_query     = '';
    $selected_cnt   = count($selected);
    for ($i = 0; $i < $selected_cnt; $i++) {
        switch ($what) {
            case 'drop_db':
                $full_query .= 'DROP DATABASE '
                            . PMA_backquote(htmlspecialchars(urldecode($selected[$i])))
                            . ';<br />';
                break;

            case 'drop_tbl':
                $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                            . PMA_backquote(htmlspecialchars(urldecode($selected[$i])))
                            . (($i == $selected_cnt - 1) ? ';<br />' : '');
                break;

            case 'empty_tbl':
                if (PMA_MYSQL_INT_VERSION >= 40000) {
                    $full_query .= 'TRUNCATE ';
                } else {
                    $full_query .= 'DELETE FROM ';
                }
                $full_query .= PMA_backquote(htmlspecialchars(urldecode($selected[$i])))
                            . ';<br />';
                break;

            case 'drop_fld':
                if ($full_query == '') {
                    $full_query .= 'ALTER TABLE '
                                . PMA_backquote(htmlspecialchars($table))
                                . '<br />&nbsp;&nbsp;DROP '
                                . PMA_backquote(htmlspecialchars(urldecode($selected[$i])))
                                . ',';
                } else {
                    $full_query .= '<br />&nbsp;&nbsp;DROP '
                                . PMA_backquote(htmlspecialchars(urldecode($selected[$i])))
                                . ',';
                }
                if ($i == $selected_cnt-1) {
                    $full_query = ereg_replace(',$', ';<br />', $full_query);
                }
                break;
        } // end switch
    }

    // Displays the form
    echo $strDoYouReally . '&nbsp;:<br />' . "\n";
    echo '<tt>' . $full_query . '</tt>&nbsp;?<br/>' . "\n";
    ?>
<form action="<?php echo $action; ?>" method="post">
    <?php
    echo "\n";
    if (strpos(' ' . $action, 'db_details') == 1) {
        echo PMA_generate_common_hidden_inputs($db);
    } else if (strpos(' ' . $action, 'tbl_properties') == 1) {
        echo PMA_generate_common_hidden_inputs($db,$table);
    }
    for ($i = 0; $i < $selected_cnt; $i++) {
        echo '    <input type="hidden" name="selected[]" value="' . htmlspecialchars($selected[$i]) . '" />' . "\n";
    }
    ?>
    <input type="hidden" name="query_type" value="<?php echo $what; ?>" />
    <input type="submit" name="mult_btn" value="<?php echo $strYes; ?>" />
    <input type="submit" name="mult_btn" value="<?php echo $strNo; ?>" />
</form>
    <?php
    echo"\n";

    include('./footer.inc.php3');
    exit();
} // end if


/**
 * Executes the query
 */
else if ($mult_btn == $strYes) {

    $sql_query      = '';
    $selected_cnt   = count($selected);
    for ($i = 0; $i < $selected_cnt; $i++) {
        switch ($query_type) {
            case 'drop_db':
                $a_query   = 'DROP DATABASE '
                           . PMA_backquote(urldecode($selected[$i]));
                $reload    = 1;
                break;

            case 'drop_tbl':
                $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ';' : '');
                $reload    = 1;
                break;

            case 'optimize_tbl':
                $sql_query .= (empty($sql_query) ? 'OPTIMIZE TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ';' : '');
                break;

            case 'repair_tbl':
                $sql_query .= (empty($sql_query) ? 'REPAIR TABLE ' : ', ')
                           . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ';' : '');
                break;

            case 'empty_tbl':
                $a_query   = 'DELETE FROM '
                           . PMA_backquote(urldecode($selected[$i]));
                break;

            case 'drop_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) : ',')
                           . ' DROP ' . PMA_backquote(urldecode($selected[$i]))
                           . (($i == $selected_cnt-1) ? ';' : '');
                break;
        } // end switch

        // All "DROP TABLE","DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
        // statements will be run at once below
        if ($query_type != 'drop_tbl'
            && $query_type != 'drop_fld'
            && $query_type != 'repair_tbl'
            && $query_type != 'optimize_tbl') {
            $sql_query .= $a_query . ';' . "\n";

            if ($query_type != 'drop_db') {
                PMA_mysql_select_db($db);
            }
            $result = @PMA_mysql_query($a_query) or PMA_mysqlDie('', $a_query, FALSE, $err_url);
        } // end if
    } // end for

    if ($query_type == 'drop_tbl'
        || $query_type == 'drop_fld'
        || $query_type == 'repair_tbl'
        || $query_type == 'optimize_tbl') {
        PMA_mysql_select_db($db);
        $result = @PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', FALSE, $err_url);
    }

    PMA_showMessage($strSuccess);
}

?>
