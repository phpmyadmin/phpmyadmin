<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');

/**
 * Bookmark Add
 */
if(isset($bookmarkthis)) {
	add_bookmarks($fields, $cfgBookmark);
	Header("Location: $goto");
}

/**
 * Gets the true sql query
 */
// $sql_query has been urlencoded in the confirmation form for drop/delete
// queries or in the navigation bar for browsing among records
if (isset($btnDrop) || isset($navig)) {
    $sql_query = urldecode($sql_query);
    if (isset($sql_order)) {
        $sql_order = urldecode($sql_order);
    }
}


/**
 * Go back to further page if table should not be dropped
 */
if (isset($goto) && $goto == 'sql.php3') {
    $goto = "sql.php3?server=$server&lang=$lang&db=$db&table=$table&pos=$pos&sql_query=" . urlencode($sql_query);
}
if (isset($btnDrop) && $btnDrop == $strNo) {
    if (file_exists('./' . $goto)) {
        include('./' . preg_replace('/\.\.*/', '.', $goto));
    } else {
        header('Location: ' . $goto);
    }
    exit();
} // end if


/**
 * Defines some "properties" of the sql query to submit
 */
$do_confirm = ($cfgConfirm
               && !isset($btnDrop)
               && eregi('DROP +(TABLE|DATABASE)|ALTER TABLE +[[:alnum:]_]* +DROP|DELETE FROM', $sql_query));
$is_select  = eregi('^SELECT ', $sql_query);
$is_delupd  = eregi('^(DELETE|UPDATE) ', $sql_query);


/**
 * Displays the confirm page if required
 */
if ($do_confirm) {
    if (get_magic_quotes_gpc()) {
        $stripped_sql_query = stripslashes($sql_query);
    } else {
        $stripped_sql_query = $sql_query;
    }
    include('./header.inc.php3');
    echo $strDoYouReally . htmlspecialchars($sql_query) . '&nbsp;?<br/>';
    ?>
<form action="sql.php3" method="post" enctype="application/x-www-form-urlencoded">
    <input type="hidden" name="sql_query" value="<?php echo urlencode($sql_query); ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="zero_rows" value="<?php echo isset($zero_rows) ? $zero_rows : ''; ?>" />
    <input type="hidden" name="table" value="<?php echo isset($table) ? $table : ''; ?>" />
    <input type="hidden" name="goto" value="<?php echo isset($goto) ? $goto : ''; ?>" />
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
    if (get_magic_quotes_gpc()) {
        $sql_query     = isset($sql_query) ? stripslashes($sql_query) : '';
        $sql_order     = isset($sql_order) ? stripslashes($sql_order) : '';
    } else {
        if (!isset($sql_query)) {
            $sql_query = '';
        }
        if (!isset($sql_order)) {
            $sql_order = '';
        }
    }

    //defines some variables
    // loic1: A table have to be created -> left frame should be reloaded
    if (!empty($reload) && eregi('^CREATE TABLE (.*)', $sql_query)) {
        $reload           = 'true';
    }
    if (isset($sessionMaxRows)) {
        $cfgMaxRows       = $sessionMaxRows;
    }
    $sql_limit_to_append  = (isset($pos) && $is_select && !eregi(' LIMIT[ 0-9,]+$', $sql_query))
                          ? " LIMIT $pos, $cfgMaxRows"
                          : '';
    $full_sql_query       = $sql_query . $sql_order . $sql_limit_to_append;

    // Executes the query and gets the number of rows returned
    mysql_select_db($db);
    $result   = @mysql_query($full_sql_query);
    $num_rows = @mysql_num_rows($result);

    // Counts the total number of rows for the same 'SELECT' query without the
    // 'LIMIT' clause that may have been programatically added
    if (empty($sql_limit_to_append)) {
        $SelectNumRows = $num_rows;
    }
    else if ($is_select) {
        // reads only the from-part of the query...
        $array = split(' from | FROM ', $sql_query, 2);
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

    // Displays an error message if required
    if (!$result) {
        $error = mysql_error();
        include('./header.inc.php3');
        mysql_die($error);
    } // end if

    // No rows returned -> move back to the calling page
    if ($num_rows < 1) {
        if (file_exists('./' . $goto)) {
            if ($is_delupd) {
                $message = $strAffectedRows . '&nbsp;' . mysql_affected_rows();
            } else if (!empty($zero_rows)) {
                $message = $zero_rows;
            } else {
                $message = $strEmptyResultSet;
            }
            $goto = preg_replace('/\.\.*/', '.', $goto);
            if ($goto != 'main.php3') {
                include('./header.inc.php3');
            }
            include('./' . $goto);
        } // end if file_exist
        else {
            $message = $zero_rows;
            header('Location: ' . $goto);
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
                           . '&sql_query=' . urlencode($full_sql_query)
                           . '&goto=' . urlencode($goto);
                echo "\n\n";
                echo '<!-- Insert a new row -->' . "\n";
                echo '<p>' . "\n";
                echo '    <a href="tbl_change.php3?' . $url_query . '">' . $strInsertNewRow . '</a>' . "\n";
                echo '</p>' . "\n";
            } // end insert row

            // Bookmark Support
            if ($cfgBookmark['db'] && $cfgBookmark['table'] && empty($id_bookmark)) {
                echo "\n";
                echo '<!-- Bookmark the query -->' . "\n";
                echo '<form method="post" action="sql.php3">' . "\n";
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
                      . '&sql_query=' . urlencode($full_sql_query)
                      . '&id_bookmark=1';
                ?>
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="bookmarkthis" value="true" />
    <input type="hidden" name="fields[dbase]" value="<?php echo $db;?>" />
    <input type="hidden" name="fields[user]" value="<?php echo $cfgBookmark['user'];?>" />
    <input type="hidden" name="fields[query]" value="<?php echo isset($sql_query) ? $sql_query : "";?>" />
    <input type="text"   name="fields[label]" value="">
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
