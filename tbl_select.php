<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/relation.lib.php'); // foreign keys
require_once('./libraries/mysql_charsets.lib.php');

if ($cfg['PropertiesIconic'] == true) {
    // We need to copy the value or else the == 'both' check will always return true
    $propicon = (string)$cfg['PropertiesIconic'];

    if ($propicon == 'both') {
        $iconic_spacer = '<div class="nowrap">';
    } else {
        $iconic_spacer = '';
    }

    $titles['Browse']     = $iconic_spacer . '<img width="12" height="13" src="images/button_browse.png" alt="' . $strBrowseForeignValues . '" title="' . $strBrowseForeignValues . '" border="0" />';

    if ($propicon == 'both') {
        $titles['Browse']        .= '&nbsp;' . $strBrowseForeignValues . '</div>';
    }
} else {
    $titles['Browse']        = $strBrowseForeignValues;
}

/**
 * Defines arrays of functions (should possibly be in config.inc.php
 * so it can also be used in tbl_qbe.php)
 *
 * LIKE works also on integers and dates so I added it in numfunctions
 */
$numfunctions   = array('=', '>', '>=', '<', '<=', '!=', 'LIKE', 'NOT LIKE');
$textfunctions  = array('LIKE %...%','LIKE', 'NOT LIKE', '=', '!=', 'REGEXP', 'NOT REGEXP');
$enumfunctions  = array('=', '!=');
$nullfunctions  = array('IS NULL', 'IS NOT NULL');
$unaryfunctions = array(
    'IS NULL'     => 1,
    'IS NOT NULL' => 1);

/**
 * Not selection yet required -> displays the selection form
 */
if (!isset($param) || $param[0] == '') {
    // Gets some core libraries
    require('./tbl_properties_common.php');
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';
    require('./tbl_properties_table_info.php');

    if (!isset($goto)) {
        $goto = $cfg['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields
    $result    = PMA_DBI_query('SHOW' . (PMA_MYSQL_INT_VERSION >= 40100 ? ' FULL' : '') . ' FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . ';', NULL, PMA_DBI_QUERY_STORE);
    $fields_cnt        = PMA_DBI_num_rows($result);
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $fields_list[] = $row['Field'];
        $type          = $row['Type'];
        // reformat mysql query output - staybyte - 9. June 2001
        if (strncasecmp($type, 'set', 3) == 0
            || strncasecmp($type, 'enum', 4) == 0) {
            $type      = str_replace(',', ', ', $type);
        } else {
            $type      = str_replace(array('binary', 'zerofill', 'unsigned'), '', strtolower($type));
        }
        if (empty($type)) {
            $type      = '&nbsp;';
        }
        $fields_null[] = $row['Null'];
        $fields_type[] = $type;
        if (PMA_MYSQL_INT_VERSION >= 40100 && !empty($row['Collation']) && $row['Collation'] != 'NULL') {
            $fields_collation[] = $row['Collation'];
            $tmp_charset = explode('_', $row['Collation']);
            $fields_charset[]   = $tmp_charset[0];
            unset($tmp_charset);
        } else {
            $fields_collation[] = $fields_charset[] = '';
        }
    } // end while
    PMA_DBI_free_result($result);
    unset($result, $type);

    // <markus@noga.de>
    // retrieve keys into foreign fields, if any
    $cfgRelation = PMA_getRelationsParam();
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    //$foreigners  = ($cfgRelation['relwork'] ? PMA_getForeigners($db, $table) : FALSE);
    $foreigners  = PMA_getForeigners($db, $table);
    ?>
<script language="JavaScript" type="text/javascript">
<!--
function PMA_tbl_select_operator(f, index, multiple) {
    switch (f.elements["func[" + index + "]"].options[f.elements["func[" + index + "]"].selectedIndex].value) {
<?php
        reset($unaryfunctions);
        while (list($operator) = each($unaryfunctions)) {
            echo '        case "' . $operator . "\":\r\n";
        }
?>
            bDisabled = true;
            break;

        default:
            bDisabled = false;
    }
    f.elements["fields[" + index + "]" + ((multiple) ? "[]": "")].disabled = bDisabled;
}
// -->
</script>
<form method="post" action="tbl_select.php" name="insertForm">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="back" value="tbl_select.php" />
    <p style="margin-left: 30px;">
    <?php echo $strSelectFields; ?><br />
    <select name="param[]" size="<?php echo min($fields_cnt, 10); ?>" multiple="multiple">
    <?php
    echo "\n";
    // Displays the list of the fields
    for ($i = 0 ; $i < $fields_cnt; $i++) {
        echo '        <option value="' . htmlspecialchars($fields_list[$i]) . '" selected="selected">' . htmlspecialchars($fields_list[$i]) . '</option>' . "\n";
    }
    ?>
    </select><br />
    <input type="checkbox" name="distinct" value="DISTINCT" id="oDistinct" /> <label for="oDistinct">DISTINCT</label></p>
    <ul>
        <li>
            <?php echo $strLimitNumRows . "\n"; ?>
            <input type="text" size="4" name="session_max_rows" value="<?php echo $cfg['MaxRows']; ?>" class="textfield" />
        </li>
        <li>
            <?php echo $strAddSearchConditions; ?><br />
            <input type="text" name="where" class="textfield" />&nbsp;
            <?php echo PMA_showMySQLDocu('Reference', 'Functions') . "\n"; ?>
            <br /><br />
            <?php echo '<i>' . $strOr . '</i> ' . $strDoAQuery; ?><br />
            <table border="<?php echo $cfg['Border']; ?>">
            <tr>
                <th><?php echo $strField; ?></th>
                <th><?php echo $strType; ?></th>
                <?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '<th>' . $strCollation . '</th>' . "\n" : ''; ?>
                <th><?php echo $strOperator; ?></th>
                <th><?php echo $strValue; ?></th>
            </tr>
    <?php
    for ($i = 0; $i < $fields_cnt; $i++) {
        echo "\n";
        $bgcolor   = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        ?>
            <tr>
                <td bgcolor="<?php echo $bgcolor; ?>"><?php echo htmlspecialchars($fields_list[$i]); ?></td>
                <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $fields_type[$i]; ?></td>
                <?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '<td bgcolor="' . $bgcolor . '">' . $fields_collation[$i] . '</td>' . "\n" : ''; ?>
                <td bgcolor="<?php echo $bgcolor; ?>">
                    <select name="func[]">
        <?php
        if (strncasecmp($fields_type[$i], 'enum', 4) == 0) {
            foreach ($enumfunctions as $k => $fc) {
                echo "\n" . '                        '
                     . '<option value="' . htmlspecialchars($fc) . '">' . htmlspecialchars($fc) . '</option>';
            }
        } elseif (preg_match('@char|blob|text|set@i', $fields_type[$i])) {
            foreach ($textfunctions as $k => $fc) {
                echo "\n" . '                        '
                     . '<option value="' . htmlspecialchars($fc) . '">' . htmlspecialchars($fc) . '</option>';
            }
        } else {
            foreach ($numfunctions as $k => $fc) {
                echo "\n" . '                        '
                     . '<option value="' .  htmlspecialchars($fc) . '">' . htmlspecialchars($fc) . '</option>';
            }
        } // end if... else...
        if ($fields_null[$i]) {
            foreach ($nullfunctions as $k => $fc) {
                echo "\n" . '                        '
                     . '<option value="' .  htmlspecialchars($fc) . '">' . htmlspecialchars($fc) . '</option>';
            }
        }
        echo "\n";
        ?>
                    </select>
                </td>
                <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        // <markus@noga.de>
        $field = $fields_list[$i];

        // do not use require_once here
        require('./libraries/get_foreign.lib.php');

        echo "\n";
        // we got a bug report: in some cases, even if $disp is true,
        // there are no rows, so we add a fetch_array

        if ($foreigners && isset($foreigners[$field]) && isset($disp_row) && is_array($disp_row)) {
            // f o r e i g n    k e y s
            echo '                    <select name="fields[' . $i . ']">' . "\n";
            // go back to first row
            echo PMA_foreignDropdown($disp_row, $foreign_field, $foreign_display, $data, 100);
            echo '                    </select>' . "\n";
        } else if (isset($foreign_link) && $foreign_link == true) {
        ?>
            <input type="text"   name="fields[<?php echo $i; ?>]" id="field_<?php echo md5($field); ?>[<?php echo $i; ?>]" class="textfield" />
            <script type="text/javascript" language="javascript">
                document.writeln('<a target="_blank" onclick="window.open(this.href, \'foreigners\', \'width=640,height=240,scrollbars=yes\'); return false" href="browse_foreigners.php?<?php echo PMA_generate_common_url($db, $table); ?>&amp;field=<?php echo urlencode($field); ?>"><?php echo str_replace("'", "\'", $titles['Browse']); ?></a>');
            </script>
        <?php
        } else if (strncasecmp($fields_type[$i], 'enum', 4) == 0) {
            // e n u m s
            $enum_value=explode(', ', str_replace("'", '', substr($fields_type[$i], 5, -1)));
            $cnt_enum_value = count($enum_value);
            echo '                    <select name="fields[' . $i . '][]" multiple="multiple" size="' . min(3, $cnt_enum_value) . '">' . "\n";
            for ($j = 0; $j < $cnt_enum_value; $j++) {
                echo '                        <option value="' . $enum_value[$j] . '">' . $enum_value[$j] . '</option>';
            } // end for
            echo '                    </select>' . "\n";
        } else {
            // o t h e r   c a s e s
            echo '                    <input type="text" name="fields[' . $i . ']" size="40" class="textfield" />' .  "\n";
        }

        ?>
                    <input type="hidden" name="names[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($fields_list[$i]); ?>" />
                    <input type="hidden" name="types[<?php echo $i; ?>]" value="<?php echo $fields_type[$i]; ?>" />
                    <input type="hidden" name="charsets[<?php echo $i; ?>]" value="<?php echo $fields_charset[$i]; ?>" />
                </td>
            </tr>
        <?php
    } // end for
    echo "\n";
    ?>
            </table>
        </li>
        <li>
            <?php echo $strDisplayOrder; ?><br />
            <select name="orderField" style="vertical-align: middle">
                <option value="--nil--"></option>
    <?php
    echo "\n";
    for ($i = 0; $i < $fields_cnt; $i++) {
        echo '                ';
        echo '<option value="' . htmlspecialchars($fields_list[$i]) . '">' . htmlspecialchars($fields_list[$i]) . '</option>' . "\n";
    } // end for
    ?>
            </select>
            <input type="radio" name="order" value="ASC" checked="checked" />
            <?php echo $strAscending; ?>&nbsp;
            <input type="radio" name="order" value="DESC" />
            <?php echo $strDescending; ?>
        </li>
    </ul>

    &nbsp;&nbsp;&nbsp;&nbsp;
    <input type="hidden" name="max_number_of_fields" value="<?php echo $fields_cnt; ?>" />
    <input type="submit" name="submit" value="<?php echo $strGo; ?>" />
</form>
    <?php
    require_once('./footer.inc.php');
}


/**
 * Selection criteria have been submitted -> do the work
 */
else {
    // Builds the query

    $sql_query = 'SELECT ' . (isset($distinct) ? 'DISTINCT ' : '');

    // if all fields were selected to display, we do a SELECT *
    // (more efficient and this helps prevent a problem in IE
    // if one of the rows is edited and we come back to the Select results)

    if (count($param) == $max_number_of_fields) {
        $sql_query .= '* ';
    } else {

        $sql_query .= PMA_backquote(urldecode($param[0]));
        $c         = count($param);
        for ($i = 1; $i < $c; $i++) {
            $sql_query .= ',' . PMA_backquote(urldecode($param[$i]));
        }
    } // end if

    $sql_query .= ' FROM ' . PMA_backquote($table);

    // The where clause
    if (trim($where) != '') {
        $sql_query .= ' WHERE ' . $where;
    } else {
        $w = array();
        $cnt_func = count($func);
        reset($func);
        while (list($i, $func_type) = each($func)) {
            if (@$unaryfunctions[$func_type] == 1) {
                $fields[$i] = '';
                $w[] = PMA_backquote(urldecode($names[$i])) . ' ' . $func_type;

            } elseif (strncasecmp($types[$i], 'enum', 4) == 0) {
                if (!empty($fields[$i])) {
                    if (!is_array($fields[$i])) {
                        $fields[$i] = explode(',', $fields[$i]);
                    }
                    $enum_selected_count = count($fields[$i]);
                    if ($func_type == '=' && $enum_selected_count > 1) {
                        $func_type    = $func[$i] = 'IN';
                        $parens_open  = '(';
                        $parens_close = ')';

                    } elseif ($func_type == '!=' && $enum_selected_count > 1) {
                        $func_type    = $func[$i] = 'NOT IN';
                        $parens_open  = '(';
                        $parens_close = ')';

                    } else {
                        $parens_open  = '';
                        $parens_close = '';
                    }
                    $enum_where = '\'' . PMA_sqlAddslashes($fields[$i][0]) . '\'';
                    for ($e = 1; $e < $enum_selected_count; $e++) {
                        $enum_where .= ', \'' . PMA_sqlAddslashes($fields[$i][$e]) . '\'';
                    }

                    $w[] = PMA_backquote(urldecode($names[$i])) . ' ' . $func_type . ' ' . $parens_open . $enum_where . $parens_close;
                }

            } elseif ($fields[$i] != '') {
                if (preg_match('@char|blob|text|set|date|time|year@i', $types[$i])) {
                    $quot = '\'';
                } else {
                    $quot = '';
                }

                // LIKE %...%
                if ($func_type == 'LIKE %...%') {
                    $func_type = 'LIKE';
                    $fields[$i] = '%' . $fields[$i] . '%';
                }
                $w[] = PMA_backquote(urldecode($names[$i])) . ' ' . $func_type . ' ' . $quot . PMA_sqlAddslashes($fields[$i]) . $quot;

            } // end if
        } // end for

        if ($w) {
            $sql_query .= ' WHERE ' . implode(' AND ', $w);
        }
    } // end if

    if ($orderField != '--nil--') {
        $sql_query .= ' ORDER BY ' . PMA_backquote(urldecode($orderField)) . ' ' . $order;
    } // end if

    include('./sql.php');
}

?>
