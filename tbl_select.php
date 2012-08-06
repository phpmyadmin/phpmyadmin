<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table search tab
 *
 * display table search form, create SQL query from form data
 * and include sql.php to execute it
 *
 * @todo display search form again if no results from previous search
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/tbl_select.lib.php';

$GLOBALS['js_include'][] = 'makegrid.js';
$GLOBALS['js_include'][] = 'sql.js';
$GLOBALS['js_include'][] = 'tbl_select.js';
$GLOBALS['js_include'][] = 'tbl_change.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'gis_data_editor.js';
$GLOBALS['js_include'][] = 'codemirror/lib/codemirror.js';
$GLOBALS['js_include'][] = 'codemirror/mode/mysql/mysql.js';

$titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));

$geom_types = PMA_getGISDatatypes();
/**
 * Not selection yet required -> displays the selection form
 */
if ((! isset($param) || $param[0] == '') && ! isset($displayAllColumns)) {
    // Gets some core libraries
    include_once './libraries/tbl_common.php';
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

    /**
     * Gets tables informations
     */
    include_once './libraries/tbl_info.inc.php';

    /**
     * Displays top menu links
     */
    include_once './libraries/tbl_links.inc.php';

    if (! isset($goto)) {
        $goto = $GLOBALS['cfg']['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields
    list($fields_list, $fields_type, $fields_collation, $fields_null, $geom_column_present) = PMA_tbl_getFields($db, $table);
    $fields_cnt = count($fields_list);

    // retrieve keys into foreign fields, if any
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    $foreigners = PMA_getForeigners($db, $table);
    ?>

<fieldset id="fieldset_subtab">
<?php
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;

echo PMA_generate_html_tabs(PMA_tbl_getSubTabs(), $url_params, '', 'topmenu2');

?>

        <form method="post" action="tbl_select.php" name="insertForm" id="tbl_search_form" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?>>
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="back" value="tbl_select.php" />

<fieldset id="fieldset_table_search">

<fieldset id="fieldset_table_qbe">
    <legend><?php echo __('Do a "query by example" (wildcard: "%")') ?></legend>
    <table class="data">
    <?php echo PMA_tbl_setTableHeader($geom_column_present); ?>
    <tbody>
    <?php
    $odd_row = true;

    for ($i = 0; $i < $fields_cnt; $i++) {
        ?>
        <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
            <?php
            // if 'Function' column is present
            if ($geom_column_present) {
                echo('<td>');
                // if a geometry column
                if (in_array($fields_type[$i], $geom_types)) {
                    echo('<select class="geom_func" name="geom_func['. $i .']">');
                        // get the relevant list of functions
                        $funcs = PMA_getGISFunctions($fields_type[$i], true, true);
                        foreach ($funcs as $func_name => $func) {
                            $name =  isset($func['display']) ? $func['display'] : $func_name;
                            echo('<option value="' . htmlspecialchars($name) . '">'
                                . htmlspecialchars($name) . '</option>');
                        }
                    echo('</select>');
                } else {
                    echo('&nbsp;');
                }
                echo('</td>');
            }
            ?>
            <th><?php echo htmlspecialchars($fields_list[$i]); ?></th>
            <td><?php echo htmlspecialchars($fields_type[$i]); ?></td>
            <td><?php echo $fields_collation[$i]; ?></td>
            <td><select name="func[<?php echo $i; ?>]">
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
        $field = $fields_list[$i];

        $foreignData = PMA_getForeignData($foreigners, $field, false, '', '');

        echo PMA_getForeignFields_Values($foreigners, $foreignData, $field, $fields_type, $i, $db, $table, $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], '', true);

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
<div id="gis_editor"></div><div id="popup_background"></div>
</fieldset>
<?php
    PMA_generate_slider_effect('searchoptions', __('Options'));
?>
<fieldset id="fieldset_select_fields">
    <legend><?php echo __('Select columns (at least one):'); ?></legend>
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

<fieldset id="fieldset_search_conditions">
    <legend><?php echo '<em>' . __('Or') . '</em> ' . __('Add search conditions (body of the "where" clause):'); ?></legend>
<?php echo PMA_showMySQLDocu('SQL-Syntax', 'Functions'); ?>

<input type="text" name="where" class="textfield" size="64" />
</fieldset>

<fieldset id="fieldset_limit_rows">
    <legend><?php echo __('Number of rows per page'); ?></legend>
    <input type="text" size="4" name="session_max_rows"
        value="<?php echo $GLOBALS['cfg']['MaxRows']; ?>" class="textfield" />
</fieldset>

<fieldset id="fieldset_display_order">
    <legend><?php echo __('Display order:'); ?></legend>
    <select name="orderField">
        <option value="--nil--"></option>
    <?php
    foreach ($fields_list as $each_field) {
        echo '        '
            .'<option value="' . htmlspecialchars($each_field) . '">'
            .htmlspecialchars($each_field) . '</option>' . "\n";
    } // end for
    ?>
    </select>
<?php
    $choices = array(
        'ASC'  => __('Ascending'),
        'DESC' => __('Descending')
    );
    PMA_display_html_radio('order', $choices, 'ASC', false, true, "formelement");
    unset($choices);
?>
</fieldset>
<br style="clear: both;"/>
</div>
</fieldset>
<fieldset class="tblFooters">
    <input type="hidden" name="max_number_of_fields"
        value="<?php echo $fields_cnt; ?>" />
    <input type="submit" name="submit" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>
<div id="sqlqueryresults"></div>
    <?php
    include './libraries/footer.inc.php';
?>

</fieldset>

<?php
} else {
    /**
     * Selection criteria have been submitted -> do the work
     */

    // Builds the query

    $sql_query = 'SELECT ' . (isset($distinct) ? 'DISTINCT ' : '');

    // if all fields were selected to display, we do a SELECT *
    // (more efficient and this helps prevent a problem in IE
    // if one of the rows is edited and we come back to the Select results)

    if (! empty($displayAllColumns) || count($param) == $max_number_of_fields) {
        $sql_query .= '* ';
    } else {
        $param = PMA_backquote($param);
        $sql_query .= implode(', ', $param);
    } // end if

    // avoid a loop, for example when $cfg['DefaultTabTable'] is set
    // to 'tbl_select.php'
    unset($param);

    $sql_query .= ' FROM ' . PMA_backquote($table);

    // The where clause
    if (trim($where) != '') {
        $sql_query .= ' WHERE ' . $where;
    } else {
        $w = $charsets = array();
        $cnt_func = count($func);
        reset($func);
        while (list($i, $func_type) = each($func)) {

            list($charsets[$i]) = explode('_', $collations[$i]);
            $unaryFlag =  (isset($GLOBALS['cfg']['UnaryOperators'][$func_type]) && $GLOBALS['cfg']['UnaryOperators'][$func_type] == 1) ? true : false;

            $tmp_geom_func = isset($geom_func[$i]) ? $geom_func[$i] : null;
            $whereClause = PMA_tbl_search_getWhereClause($fields[$i], $names[$i], $types[$i], $collations[$i], $func_type, $unaryFlag, $tmp_geom_func);

            if($whereClause)
                $w[] = $whereClause;
            } // end for
        if ($w) {
            $sql_query .= ' WHERE ' . implode(' AND ', $w);
        }
    } // end if

    if ($orderField != '--nil--') {
        $sql_query .= ' ORDER BY ' . PMA_backquote($orderField) . ' ' . $order;
    } // end if
    include './sql.php';
}

?>
