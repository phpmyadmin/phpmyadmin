<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');


/** 
 * Check rights in case of DROP DATABASE
 *
 * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
 * but since a malicious user may pass this variable by url/form, we don't take
 * into account this case.
 */
if (!defined('PMA_CHK_DROP')
    && !$cfgAllowUserDropDatabase
    && eregi('DROP[[:space:]]+(IF EXISTS[[:space:]]+)?DATABASE ', $sql_query)) {
    // Checks if the user is a Superuser
    // TODO: set a global variable with this information
    // loic1: optimized query
    $result = @mysql_query('USE mysql');
    if (mysql_error()) {
        include('./header.inc.php3');
        mysql_die($strNoDropDatabases);
    } // end if
} // end if


/**
 * Bookmark add
 */
if (isset($store_bkm)) {
    if (get_magic_quotes_gpc()) {
        $fields['label'] = stripslashes($fields['label']);
    }
    add_bookmarks($fields, $cfgBookmark);
    header('Location: ' . $cfgPmaAbsoluteUri . $goto);
}


/**
 * Gets the true sql query
 */
// $sql_query has been urlencoded in the confirmation form for drop/delete
// queries or in the navigation bar for browsing among records
if (isset($btnDrop) || isset($navig)) {
    $sql_query = urldecode($sql_query);
}


/**
 * Sets or modifies the $goto variable if required
 */
if (empty($goto)) {
    $goto = (empty($table)) ? 'db_details.php3' : 'tbl_properties.php3';
} else if ($goto == 'sql.php3') {
    $goto = 'sql.php3'
          . '?lang=' . $lang
          . '&server=' . $server
          . '&db=' . urlencode($db)
          . '&table=' . urlencode($table)
          . '&pos=' . $pos
          . '&sql_query=' . urlencode($sql_query);
}


/**
 * Go back to further page if table should not be dropped
 */
if (isset($btnDrop) && $btnDrop == $strNo) {
    if (!empty($back)) {
        $goto = $back;
    }
    if (file_exists('./' . $goto)) {
        if ($goto == 'db_details.php3' && !empty($table)) {
            unset($table);
        }
        include('./' . ereg_replace('\.\.*', '.', $goto));
    } else {
        header('Location: ' . $cfgPmaAbsoluteUri . $goto);
    }
    exit();
} // end if


/**
 * Displays the confirm page if required
 *
 * This part of the script is bypassed if $is_js_confirmed = 1 (already checked
 * with js) because possible security issue is not so important here: at most,
 * the confirm message isn't displayed.
 */
if (!$cfgConfirm
    || (isset($is_js_confirmed) && $is_js_confirmed)
    || isset($btnDrop)) {
    $do_confirm = FALSE;
} else {
    $do_confirm = (eregi('DROP[[:space:]]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)|ALTER TABLE +((`[^`]+`)|([A-Za-z0-9_$]+)) +DROP|DELETE FROM', $sql_query));
}

if ($do_confirm) {
    if (get_magic_quotes_gpc()) {
        $stripped_sql_query = stripslashes($sql_query);
    } else {
        $stripped_sql_query = $sql_query;
    }
    include('./header.inc.php3');
    echo $strDoYouReally . '&nbsp;:<br />' . "\n";
    echo '<tt>' . htmlspecialchars($stripped_sql_query) . '</tt>&nbsp;?<br/>' . "\n";
    ?>
<form action="sql.php3" method="post">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo isset($table) ? $table : ''; ?>" />
    <input type="hidden" name="sql_query" value="<?php echo urlencode($sql_query); ?>" />
    <input type="hidden" name="zero_rows" value="<?php echo isset($zero_rows) ? $zero_rows : ''; ?>" />
    <input type="hidden" name="goto" value="<?php echo isset($goto) ? $goto : ''; ?>" />
    <input type="hidden" name="back" value="<?php echo isset($back) ? $back : ''; ?>" />
    <input type="hidden" name="reload" value="<?php echo isset($reload) ? $reload : ''; ?>" />
    <input type="hidden" name="show_query" value="<?php echo isset($show_query) ? $show_query : ''; ?>" />
    <input type="submit" name="btnDrop" value="<?php echo $strYes; ?>" />
    <input type="submit" name="btnDrop" value="<?php echo $strNo; ?>" />
</form>
    <?php
    echo "\n";
} // end if


/**
 * Executes the query and displays results
 */
else {
    if (!isset($sql_query)) {
        $sql_query = '';
    } else if (get_magic_quotes_gpc()) {
        $sql_query = stripslashes($sql_query);
    }

    // Defines some variables
    // loic1: A table have to be created -> left frame should be reloaded
    if (!empty($reload) && eregi('^CREATE TABLE (.*)', $sql_query)) {
        $reload           = 'true';
    }
    if (isset($sessionMaxRows)) {
        $cfgMaxRows       = $sessionMaxRows;
    }

    $is_explain = $is_select = $is_count = $is_delete = $is_insert = $is_affected = $is_show = $is_maint = FALSE;
    if (eregi('^EXPLAIN[[:space:]]+', $sql_query)) {
        $is_explain  = TRUE;
    } else if (eregi('^SELECT[[:space:]]+', $sql_query)) {
        $is_select   = TRUE;
        $is_count    = (eregi('^SELECT COUNT\((.*\.+)?.*\)', $sql_query));
    } else if (eregi('^DELETE[[:space:]]+', $sql_query)) {
        $is_delete   = TRUE;
        $is_affected = TRUE;
    } else if (eregi('^(INSERT|LOAD DATA|REPLACE)[[:space:]]+', $sql_query)) {
        $is_insert   = TRUE;
        $is_affected = TRUE;
    } else if (eregi('^UPDATE[[:space:]]+', $sql_query)) {
        $is_affected = TRUE;
    } else if (eregi('^SHOW[[:space:]]+', $sql_query)) {
        $is_show     = TRUE;
    } else if (eregi('^(CHECK|ANALYZE|REPAIR|OPTIMIZE)[[:space:]]+TABLE[[:space:]]+', $sql_query)) {
        $is_maint    = TRUE;
    }

    // Do append a "LIMIT" clause?
    if (isset($pos)
        && ($is_select && !$is_count)
        && !eregi(' LIMIT[ 0-9,]+$', $sql_query)) {
        $sql_limit_to_append = " LIMIT $pos, $cfgMaxRows";
        if (eregi('(.*)( PROCEDURE (.*)| FOR UPDATE| LOCK IN SHARE MODE)$', $sql_query, $regs)) {
            $full_sql_query  = $regs[1] . $sql_limit_to_append . $regs[2];
        } else {
            $full_sql_query  = $sql_query . $sql_limit_to_append;
        }
    } else {
        $full_sql_query      = $sql_query;
    } // end if...else

    mysql_select_db($db);

    // If the query is a DELETE query with no WHERE clause, get the number of
    // rows that will be deleted (mysql_affected_rows will always return 0 in
    // this case)
    if ($is_delete
        && eregi('^DELETE( .+)?( FROM (.+))$', $sql_query, $parts)
        && !eregi(' WHERE ', $parts[3])) {
        $OPresult     = @mysql_query('SELECT COUNT(*) as count' .  $parts[2]);
        if ($OPresult) {
            $num_rows = mysql_result($OPresult, 0, 'count');
        } else {
            $num_rows = 0;
        }
        mysql_free_result($OPresult);
    }

    // Executes the query
    $result   = @mysql_query($full_sql_query);

    // Displays an error message if required and stop parsing the script
    if (mysql_error()) {
        $error = mysql_error();
        include('./header.inc.php3');
        mysql_die($error, $full_sql_query);
    }

    // Gets the number of rows affected/returned
    if (!$is_affected) {
        $num_rows = @mysql_num_rows($result);
    } else if (!isset($num_rows)) {
        $num_rows = @mysql_affected_rows();
    }

    // Counts the total number of rows for the same 'SELECT' query without the
    // 'LIMIT' clause that may have been programatically added
    if (empty($sql_limit_to_append)) {
        $unlim_num_rows         = $num_rows;
    }
    else if ($is_select) {
        // reads only the from-part of the query...
        $array = split(' from | FROM | order | ORDER | having | HAVING | limit | LIMIT | group by | GROUP BY', $sql_query);
        if (!empty($array[1])) {
            // ... and makes a count(*) to count the entries
            $count_query = 'SELECT COUNT(*) AS count FROM ' . $array[1];
            $OPresult    = mysql_query($count_query);
            if ($OPresult) {
                $unlim_num_rows = mysql_result($OPresult, 0, 'count');
            }
            mysql_free_result($OPresult);
        } else {
            $unlim_num_rows     = 0;
        }
    } // end rows total count

    // No rows returned -> move back to the calling page
    if ($num_rows < 1 || $is_affected) {
        if (isset($strYes)) {
            if (isset($table)
                && (eregi('DROP[[:space:]]+(IF EXISTS[[:space:]]+)?TABLE[[:space:]]+`?' . $table . '`?[[:space:]]*$', $sql_query))) {
                unset($table);
            }
            if (isset($db)
                && (eregi('DROP[[:space:]]+(IF EXISTS[[:space:]]+)?DATABASE[[:space:]]+`?' . $db . '`?[[:space:]]*$', $sql_query))) {
                unset($db);
            }
        }
        if (file_exists('./' . $goto)) {
            if ($is_delete) {
                $message = $strDeletedRows . '&nbsp;' . $num_rows;
            } else if ($is_insert) {
                $message = $strInsertedRows . '&nbsp;' . $num_rows;
            } else if ($is_affected) {
                $message = $strAffectedRows . '&nbsp;' . $num_rows;
            } else if (!empty($zero_rows)) {
                $message = $zero_rows;
            } else {
                $message = $strEmptyResultSet;
            }
            $goto = ereg_replace('\.\.*', '.', $goto);
            if ($goto == 'db_details.php3' && !empty($table)) {
                unset($table);
            }
            if ($goto == 'db_details.php3' || $goto == 'tbl_properties.php3') {
                $js_to_run = 'functions.js';
            }
            if ($goto != 'main.php3') {
                include('./header.inc.php3');
            }
            include('./' . $goto);
        } // end if file_exist
        else {
            $message = $zero_rows;
            header('Location: ' . $cfgPmaAbsoluteUri . $goto);
        } // end else
        exit();
    } // end no rows returned

    // At least one row is returned -> displays a table with results
    else {
        // Displays the headers
        if (isset($show_query)) {
            unset($show_query);
        }
        $js_to_run = 'functions.js';
        include('./header.inc.php3');

        // Gets the list of fields properties 
        while ($field = mysql_fetch_field($result)) {
            $fields_meta[] = $field;
        }
        $fields_cnt        = count($fields_meta);

        // Displays the results in a table
        if (empty($disp_mode)) {
            // see the "set_display_mode()" function in lib.inc.php3
            $disp_mode = 'urdr11110';
        }
        display_table($result, $disp_mode);
        mysql_free_result($result);
        
        // Displays "Insert a new row" link if required
        if ($disp_mode[6] == '1') {
            $url_query = 'lang=' . $lang
                       . '&server=' . $server
                       . '&db=' . urlencode($db)
                       . '&table=' . urlencode($table)
                       . '&pos=' . $pos
                       . '&sql_query=' . urlencode($sql_query)
                       . '&goto=' . urlencode($goto);

            echo "\n\n";
            echo '<!-- Insert a new row -->' . "\n";
            echo '<p>' . "\n";
            echo '    <a href="tbl_change.php3?' . $url_query . '">' . $strInsertNewRow . '</a>' . "\n";
            echo '</p>' . "\n";
        } // end insert new row

        // Bookmark Support if required
        if ($disp_mode[7] == '1'
            && ($cfgBookmark['db'] && $cfgBookmark['table'] && empty($id_bookmark))
            && !empty($sql_query)) {
            echo "\n";

            $goto = 'sql.php3'
                  . '?lang=' . $lang
                  . '&server=' . $server
                  . '&db=' . urlencode($db)
                  . '&table=' . urlencode($table)
                  . '&pos=' . $pos
                  . '&sql_query=' . urlencode($sql_query)
                  . '&id_bookmark=1';
            ?>
<!-- Bookmark the query -->
<script type="text/javascript" language="javascript">
<!--
var errorMsg0 = '<?php echo(str_replace('\'', '\\\'', $strFormEmpty)); ?>';
//-->
</script>
<form action="sql.php3" method="post" onsubmit="return emptyFormElements(this, 'fields[label]');">
            <?php
            echo "\n";
            if ($disp_mode[3] == '1') {
                echo '    <i>' . $strOr . '</i>' . "\n";
            }
            ?>
    <br /><br />
    <?php echo $strBookmarkLabel; ?>&nbsp;:
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="fields[dbase]" value="<?php echo $db; ?>" />
    <input type="hidden" name="fields[user]" value="<?php echo $cfgBookmark['user']; ?>" />
    <input type="hidden" name="fields[query]" value="<?php echo urlencode($sql_query); ?>" />
    <input type="text" name="fields[label]" value="" />
    <input type="submit" name="store_bkm" value="<?php echo $strBookmarkThis; ?>" />
</form>
            <?php
        } // end bookmark support
    } // end rows returned

} // end executes the query
echo "\n\n";


/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
