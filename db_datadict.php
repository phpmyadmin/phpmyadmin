<?php
/* $Id$ */


/**
 * Gets the variables sent or posted to this script, then displays headers
 */
if (!isset($selected_tbl)) {
    require_once('./libraries/grab_globals.lib.php');
    require_once('./header.inc.php');
}


/**
 * Gets the relations settings
 */
require_once('./libraries/relation.lib.php');
require_once('./libraries/transformations.lib.php');

$cfgRelation  = PMA_getRelationsParam();

/**
 * Check parameters
 */
PMA_checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (isset($table)) {
    $err_url = 'tbl_properties.php?' . PMA_generate_common_url($db, $table);
} else {
    $err_url = 'db_details.php?' . PMA_generate_common_url($db);
}

if ($cfgRelation['commwork']) {
    $comment = PMA_getComments($db);

    /**
     * Displays DB comment
     */
    if (is_array($comment)) {
        ?>
    <!-- DB comment -->
    <p><?php echo $strDBComment; ?> <i>
        <?php echo htmlspecialchars(implode(' ', $comment)) . "\n"; ?>
    </i></p>
        <?php
    } // end if
}

/**
 * Selects the database and gets tables names
 */
PMA_mysql_select_db($db);
$sql    = 'SHOW TABLES FROM ' . PMA_backquote($db);
$rowset = @PMA_mysql_query($sql);

if (!$rowset) {
    exit();
}
$count  = 0;
while ($row = mysql_fetch_array($rowset)) {
    $myfieldname = 'Tables_in_' . htmlspecialchars($db);
    $table        = $row[$myfieldname];
    if ($cfgRelation['commwork']) {
        $comments = PMA_getComments($db, $table);
    }

    if ($count != 0) {
        echo '<div style="page-break-before: always">' . "\n";
    }
    echo '<h1>' . $table . '</h1>' . "\n";

    /**
     * Gets table informations
     */
    // The 'show table' statement works correct since 3.23.03
    $local_query  = 'SHOW TABLE STATUS LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'';
    $result       = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    $showtable    = PMA_mysql_fetch_array($result);
    $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
    $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');
    if ($result) {
         mysql_free_result($result);
    }


    /**
     * Gets table keys and retains them
     */
    $local_query  = 'SHOW KEYS FROM ' . PMA_backquote($table);
    $result       = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    $primary      = '';
    $indexes      = array();
    $lastIndex    = '';
    $indexes_info = array();
    $indexes_data = array();
    $pk_array     = array(); // will be use to emphasis prim. keys in the table
                             // view
    while ($row = PMA_mysql_fetch_array($result)) {
        // Backups the list of primary keys
        if ($row['Key_name'] == 'PRIMARY') {
            $primary   .= $row['Column_name'] . ', ';
            $pk_array[$row['Column_name']] = 1;
        }
        // Retains keys informations
        if ($row['Key_name'] != $lastIndex ){
            $indexes[] = $row['Key_name'];
            $lastIndex = $row['Key_name'];
        }
        $indexes_info[$row['Key_name']]['Sequences'][]     = $row['Seq_in_index'];
        $indexes_info[$row['Key_name']]['Non_unique']      = $row['Non_unique'];
        if (isset($row['Cardinality'])) {
            $indexes_info[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
        }
        // I don't know what does following column mean....
        // $indexes_info[$row['Key_name']]['Packed']          = $row['Packed'];

        $indexes_info[$row['Key_name']]['Comment']     = $row['Comment'];

        $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Column_name']  = $row['Column_name'];
        if (isset($row['Sub_part'])) {
            $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Sub_part'] = $row['Sub_part'];
        }

    } // end while
    if ($result) {
        mysql_free_result($result);
    }


    /**
     * Gets fields properties
     */
    $local_query = 'SHOW FIELDS FROM ' . PMA_backquote($table);
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    $fields_cnt  = mysql_num_rows($result);

    // Check if we can use Relations (Mike Beck)
    if (!empty($cfgRelation['relation'])) {
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel = PMA_getForeigners($db, $table);

        if (count($res_rel) > 0) {
            $have_rel = TRUE;
        } else {
            $have_rel = FALSE;
        }
    }
    else {
        $have_rel = FALSE;
    } // end if


    /**
     * Displays the comments of the table if MySQL >= 3.23
     */
    if (!empty($show_comment)) {
        echo $strTableComments . '&nbsp;:&nbsp;' . $show_comment . '<br /><br />';
    }

    /**
     * Displays the table structure
     */
    ?>

<!-- TABLE INFORMATIONS -->
<table width="100%" bordercolorlight="black" border="border" style="border-collapse: collapse;background-color: white">
<tr>
    <th width="50"><?php echo $strField; ?></th>
    <th width="80"><?php echo $strType; ?></th>
    <!--<th width="50"><?php echo $strAttr; ?></th>-->
    <th width="40"><?php echo $strNull; ?></th>
    <th width="70"><?php echo $strDefault; ?></th>
    <!--<th width="50"><?php echo $strExtra; ?></th>-->
    <?php
    echo "\n";
    if ($have_rel) {
        echo '    <th>' . $strLinksTo . '</th>' . "\n";
    }
    if ($cfgRelation['commwork']) {
        echo '    <th>' . $strComments . '</th>' . "\n";
    }
    if ($cfgRelation['mimework']) {
        echo '    <th>MIME</th>' . "\n";
    }
    ?>
</tr>

    <?php
    $i = 0;
    while ($row = PMA_mysql_fetch_array($result)) {
        $bgcolor = ($i % 2) ?$cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        $i++;

        $type             = $row['Type'];
        // reformat mysql query output - staybyte - 9. June 2001
        // loic1: set or enum types: slashes single quotes inside options
        if (preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)) {
            $tmp[2]       = substr(preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1);
            $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
            $type_nowrap  = '';

            $binary       = 0;
            $unsigned     = 0;
            $zerofill     = 0;
        } else {
            $binary       = stristr($row['Type'], 'binary');
            $unsigned     = stristr($row['Type'], 'unsigned');
            $zerofill     = stristr($row['Type'], 'zerofill');
            $type_nowrap  = ' nowrap="nowrap"';
            $type         = preg_replace('@BINARY@i', '', $type);
            $type         = preg_replace('@ZEROFILL@i', '', $type);
            $type         = preg_replace('@UNSIGNED@i', '', $type);
            if (empty($type)) {
                $type     = '&nbsp;';
            }
        }
        $strAttribute     = '&nbsp;';
        if ($binary) {
            $strAttribute = 'BINARY';
        }
        if ($unsigned) {
            $strAttribute = 'UNSIGNED';
        }
        if ($zerofill) {
            $strAttribute = 'UNSIGNED ZEROFILL';
        }
        if (!isset($row['Default'])) {
            if ($row['Null'] != '') {
                $row['Default'] = '<i>NULL</i>';
            }
        } else {
            $row['Default'] = htmlspecialchars($row['Default']);
        }
        $field_name = htmlspecialchars($row['Field']);
        echo "\n";
        ?>
<tr>
    <td width=50 class='print' nowrap="nowrap">
        <?php
        echo "\n";
        if (isset($pk_array[$row['Field']])) {
            echo '    <u>' . $field_name . '</u>&nbsp;' . "\n";
        } else {
            echo '    ' . $field_name . '&nbsp;' . "\n";
        }
        ?>
    </td>
    <td width="80" class="print"<?php echo $type_nowrap; ?>><?php echo $type; ?><bdo dir="ltr"></bdo></td>
    <!--<td width="50" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $strAttribute; ?></td>-->
    <td width="40" class="print"><?php echo (($row['Null'] == '') ? $strNo : $strYes); ?>&nbsp;</td>
    <td width="70" class="print" nowrap="nowrap"><?php if (isset($row['Default'])) echo $row['Default']; ?>&nbsp;</td>
    <!--<td width="50" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $row['Extra']; ?>&nbsp;</td>-->
        <?php
        echo "\n";
        if ($have_rel) {
            echo '    <td class="print">';
            if (isset($res_rel[$field_name])) {
                echo htmlspecialchars($res_rel[$field_name]['foreign_table'] . ' -> ' . $res_rel[$field_name]['foreign_field']);
            }
            echo '&nbsp;</td>' . "\n";
        }
        if ($cfgRelation['commwork']) {
            echo '    <td class="print">';
            if (isset($comments[$field_name])) {
                echo htmlspecialchars($comments[$field_name]);
            }
            echo '&nbsp;</td>' . "\n";
        }
        if ($cfgRelation['mimework']) {
            $mime_map = PMA_getMIME($db, $table, true);

            echo '    <td class="print">';
            if (isset($mime_map[$field_name])) {
                echo htmlspecialchars(str_replace('_', '/', $mime_map[$field_name]['mimetype']));
            }
            echo '&nbsp;</td>' . "\n";
        }
        ?>
</tr>
        <?php
    } // end while
    mysql_free_result($result);

    echo "\n";
    ?>
</table>

    <?php
    echo '</div>' . "\n";

    $count++;
} //ends main while


/**
 * Displays the footer
 */
echo "\n";
?>
<script type="text/javascript" language="javascript1.2">
<!--
function printPage()
{
    document.getElementById('print').style.visibility = 'hidden';
    // Do print the page
    if (typeof(window.print) != 'undefined') {
        window.print();
    }
    document.getElementById('print').style.visibility = '';
}
//-->
</script>
<?php
echo '<br /><br />&nbsp;<input type="button" style="visibility: ; width: 100px; height: 25px" id="print" value="' . $strPrint . '" onclick="printPage()">' . "\n";

require_once('./footer.inc.php');
?>
