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

    $titles['Browse']     = $iconic_spacer . '<img width="16" height="16" src="' . $pmaThemeImage . 'b_browse.png" alt="' . $strBrowseForeignValues . '" title="' . $strBrowseForeignValues . '" border="0" />';

    if ($propicon == 'both') {
        $titles['Browse']        .= '&nbsp;' . $strBrowseForeignValues . '</div>';
    }
} else {
    $titles['Browse']        = $strBrowseForeignValues;
}

/**
 * Not selection yet required -> displays the selection form
 */
if (!isset($param) || $param[0] == '') {
    // Gets some core libraries
    require('./tbl_properties_common.php');
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

    /**
     * Gets tables informations
     */
    require('./tbl_properties_table_info.php');

    /**
     * Displays top menu links
     */
    require('./tbl_properties_links.php');

    if (!isset($goto)) {
        $goto = $cfg['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields
    $result    = PMA_DBI_query('SHOW' . (PMA_MYSQL_INT_VERSION >= 40100 ? ' FULL' : '') . ' FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . ';', NULL, PMA_DBI_QUERY_STORE);
    $fields_cnt        = PMA_DBI_num_rows($result);
    // rabue: we'd better ensure, that all arrays are empty.
    $fields_list = $fields_null = $fields_type = $fields_collation = array();
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
        $fields_collation[] = PMA_MYSQL_INT_VERSION >= 40100 && !empty($row['Collation']) && $row['Collation'] != 'NULL'
                          ? $row['Collation']
                          : '';
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
        reset($GLOBALS['cfg']['UnaryOperators']);
        while (list($operator) = each($GLOBALS['cfg']['UnaryOperators'])) {
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
    <table border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td valign="top">
                <table border="0" cellpadding="3" cellspacing="0">
                    <tr>
                        <th align="left" colspan="2">
                            <?php echo $strSelectFields; ?>
                        </th>
                    </tr>
                    <tr>
                        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                            <select name="param[]" size="<?php echo min($fields_cnt, 10); ?>" multiple="multiple" align="left">
    <?php
    echo "\n";
    // Displays the list of the fields
    for ($i = 0 ; $i < $fields_cnt; $i++) {
        echo '                            '
            . '<option value="' . htmlspecialchars($fields_list[$i]) . '" selected="selected">' . htmlspecialchars($fields_list[$i]) . '</option>' . "\n";
    }
    ?>
                            </select>
                        </td>
                        <td valign="bottom" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                            <input type="checkbox" name="distinct" value="DISTINCT" id="oDistinct" /><label for="oDistinct">DISTINCT</label>
                        </td>
                    </tr>
                </table>
            </td>
            <td nowrap="nowrap" width="50">&nbsp;</td>
            <td valign="top">
                <table border="0" cellpadding="3" cellspacing="0">
                    <tr>
                        <th align="left">
                            <?php echo $strLimitNumRows . "\n"; ?>:
                        </th>
                    </tr>
                    <tr>
                        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                            <input type="text" size="4" name="session_max_rows" value="<?php echo $cfg['MaxRows']; ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <th align="left">
                            <?php echo $strDisplayOrder; ?>
                        </th>
                    </tr>
                    <tr>
                        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                            <select name="orderField" style="vertical-align: middle">
                                <option value="--nil--"></option>
            <?php
    echo "\n";
    for ($i = 0; $i < $fields_cnt; $i++) {
        echo '                                ';
        echo '<option value="' . htmlspecialchars($fields_list[$i]) . '">' . htmlspecialchars($fields_list[$i]) . '</option>' . "\n";
    } // end for
    ?>
                            </select><br />
                            <input type="radio" name="order" value="ASC" checked="checked" id="sortASC" /><label for="sortASC"><?php echo $strAscending; ?></label><br />
                            <input type="radio" name="order" value="DESC" id="sortDESC" /><label for="sortDESC"><?php echo $strDescending; ?></label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table border="0" cellpadding="3" cellspacing="0">
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <th align="left" class="tblHeaders" colspan="2">
                <?php echo $strAddSearchConditions; ?>
                <?php echo PMA_showMySQLDocu('Reference', 'Functions') . "\n"; ?>
            </th>
        </tr>
        <tr>
            <td>
                <input type="text" name="where" class="textfield" size="64" />
            </td>
            <td align="right">
                <input type="submit" name="submit" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <th align="left" class="tblHeaders" colspan="2">
                <?php echo '<i>' . $strOr . '</i> ' . $strDoAQuery; ?>
            </th>
        </tr>
        <tr>
            <td colspan="2">
            <table border="<?php echo $cfg['Border']; ?>" cellpadding="2" cellspacing="1">
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
                <td bgcolor="<?php echo $bgcolor; ?>"><b><?php echo htmlspecialchars($fields_list[$i]); ?></b></td>
                <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $fields_type[$i]; ?></td>
                <?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '<td bgcolor="' . $bgcolor . '">' . $fields_collation[$i] . '</td>' . "\n" : ''; ?>
                 <td bgcolor="<?php echo $bgcolor; ?>">
                    <select name="func[]">
        <?php
        if (strncasecmp($fields_type[$i], 'enum', 4) == 0) {
            foreach ($GLOBALS['cfg']['EnumOperators'] as $k => $fc) {
                echo "\n" . '                        '
                   . '<option value="' . htmlspecialchars($fc) . '">' . htmlspecialchars($fc) . '</option>';
            }
        } elseif (preg_match('@char|blob|text|set@i', $fields_type[$i])) {
            foreach ($GLOBALS['cfg']['TextOperators'] as $k => $fc) {
            echo "\n" . '                        '
               . '<option value="' . htmlspecialchars($fc) . '">' . htmlspecialchars($fc) . '</option>';
            }
        } else {
            foreach ($GLOBALS['cfg']['NumOperators'] as $k => $fc) {
                echo "\n" . '                        '
                   . '<option value="' .  htmlspecialchars($fc) . '">' . htmlspecialchars($fc) . '</option>';
            }
        } // end if... else...
        if ($fields_null[$i]) {
            foreach ($GLOBALS['cfg']['NullOperators'] as $k => $fc) {
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

            // here, the 4th parameter is empty because there is no current
            // value of data for the dropdown (the search page initial values
            // are displayed empty)
            echo PMA_foreignDropdown($disp_row, $foreign_field, $foreign_display, '', 100);
            echo '                    </select>' . "\n";
        } else if (isset($foreign_link) && $foreign_link == true) {
        ?>
            <input type="text"   name="fields[<?php echo $i; ?>]" id="field_<?php echo md5($field); ?>[<?php echo $i; ?>]" class="textfield" />
            <script type="text/javascript" language="javascript">
                document.writeln('<a target="_blank" onclick="window.open(this.href, \'foreigners\', \'width=640,height=240,scrollbars=yes\'); return false" href="browse_foreigners.php?<?php echo PMA_generate_common_url($db, $table); ?>&amp;field=<?php echo urlencode($field); ?>&amp;fieldkey=<?php echo $i; ?>"><?php echo str_replace("'", "\'", $titles['Browse']); ?></a>');
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
                    <input type="hidden" name="collations[<?php echo $i; ?>]" value="<?php echo $fields_collation[$i]; ?>" />
                </td>
            </tr>
        <?php
    } // end for
    echo "\n";
    ?>
            </table>
            </td>
        </tr>
        <tr>
            <td nowrap="nowrap" colspan="2" align="right">
                <input type="hidden" name="max_number_of_fields" value="<?php echo $fields_cnt; ?>" />
                <input type="submit" name="submit" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
    </table>
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
        $w = $charsets = array();
        $cnt_func = count($func);
        reset($func);
        while (list($i, $func_type) = each($func)) {
            if (PMA_MYSQL_INT_VERSION >= 40100) {
                list($charsets[$i]) = explode('_', $collations[$i]);
            }
            if (@$GLOBALS['cfg']['UnaryOperators'][$func_type] == 1) {
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
                    if (PMA_MYSQL_INT_VERSION >= 40100 && $charsets[$i] != $charset_connection) {
                        $enum_where = 'CONVERT(_utf8 ' . $enum_where . ' USING ' . $charsets[$i] . ') COLLATE ' . $collations[$i];
                    }
                    for ($e = 1; $e < $enum_selected_count; $e++) {
                        $enum_where .= ', ';
                        $tmp_literal = '\'' . PMA_sqlAddslashes($fields[$i][$e]) . '\'';
                        if (PMA_MYSQL_INT_VERSION >= 40100 && $charsets[$i] != $charset_connection) {
                            $tmp_literal = 'CONVERT(_utf8 ' . $tmp_literal . ' USING ' . $charsets[$i] . ') COLLATE ' . $collations[$i];
                        }
                        $enum_where .= $tmp_literal;
                        unset($tmp_literal);
                    }

                    $w[] = PMA_backquote(urldecode($names[$i])) . ' ' . $func_type . ' ' . $parens_open . $enum_where . $parens_close;
                }

            } elseif ($fields[$i] != '') {
                if (preg_match('@char|binary|blob|text|set|date|time|year@i', $types[$i])) {
                    $quot = '\'';
                } else {
                    $quot = '';
                }

                // Make query independant from the selected connection charset.
                if (PMA_MYSQL_INT_VERSION >= 40101 && $charsets[$i] != $charset_connection && preg_match('@char|binary|blob|text|set@i', $types[$i])) {
                    $prefix = 'CONVERT(_utf8 ';
                    $suffix = ' USING ' . $charsets[$i] . ') COLLATE ' . $collations[$i];
                } else {
                    $prefix = $suffix = '';
                }

                // LIKE %...%
                if ($func_type == 'LIKE %...%') {
                    $func_type = 'LIKE';
                    $fields[$i] = '%' . $fields[$i] . '%';
                }
                $w[] = PMA_backquote(urldecode($names[$i])) . ' ' . $func_type . ' ' . $prefix . $quot . PMA_sqlAddslashes($fields[$i]) . $quot . $suffix;

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
