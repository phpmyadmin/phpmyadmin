<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

require './libraries/tbl_common.php';

/**
 * Gets the variables sent or posted to this script, then displays headers
 */
$print_view = true;
if (! isset($selected_tbl)) {
    require_once './libraries/header.inc.php';
}

// Check parameters

if (! isset($the_tables) || ! is_array($the_tables)) {
    $the_tables = array();
}

/**
 * Gets the relations settings
 */
require_once './libraries/relation.lib.php';
require_once './libraries/transformations.lib.php';
require_once './libraries/Index.class.php';

$cfgRelation = PMA_getRelationsParam();

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (strlen($table)) {
    $err_url = 'tbl_sql.php?' . PMA_generate_common_url($db, $table);
} else {
    $err_url = 'db_sql.php?' . PMA_generate_common_url($db);
}


/**
 * Selects the database
 */
PMA_DBI_select_db($db);


/**
 * Multi-tables printview thanks to Christophe Gesche from the "MySQL Form
 * Generator for PHPMyAdmin" (http://sourceforge.net/projects/phpmysqlformgen/)
 */
if (isset($selected_tbl) && is_array($selected_tbl)) {
    $the_tables   = $selected_tbl;
} elseif (strlen($table)) {
    $the_tables[] = $table;
}
$multi_tables     = (count($the_tables) > 1);

if ($multi_tables) {
    if (empty($GLOBALS['is_header_sent'])) {
        require_once './libraries/header.inc.php';
    }
    $tbl_list     = '';
    foreach ($the_tables as $key => $table) {
        $tbl_list .= (empty($tbl_list) ? '' : ', ')
                  . PMA_backquote($table);
    }
    echo '<strong>'.  $strShowTables . ': ' . $tbl_list . '</strong>' . "\n";
    echo '<hr />' . "\n";
} // end if

$tables_cnt = count($the_tables);
$counter    = 0;

foreach ($the_tables as $key => $table) {
    if ($counter + 1 >= $tables_cnt) {
        $breakstyle = '';
    } else {
        $breakstyle = ' style="page-break-after: always;"';
    }
    $counter++;
    echo '<div' . $breakstyle . '>' . "\n";
    echo '<h1>' . $table . '</h1>' . "\n";

    /**
     * Gets table informations
     */
    $showtable    = PMA_Table::sGetStatusInfo($db, $table);
    $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
    $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');

    $tbl_is_view = PMA_Table::isView($db, $table);

    /**
     * Gets fields properties
     */
    $result      = PMA_DBI_query(
        'SHOW FIELDS FROM ' . PMA_backquote($table) . ';', null,
        PMA_DBI_QUERY_STORE);
    $fields_cnt  = PMA_DBI_num_rows($result);


// We need this to correctly learn if a TIMESTAMP is NOT NULL, since
// SHOW FULL FIELDS or INFORMATION_SCHEMA incorrectly says NULL
// and SHOW CREATE TABLE says NOT NULL (tested
// in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

    $show_create_table = PMA_DBI_fetch_value(
        'SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table),
        0, 1);
    $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

    // Check if we can use Relations (Mike Beck)
    // Find which tables are related with the current one and write it in
    // an array
    $res_rel  = PMA_getForeigners($db, $table);
    $have_rel = (bool) count($res_rel);

    /**
     * Displays the comments of the table if MySQL >= 3.23
     */
    if (!empty($show_comment)) {
        echo $strTableComments . ': ' . htmlspecialchars($show_comment) . '<br /><br />';
    }

    /**
     * Displays the table structure
     */
    ?>

<!-- TABLE INFORMATIONS -->
<table style="width: 100%;">
<thead>
<tr>
    <th><?php echo $strField; ?></th>
    <th><?php echo $strType; ?></th>
    <!--<th><?php echo $strAttr; ?></th>-->
    <th><?php echo $strNull; ?></th>
    <th><?php echo $strDefault; ?></th>
    <!--<th><?php echo $strExtra; ?></th>-->
    <?php
    if ($have_rel) {
        echo '<th>' . $strLinksTo . '</th>' . "\n";
    }
    echo '    <th>' . $strComments . '</th>' . "\n";
    if ($cfgRelation['mimework']) {
        echo '    <th>MIME</th>' . "\n";
    }
    ?>
</tr>
</thead>
<tbody>
    <?php
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $type             = $row['Type'];
        // reformat mysql query output - staybyte - 9. June 2001
        // loic1: set or enum types: slashes single quotes inside options
        if (preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)) {
            $tmp[2]       = substr(preg_replace('@([^,])\'\'@', '\\1\\\'',
                                    ',' . $tmp[2]), 1);
            $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';

            $binary       = 0;
            $unsigned     = 0;
            $zerofill     = 0;
        } else {
            $type         = preg_replace('@BINARY@i', '', $type);
            $type         = preg_replace('@ZEROFILL@i', '', $type);
            $type         = preg_replace('@UNSIGNED@i', '', $type);
            if (empty($type)) {
                $type     = '&nbsp;';
            }

            $binary       = stristr($row['Type'], 'binary');
            $unsigned     = stristr($row['Type'], 'unsigned');
            $zerofill     = stristr($row['Type'], 'zerofill');
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
            if ($row['Null'] != ''  && $row['Null'] != 'NO') {
                $row['Default'] = '<i>NULL</i>';
            }
        } else {
            $row['Default'] = htmlspecialchars($row['Default']);
        }
        $field_name = htmlspecialchars($row['Field']);

        // here, we have a TIMESTAMP that SHOW FULL FIELDS reports as having the
        // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
        // the latter.
        /**
         * @todo merge this logic with the one in tbl_structure.php
         * or move it in a function similar to PMA_DBI_get_columns_full()
         * but based on SHOW CREATE TABLE because information_schema
         * cannot be trusted in this case (MySQL bug)
         */
        if (!empty($analyzed_sql[0]['create_table_fields'][$field_name]['type']) && $analyzed_sql[0]['create_table_fields'][$field_name]['type'] == 'TIMESTAMP' && $analyzed_sql[0]['create_table_fields'][$field_name]['timestamp_not_null']) {
            $row['Null'] = '';
        }
        ?>

<tr><td>
    <?php
    if (isset($pk_array[$row['Field']])) {
        echo '    <u>' . $field_name . '</u>' . "\n";
    } else {
        echo '    ' . $field_name . "\n";
    }
    ?>
    </td>
    <td><?php echo $type; ?><bdo dir="ltr"></bdo></td>
    <!--<td><?php echo $strAttribute; ?></td>-->
    <td><?php echo (($row['Null'] == '' || $row['Null'] == 'NO') ? $strNo : $strYes); ?>&nbsp;</td>
    <td><?php if (isset($row['Default'])) { echo $row['Default']; } ?>&nbsp;</td>
    <!--<td><?php echo $row['Extra']; ?>&nbsp;</td>-->
    <?php
    if ($have_rel) {
        echo '    <td>';
        if (isset($res_rel[$field_name])) {
            echo htmlspecialchars($res_rel[$field_name]['foreign_table'] . ' -> ' . $res_rel[$field_name]['foreign_field']);
        }
        echo '&nbsp;</td>' . "\n";
    }
    echo '    <td>';
    $comments = PMA_getComments($db, $table);
    if (isset($comments[$field_name])) {
        echo htmlspecialchars($comments[$field_name]);
    }
    echo '&nbsp;</td>' . "\n";
    if ($cfgRelation['mimework']) {
        $mime_map = PMA_getMIME($db, $table, true);

        echo '    <td>';
        if (isset($mime_map[$field_name])) {
            echo htmlspecialchars(str_replace('_', '/', $mime_map[$field_name]['mimetype']));
        }
        echo '&nbsp;</td>' . "\n";
    }
    ?>
</tr>
        <?php
    } // end while
    PMA_DBI_free_result($result);
    ?>
</tbody>
</table>
    <?php
    if (! $tbl_is_view && $db != 'information_schema') {
        /**
         * Displays indexes
         */
        echo PMA_Index::getView($table, $db, true);

        /**
         * Displays Space usage and row statistics
         *
         * staybyte - 9 June 2001
         */
        if ($cfg['ShowStats']) {
            $nonisam     = false;
            if (isset($showtable['Type']) && !preg_match('@ISAM|HEAP@i', $showtable['Type'])) {
                $nonisam = true;
            }
            if ($nonisam == false) {
                // Gets some sizes

		$mergetable = PMA_Table::isMerge($db, $table);

                list($data_size, $data_unit)         = PMA_formatByteDown($showtable['Data_length']);
                if ($mergetable == false) {
                    list($index_size, $index_unit)   = PMA_formatByteDown($showtable['Index_length']);
                }
                if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
                    list($free_size, $free_unit)     = PMA_formatByteDown($showtable['Data_free']);
                    list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free']);
                } else {
                    unset($free_size);
                    unset($free_unit);
                    list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length']);
                }
                list($tot_size, $tot_unit)           = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length']);
                if ($num_rows > 0) {
                    list($avg_size, $avg_unit)       = PMA_formatByteDown(($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1);
                }

                // Displays them
                ?>
    <br /><br />

    <table border="0" cellspacing="0" cellpadding="0" class="noborder">
    <tr>

        <!-- Space usage -->
        <td valign="top">
            <big><?php echo $strSpaceUsage . ':'; ?></big>
            <table width="100%">
            <tr>
                <th><?php echo $strType; ?></th>
                <th colspan="2" align="center"><?php echo $strUsage; ?></th>
            </tr>
            <tr>
                <td style="padding-right: 10px"><?php echo $strData; ?></td>
                <td align="right"><?php echo $data_size; ?></td>
                <td><?php echo $data_unit; ?></td>
            </tr>
                <?php
                if (isset($index_size)) {
                    echo "\n";
                    ?>
            <tr>
                <td style="padding-right: 10px"><?php echo $strIndex; ?></td>
                <td align="right"><?php echo $index_size; ?></td>
                <td><?php echo $index_unit; ?></td>
            </tr>
                    <?php
                }
                if (isset($free_size)) {
                    echo "\n";
                    ?>
            <tr style="color: #bb0000">
                <td style="padding-right: 10px"><?php echo $strOverhead; ?></td>
                <td align="right"><?php echo $free_size; ?></td>
                <td><?php echo $free_unit; ?></td>
            </tr>
            <tr>
                <td style="padding-right: 10px"><?php echo $strEffective; ?></td>
                <td align="right"><?php echo $effect_size; ?></td>
                <td><?php echo $effect_unit; ?></td>
            </tr>
                    <?php
                }
                if (isset($tot_size) && $mergetable == false) {
                    echo "\n";
                    ?>
            <tr>
                <td style="padding-right: 10px"><?php echo $strTotalUC; ?></td>
                <td align="right"><?php echo $tot_size; ?></td>
                <td><?php echo $tot_unit; ?></td>
            </tr>
                    <?php
                }
                echo "\n";
                ?>
            </table>
        </td>

        <td width="20">&nbsp;</td>

        <!-- Rows Statistic -->
        <td valign="top">
            <big><?php echo $strRowsStatistic . ':'; ?></big>
            <table width="100%">
            <tr>
                <th><?php echo $strStatement; ?></th>
                <th align="center"><?php echo $strValue; ?></th>
            </tr>
                <?php
                if (isset($showtable['Row_format'])) {
                    ?>
            <tr>
                <td><?php echo ucfirst($strFormat); ?></td>
                <td align="<?php echo $cell_align_left; ?>">
                    <?php
                    if ($showtable['Row_format'] == 'Fixed') {
                        echo $strStatic;
                    } elseif ($showtable['Row_format'] == 'Dynamic') {
                        echo $strDynamic;
                    } else {
                        echo $showtable['Row_format'];
                    }
                    ?>
                </td>
            </tr>
                    <?php
                }
                if (isset($showtable['Rows'])) {
                    ?>
            <tr>
                <td><?php echo ucfirst($strRows); ?></td>
                <td align="right">
                    <?php echo PMA_formatNumber($showtable['Rows'], 0) . "\n"; ?>
                </td>
            </tr>
                    <?php
                }
                if (isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
                    ?>
            <tr>
                <td><?php echo ucfirst($strRowLength); ?>&nbsp;&oslash;</td>
                <td>
                    <?php echo PMA_formatNumber($showtable['Avg_row_length'], 0) . "\n"; ?>
                </td>
            </tr>
                    <?php
                }
                if (isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == false) {
                    ?>
            <tr>
                <td><?php echo ucfirst($strRowSize); ?>&nbsp;&oslash;</td>
                <td align="right">
                    <?php echo $avg_size . ' ' . $avg_unit . "\n"; ?>
                </td>
            </tr>
                    <?php
                }
                if (isset($showtable['Auto_increment'])) {
                    ?>
            <tr>
                <td><?php echo ucfirst($strNext); ?>&nbsp;Autoindex</td>
                <td align="right">
                    <?php echo PMA_formatNumber($showtable['Auto_increment'], 0) . "\n"; ?>
                </td>
            </tr>
                    <?php
                }
                if (isset($showtable['Create_time'])) {
                    ?>
            <tr>
                <td><?php echo $strStatCreateTime; ?></td>
                <td align="right">
                    <?php echo PMA_localisedDate(strtotime($showtable['Create_time'])) . "\n"; ?>
                </td>
            </tr>
                    <?php
                }
                if (isset($showtable['Update_time'])) {
                    ?>
            <tr>
                <td><?php echo $strStatUpdateTime; ?></td>
                <td align="right">
                    <?php echo PMA_localisedDate(strtotime($showtable['Update_time'])) . "\n"; ?>
                </td>
            </tr>
                    <?php
                }
                if (isset($showtable['Check_time'])) {
                    ?>
            <tr>
                <td><?php echo $strStatCheckTime; ?></td>
                <td align="right">
                    <?php echo PMA_localisedDate(strtotime($showtable['Check_time'])) . "\n"; ?>
                </td>
            </tr>
                    <?php
                }
                ?>

            </table>
        </td>
    </tr>
    </table>

                <?php
            } // end if ($nonisam == false)
        } // end if ($cfg['ShowStats'])
    }
    if ($multi_tables) {
        unset($num_rows, $show_comment);
        echo '<hr />' . "\n";
    } // end if
    echo '</div>' . "\n";

} // end while

/**
 * Displays the footer
 */
?>

<script type="text/javascript">
//<![CDATA[
function printPage()
{
    // Do print the page
    if (typeof(window.print) != 'undefined') {
        window.print();
    }
}
//]]>
</script>

<p class="print_ignore">
    <input type="button" id="print" value="<?php echo $strPrint; ?>"
        onclick="printPage()" /></p>

<?php
require_once './libraries/footer.inc.php';
?>
