<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');

/**
 * Check rights in case of DROP DATABASE
 */

if (eregi('DROP DATABASE', $sql_query)) {
    // Check if the user is a Superuser - TODO: set a global variable with this information

    $is_superuser = FALSE;
    $result = mysql_query('SELECT * FROM mysql.user');
    $rows   = @mysql_num_rows($result);
    if (!empty($rows)) { $is_superuser = TRUE; }

    if (!$cfgAllowUserDropDatabase && !$is_superuser) {
        include('./header.inc.php3');
	echo '<b>' . $strAccessDenied . '</b>' . "\n";
        require('./footer.inc.php3');
        exit();
    }
}




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
 * Go back to further page if table should not be dropped
 */
if (isset($goto) && $goto == 'sql.php3') {
    $goto = 'sql.php3'
          . '?lang=' . $lang
          . '&server=' . urlencode($server)
          . '&db=' . urlencode($db)
          . '&table=' . urlencode($table)
          . '&pos=' . $pos
          . '&sql_query=' . urlencode($sql_query);
}
if (isset($btnDrop) && $btnDrop == $strNo) {
    if (!empty($back)) {
        $goto = $back;
    }
    if (file_exists('./' . $goto)) {
        include('./' . ereg_replace('\.\.*', '.', $goto));
    } else {
        header('Location: ' . $cfgPmaAbsoluteUri . $goto);
    }
    exit();
} // end if


/**
 * Displays the confirm page if required
 */
$do_confirm   = ($cfgConfirm
                 && !isset($btnDrop)
                 && eregi('DROP +(TABLE|DATABASE)|ALTER TABLE +[[:alnum:]_`]* +DROP|DELETE FROM', $sql_query));
if ($do_confirm) {
    if (get_magic_quotes_gpc()) {
        $stripped_sql_query = stripslashes($sql_query);
    } else {
        $stripped_sql_query = $sql_query;
    }
    include('./header.inc.php3');
    echo $strDoYouReally . '&nbsp;:<br />' . "\n";
    echo '<tt>' . htmlspecialchars($stripped_sql_query) . '</tt>&nbsp;?<br/>';
    ?>
<form action="sql.php3" method="post" enctype="application/x-www-form-urlencoded">
    <input type="hidden" name="sql_query" value="<?php echo urlencode($sql_query); ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="zero_rows" value="<?php echo isset($zero_rows) ? $zero_rows : ''; ?>" />
    <input type="hidden" name="table" value="<?php echo isset($table) ? $table : ''; ?>" />
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

    $is_select = $is_count = $is_delete = $is_insert = $is_affected = FALSE;
    if (eregi('^SELECT ', $sql_query)) {
        $is_select   = TRUE;
        $is_count    = (eregi('^SELECT COUNT\((.*\.+)?\*\) FROM ', $sql_query));
    } else if (eregi('^DELETE ', $sql_query)) {
        $is_delete   = TRUE;
        $is_affected = TRUE;
    } else if (eregi('^(INSERT|LOAD DATA) ', $sql_query)) {
        $is_insert   = TRUE;
        $is_affected = TRUE;
    } else if (eregi('^UPDATE ', $sql_query)) {
        $is_affected = TRUE;
    }

    $sql_limit_to_append  = (isset($pos)
                             && ($is_select && !$is_count)
                             && !eregi(' LIMIT[ 0-9,]+$', $sql_query))
                          ? " LIMIT $pos, $cfgMaxRows"
                          : '';
    if (eregi('(.*)( PROCEDURE (.*)| FOR UPDATE| LOCK IN SHARE MODE)$', $sql_query, $regs)) {
        $full_sql_query   = $regs[1] . $sql_limit_to_append . $regs[2];
    } else {
        $full_sql_query   = $sql_query . $sql_limit_to_append;
    }

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
        $SelectNumRows = $num_rows;
    }
    else if ($is_select) {
        // reads only the from-part of the query...
        $array = split(' from | FROM | order | ORDER | having | HAVING | limit | LIMIT | group by | GROUP BY', $sql_query);
        if (!empty($array[1])) {
            // ... and makes a count(*) to count the entries
            $count_query       = 'SELECT COUNT(*) AS count FROM ' . $array[1];
            $OPresult          = mysql_query($count_query);
            if ($OPresult) {
                $SelectNumRows = mysql_result($OPresult, 0, 'count');
            }
        } else {
            $SelectNumRows     = 0;
        }
    } // end rows total count

    // No rows returned -> move back to the calling page
    if ($num_rows < 1 || $is_affected) {
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
        include('./header.inc.php3');
        // Defines the display mode if it wasn't passed by url
        if ($is_count) {
            $display = 'simple';
        }
        if (!isset($display)) {
            $display = eregi('^((SHOW (VARIABLES|PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS))|((CHECK|ANALYZE|REPAIR|OPTIMIZE) TABLE ))', $sql_query, $which);
            if (!empty($which[2]) && !empty($which[3])) {
                $display = 'simple';
            } else if (!empty($which[4]) && !empty($which[5])) {
                $display = 'bkmOnly';
            }
        }

        // Displays the results in a table
        display_table($result, ($display == 'simple' || $display == 'bkmOnly'));
        
        if ($display != 'simple') {
            // Insert a new row
            if ($display != 'bkmOnly') {
                $url_query = 'lang=' . $lang
                           . '&server=' . urlencode($server)
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
            } // end insert row

            // Bookmark Support
            if ($cfgBookmark['db'] && $cfgBookmark['table'] && empty($id_bookmark)
                && !empty($sql_query)) {
                echo "\n";
                ?>
<!-- Bookmark the query -->
<script type="text/javascript" language="javascript">
<!--
var errorMsg0 = '<?php echo(str_replace('\'', '\\\'', $strFormEmpty)); ?>';
//-->
</script>
<form method="post" action="sql.php3" onsubmit="return emptyFormElements(this, 'fields[label]');">
                <?php
                echo "\n";
                if ($display != 'bkmOnly') {
                    echo '    <i>' . $strOr . '</i>' . "\n";
                }
                echo '    <br /><br />' . "\n";
                echo '    ' . $strBookmarkLabel . '&nbsp;:' . "\n";
                $goto = 'sql.php3'
                      . '?lang=' . $lang
                      . '&server=' . urlencode($server)
                      . '&db=' . urlencode($db)
                      . '&table=' . urlencode($table)
                      . '&pos=' . $pos
                      . '&sql_query=' . urlencode($sql_query)
                      . '&id_bookmark=1';
                ?>
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="fields[dbase]" value="<?php echo $db; ?>" />
    <input type="hidden" name="fields[user]" value="<?php echo $cfgBookmark['user']; ?>" />
    <input type="hidden" name="fields[query]" value="<?php echo urlencode($sql_query); ?>" />
    <input type="text" name="fields[label]" value="" />
    <input type="submit" name="store_bkm" value="<?php echo $strBookmarkThis; ?>" />
</form>
                <?php
            } // end bookmark support
        } // end display != simple
    } // end rows returned
} // end executes the query
echo "\n\n";


/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
