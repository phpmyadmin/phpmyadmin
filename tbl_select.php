<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/relation.lib.php'; // foreign keys
require_once './libraries/mysql_charsets.lib.php';

if ($GLOBALS['cfg']['PropertiesIconic'] == true) {
    $titles['Browse'] =
        '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        .'b_browse.png" alt="' . $strBrowseForeignValues . '" title="'
        .$strBrowseForeignValues . '" />';

    if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
        $titles['Browse'] .= $strBrowseForeignValues;
    }
} else {
    $titles['Browse'] = $strBrowseForeignValues;
}

/**
 * Not selection yet required -> displays the selection form
 */
if (!isset($param) || $param[0] == '') {
    // Gets some core libraries
    require_once './libraries/tbl_common.php';
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

    /**
     * Gets tables informations
     */
    require_once './libraries/tbl_info.inc.php';

    /**
     * Displays top menu links
     */
    require_once './libraries/tbl_links.inc.php';

    if (!isset($goto)) {
        $goto = $GLOBALS['cfg']['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields
    $result     = PMA_DBI_query('SHOW' . (PMA_MYSQL_INT_VERSION >= 40100 ? ' FULL' : '') . ' FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . ';', null, PMA_DBI_QUERY_STORE);
    $fields_cnt = PMA_DBI_num_rows($result);
    // rabue: we'd better ensure, that all arrays are empty.
    $fields_list = $fields_null = $fields_type = $fields_collation = array();
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $fields_list[] = $row['Field'];
        $type          = $row['Type'];
        // reformat mysql query output - staybyte - 9. June 2001
        if (strncasecmp($type, 'set', 3) == 0
            || strncasecmp($type, 'enum', 4) == 0) {
            $type = str_replace(',', ', ', $type);
        } else {

            // strip the "BINARY" attribute, except if we find "BINARY(" because
            // this would be a BINARY or VARBINARY field type
            if (!preg_match('@BINARY[\(]@i', $type)) {
                $type = preg_replace('@BINARY@i', '', $type);
            }
            $type = preg_replace('@ZEROFILL@i', '', $type);
            $type = preg_replace('@UNSIGNED@i', '', $type);

            $type = strtolower($type);
        }
        if (empty($type)) {
            $type = '&nbsp;';
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
<script type="text/javascript">
// <![CDATA[
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
// ]]>
</script>
<form method="post" action="tbl_select.php" name="insertForm">
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="back" value="tbl_select.php" />

<fieldset id="fieldset_table_search">

<fieldset id="fieldset_select_fields">
    <legend><?php echo $strSelectFields; ?></legend>
    <select name="param[]" size="<?php echo min($fields_cnt, 10); ?>"
        multiple="multiple">
    <?php
    // Displays the list of the fields
    foreach ($fields_list as $each_field) {
        echo '        '
            .'<option value="' . htmlspecialchars($each_field) . '"'
            .' selected="selected">' . htmlspecialchars($each_field)
            .'</option>' . "\n";
    }
    ?>
    </select>
    <input type="checkbox" name="distinct" value="DISTINCT" id="oDistinct" />
    <label for="oDistinct">DISTINCT</label>
</fieldset>

<fieldset id="fieldset_limit_rows">
    <legend><?php echo $strLimitNumRows; ?></legend>
    <input type="text" size="4" name="session_max_rows"
        value="<?php echo $GLOBALS['cfg']['MaxRows']; ?>" class="textfield" />
</fieldset>

<fieldset id="fieldset_display_order">
    <legend><?php echo $strDisplayOrder; ?></legend>
    <select name="orderField" style="vertical-align: middle">
        <option value="--nil--"></option>
    <?php
    foreach ($fields_list as $each_field) {
        echo '        '
            .'<option value="' . htmlspecialchars($each_field) . '">'
            .htmlspecialchars($each_field) . '</option>' . "\n";
    } // end for
    ?>
    </select>

    <div class="formelement">
        <input type="radio" name="order" value="ASC" checked="checked" id="sortASC" />
        <label for="sortASC"><?php echo $strAscending; ?></label>
    </div>

    <div class="formelement">
        <input type="radio" name="order" value="DESC" id="sortDESC" />
        <label for="sortDESC"><?php echo $strDescending; ?></label>
    </div>
</fieldset>

<br class="clearfloat" />
<?php echo $strAddSearchConditions; ?>
<?php echo PMA_showMySQLDocu('SQL-Syntax', 'Functions'); ?>

<input type="text" name="where" class="textfield" size="64" />

</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit" value="<?php echo $strGo; ?>" />
</fieldset>

<fieldset id="fieldset_table_qbe">
    <legend><?php echo '<em>' . $strOr . '</em> ' . $strDoAQuery; ?></legend>
    <table class="data">
    <thead>
    <tr><th><?php echo $strField; ?></th>
        <th><?php echo $strType; ?></th>
        <?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '<th>' . $strCollation . '</th>' . "\n" : ''; ?>
        <th><?php echo $strOperator; ?></th>
        <th><?php echo $strValue; ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $odd_row = true;
?>
<script type="text/javascript" src="./js/tbl_change.js"></script>
<?php
    for ($i = 0; $i < $fields_cnt; $i++) {
        ?>
        <tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
            <th><?php echo htmlspecialchars($fields_list[$i]); ?></th>
            <td><?php echo $fields_type[$i]; ?></td>
            <?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '<td>'
                . $fields_collation[$i] . '</td>' . "\n" : ''; ?>
            <td><select name="func[]">
        <?php
        if (strncasecmp($fields_type[$i], 'enum', 4) == 0) {
            foreach ($GLOBALS['cfg']['EnumOperators'] as $fc) {
                echo "\n" . '                        '
                   . '<option value="' . htmlspecialchars($fc) . '">'
                   . htmlspecialchars($fc) . '</option>';
            }
        } elseif (preg_match('@char|blob|text|set@i', $fields_type[$i])) {
            foreach ($GLOBALS['cfg']['TextOperators'] as $fc) {
            echo "\n" . '                        '
               . '<option value="' . htmlspecialchars($fc) . '">'
               . htmlspecialchars($fc) . '</option>';
            }
        } else {
            foreach ($GLOBALS['cfg']['NumOperators'] as $fc) {
                echo "\n" . '                        '
                   . '<option value="' .  htmlspecialchars($fc) . '">'
                   . htmlspecialchars($fc) . '</option>';
            }
        } // end if... else...
        if ($fields_null[$i]) {
            foreach ($GLOBALS['cfg']['NullOperators'] as $fc) {
                echo "\n" . '                        '
                   . '<option value="' .  htmlspecialchars($fc) . '">'
                   . htmlspecialchars($fc) . '</option>';
            }
        }
        ?>

                </select>
            </td>
            <td>
        <?php
        // <markus@noga.de>
        $field = $fields_list[$i];

        // do not use require_once here
        require './libraries/get_foreign.lib.php';

        // we got a bug report: in some cases, even if $disp is true,
        // there are no rows, so we add a fetch_array

        if ($foreigners && isset($foreigners[$field]) && isset($disp_row) && is_array($disp_row)) {
            // f o r e i g n    k e y s
            echo '            <select name="fields[' . $i . ']">' . "\n";
            // go back to first row

            // here, the 4th parameter is empty because there is no current
            // value of data for the dropdown (the search page initial values
            // are displayed empty)
            echo PMA_foreignDropdown($disp_row, $foreign_field, $foreign_display,
                '', $GLOBALS['cfg']['ForeignKeyMaxLimit']);
            echo '            </select>' . "\n";
        } elseif (isset($foreign_link) && $foreign_link == true) {
            ?>
            <input type="text" name="fields[<?php echo $i; ?>]"
                id="field_<?php echo md5($field); ?>[<?php echo $i; ?>]"
                class="textfield" />
            <script type="text/javascript">
            // <![CDATA[
                document.writeln('<a target="_blank" onclick="window.open(this.href, \'foreigners\', \'width=640,height=240,scrollbars=yes\'); return false" href="browse_foreigners.php?<?php echo PMA_generate_common_url($db, $table); ?>&amp;field=<?php echo urlencode($field); ?>&amp;fieldkey=<?php echo $i; ?>"><?php echo str_replace("'", "\'", $titles['Browse']); ?></a>');
            // ]]>
            </script>
            <?php
        } elseif (strncasecmp($fields_type[$i], 'enum', 4) == 0) {
            // e n u m s
            $enum_value=explode(', ', str_replace("'", '', substr($fields_type[$i], 5, -1)));
            $cnt_enum_value = count($enum_value);
            echo '            <select name="fields[' . $i . '][]"'
                .' multiple="multiple" size="' . min(3, $cnt_enum_value) . '">' . "\n";
            for ($j = 0; $j < $cnt_enum_value; $j++) {
                echo '                <option value="' . $enum_value[$j] . '">'
                    . $enum_value[$j] . '</option>';
            } // end for
            echo '            </select>' . "\n";
        } else {
            // o t h e r   c a s e s
            echo '            <input type="text" name="fields[' . $i . ']"'
                .' size="40" class="textfield" id="field_' . $i . '" />' .  "\n";
        };
        $type = $fields_type[$i];
        if ($type == 'date' || $type == 'datetime' || substr($type, 0, 9) == 'timestamp') {
        ?>
                    <script type="text/javascript">
                    //<![CDATA[
                    document.write('<a title="<?php echo $strCalendar;?>" href="javascript:openCalendar(\'<?php echo PMA_generate_common_url();?>\', \'insertForm\', \'field_<?php echo ($i); ?>\', \'<?php echo (PMA_MYSQL_INT_VERSION >= 40100 && substr($type, 0, 9) == 'timestamp') ? 'datetime' : substr($type, 0, 9); ?>\')"><img class="calendar" src="<?php echo $pmaThemeImage; ?>b_calendar.png" alt="<?php echo $strCalendar; ?>"/></a>');
                    //]]>
                    </script>
        <?php
        }
        ?>
            <input type="hidden" name="names[<?php echo $i; ?>]"
                value="<?php echo htmlspecialchars($fields_list[$i]); ?>" />
            <input type="hidden" name="types[<?php echo $i; ?>]"
                value="<?php echo $fields_type[$i]; ?>" />
            <input type="hidden" name="collations[<?php echo $i; ?>]"
                value="<?php echo $fields_collation[$i]; ?>" />
        </td>
    </tr>
        <?php
    } // end for
    ?>
    </tbody>
    </table>
</fieldset>
<fieldset class="tblFooters">
    <input type="hidden" name="max_number_of_fields"
        value="<?php echo $fields_cnt; ?>" />
    <input type="submit" name="submit" value="<?php echo $strGo; ?>" />
</fieldset>
</form>
    <?php
    require_once './libraries/footer.inc.php';
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
        $param = PMA_backquote($param);
        $sql_query .= implode(', ', $param);
        unset($param);
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
                // For these types we quote the value. Even if it's another type (like INT),
                // for a LIKE we always quote the value. MySQL converts strings to numbers
                // and numbers to strings as necessary during the comparison
                if (preg_match('@char|binary|blob|text|set|date|time|year@i', $types[$i]) || strpos(' ' . $func_type, 'LIKE')) {
                    $quot = '\'';
                } else {
                    $quot = '';
                }

                // Make query independant from the selected connection charset.
                // But if the field's type is VARBINARY, it has no charset
                // and $charsets[$i] is empty, so we cannot generate a CONVERT

                if (PMA_MYSQL_INT_VERSION >= 40101 && !empty($charsets[$i]) && $charsets[$i] != $charset_connection && preg_match('@char|binary|blob|text|set@i', $types[$i])) {
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
    include './sql.php';
}

?>
