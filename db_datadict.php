<?php
/* $Id$ */


/**
 * Gets the variables sent or posted to this script, then displays headers
 */
require_once('./libraries/common.lib.php');

if (!isset($selected_tbl)) {
    require_once('./libraries/header.inc.php');
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
    <p> <?php echo $strDBComment; ?> 
        <i><?php echo htmlspecialchars( implode( ' ', $comment ) ); ?></i></p>
        <?php
    } // end if
}

/**
 * Selects the database and gets tables names
 */
PMA_DBI_select_db($db);
$rowset = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($db) . ';', NULL, PMA_DBI_QUERY_STORE);

$count  = 0;
while ($row = PMA_DBI_fetch_assoc($rowset)) {
    $myfieldname = 'Tables_in_' . htmlspecialchars($db);
    $table        = $row[$myfieldname];
    if ($cfgRelation['commwork'] || PMA_MYSQL_INT_VERSION >= 40100) {
        $comments = PMA_getComments($db, $table);
    }

    if ($count != 0) {
        echo '<div style="page-break-before: always;">' . "\n";
    } else {
        echo '<div>' . "\n";
    }

    echo '<h2>' . $table . '</h2>' . "\n";

    /**
     * Gets table informations
     */
    // The 'show table' statement works correct since 3.23.03
    $showtable    = PMA_DBI_get_tables_full( $db, $table );
    $num_rows     = (isset($showtable[$table]['TABLE_ROWS']) ? $showtable[$table]['TABLE_ROWS'] : 0);
    $show_comment = (isset($showtable[$table]['TABLE_COMMENT']) ? $showtable[$table]['TABLE_COMMENT'] : '');
    unset( $showtable );


    /**
     * Gets table keys and retains them
     */

    PMA_DBI_select_db($db);
    $result       = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($table) . ';');
    $primary      = '';
    $indexes      = array();
    $lastIndex    = '';
    $indexes_info = array();
    $indexes_data = array();
    $pk_array     = array(); // will be use to emphasis prim. keys in the table
                             // view
    while ($row = PMA_DBI_fetch_assoc($result)) {
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
        PMA_DBI_free_result($result);
    }


    /**
     * Gets fields properties
     */
    $result      = PMA_DBI_query('SHOW FIELDS FROM ' . PMA_backquote($table) . ';', NULL, PMA_DBI_QUERY_STORE);
    $fields_cnt  = PMA_DBI_num_rows($result);
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
        echo $strTableComments . ': ' . $show_comment . '<br /><br />';
    }

    /**
     * Displays the table structure
     */
    ?> 

<table width="100%" class="print">
<tr><th width="50"><?php echo $strField; ?></th>
    <th width="80"><?php echo $strType; ?></th>
<?php /*    <th width="50"><?php echo $strAttr; ?></th>*/ ?>
    <th width="40"><?php echo $strNull; ?></th>
    <th width="70"><?php echo $strDefault; ?></th>
<?php /*    <th width="50"><?php echo $strExtra; ?></th>*/ ?>
    <?php
    if ($have_rel) {
        echo '    <th>' . $strLinksTo . '</th>' . "\n";
    }
    if ($cfgRelation['commwork'] || PMA_MYSQL_INT_VERSION >= 40100) {
        echo '    <th>' . $strComments . '</th>' . "\n";
    }
    if ($cfgRelation['mimework']) {
        echo '    <th>MIME</th>' . "\n";
    }
    ?>
</tr>
    <?php
    $odd_row = true;
    while ($row = PMA_DBI_fetch_assoc($result)) {

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
                $type     = ' ';
            }
        }
        $strAttribute     = ' ';
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
        ?> 
<tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
    <td nowrap="nowrap">
        <?php
        if (isset($pk_array[$row['Field']])) {
            echo '<u>' . $field_name . '</u>';
        } else {
            echo $field_name;
        }
        ?> 
    </td>
    <td<?php echo $type_nowrap; ?> xml:lang="en" dir="ltr"><?php echo $type; ?></td>
<?php /*    <td<?php echo $type_nowrap; ?>><?php echo $strAttribute; ?></td>*/ ?>
    <td><?php echo (($row['Null'] == '') ? $strNo : $strYes); ?></td>
    <td nowrap="nowrap"><?php if (isset($row['Default'])) echo $row['Default']; ?></td>
<?php /*    <td<?php echo $type_nowrap; ?>><?php echo $row['Extra']; ?></td>*/ ?>
        <?php
        if ($have_rel) {
            echo '    <td>';
            if (isset($res_rel[$field_name])) {
                echo htmlspecialchars($res_rel[$field_name]['foreign_table'] . ' -> ' . $res_rel[$field_name]['foreign_field']);
            }
            echo '</td>' . "\n";
        }
        if ($cfgRelation['commwork'] || PMA_MYSQL_INT_VERSION >= 40100) {
            echo '    <td>';
            if (isset($comments[$field_name])) {
                echo htmlspecialchars($comments[$field_name]);
            }
            echo '</td>' . "\n";
        }
        if ($cfgRelation['mimework']) {
            $mime_map = PMA_getMIME($db, $table, true);

            echo '    <td>';
            if (isset($mime_map[$field_name])) {
                echo htmlspecialchars(str_replace('_', '/', $mime_map[$field_name]['mimetype']));
            }
            echo '</td>' . "\n";
        }
        ?>
</tr>
        <?php
    } // end while
    PMA_DBI_free_result( $result );
    $count++;
    ?> 
</table>
</div>
    <?php
} //ends main while

/**
 * Displays the footer
 */
?> 
<script type="text/javascript" language="javascript">
//<![CDATA[
function printPage()
{
    document.getElementById('print').style.visibility = 'hidden';
    // Do print the page
    if (typeof(window.print) != 'undefined') {
        window.print();
    }
    document.getElementById('print').style.visibility = '';
}
//]]>
</script>
<?php
echo '<br /><br /><input type="button" id="print" value="' . $strPrint . '" onclick="printPage()" />';

require_once('./libraries/footer.inc.php');
?>
