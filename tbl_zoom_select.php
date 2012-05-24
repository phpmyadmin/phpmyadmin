<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table zoom search tab
 *
 * display table zoom search form, create SQL queries from form data
 *
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/tbl_select.lib.php';
require_once './libraries/relation.lib.php';
require_once './libraries/tbl_info.inc.php';

$GLOBALS['js_include'][] = 'makegrid.js';
$GLOBALS['js_include'][] = 'sql.js';
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'date.js';
/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $GLOBALS['js_include'][] = 'canvg/flashcanvas.js';
}

$GLOBALS['js_include'][] = 'jqplot/jquery.jqplot.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.canvasTextRenderer.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.canvasAxisLabelRenderer.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.dateAxisRenderer.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.highlighter.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.cursor.js';
$GLOBALS['js_include'][] = 'canvg/canvg.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'tbl_zoom_plot_jqplot.js';


/**
 * Handle AJAX request for data row on point select
 * @var post_params Object containing parameters for the POST request
 */

if (isset($_REQUEST['get_data_row']) && $_REQUEST['get_data_row'] == true) {
    $extra_data = array();
    $row_info_query = 'SELECT * FROM `' . $_REQUEST['db'] . '`.`'
        . $_REQUEST['table'] . '` WHERE ' .  $_REQUEST['where_clause'];
    $result = PMA_DBI_query($row_info_query . ";", null, PMA_DBI_QUERY_STORE);
    $fields_meta = PMA_DBI_get_fields_meta($result);
    while ($row = PMA_DBI_fetch_assoc($result)) {
        // for bit fields we need to convert them to printable form
        $i = 0;
        foreach ($row as $col => $val) {
            if ($fields_meta[$i]->type == 'bit') {
                $row[$col] = PMA_printable_bit_value($val, $fields_meta[$i]->length);
            }
            $i++;
        }
        $extra_data['row_info'] = $row;
    }
    PMA_ajaxResponse(null, true, $extra_data);
}

$titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));
/**
 * Not selection yet required -> displays the selection form
 */

// Gets some core libraries
require_once './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

/**
 * Gets tables informations
 */
require_once './libraries/tbl_info.inc.php';

/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';

if (! isset($goto)) {
    $goto = $GLOBALS['cfg']['DefaultTabTable'];
}
// Defines the url to return to in case of error in the next sql statement
$err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

// Gets the list and number of fields

list($fields_list, $fields_type, $fields_collation, $fields_null) = PMA_tbl_getFields($db, $table);
$fields_cnt = count($fields_list);

// retrieve keys into foreign fields, if any
// check also foreigners even if relwork is FALSE (to get
// foreign keys from innodb)
$foreigners = PMA_getForeigners($db, $table);
$flag = 1;
$tbl_fields_type = $tbl_fields_collation = $tbl_fields_null = array();
if (! isset($zoom_submit) && ! isset($inputs)) {
    $dataLabel = PMA_getDisplayField($db, $table);
}
?>
<div id="sqlqueryresults"></div>
<fieldset id="fieldset_subtab">
<?php
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;
echo PMA_generate_html_tabs(PMA_tbl_getSubTabs(), $url_params, '', 'topmenu2');

/**
 *  Set the field name,type,collation and whether null on select of a coulmn
 */
if (isset($inputs) && ($inputs[0] != 'pma_null' || $inputs[1] != 'pma_null')) {
    $flag = 2;
    for ($i = 0 ; $i < 4 ; $i++) {
        if ($inputs[$i] != 'pma_null') {
            $key = array_search($inputs[$i], $fields_list);
            $tbl_fields_type[$i] = $fields_type[$key];
            $tbl_fields_collation[$i] = $fields_collation[$key];
            $tbl_fields_null[$i] = $fields_null[$key];
        }
    }
}

/*
 * Form for input criteria
 */

?>
<form method="post" action="tbl_zoom_select.php" name="insertForm" id="zoom_search_form"
    <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?>>
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="back" value="tbl_zoom_select.php" />
<input type="hidden" name="flag" id="id_flag" value="<?php echo $flag; ?>" />

<fieldset id="inputSection">

<legend><?php echo __('Do a "query by example" (wildcard: "%") for two different columns') ?></legend>
<table class="data">
<?php echo PMA_tbl_setTableHeader();?>
<tbody>
<?php
    $odd_row = true;

for ($i = 0; $i < 4; $i++) {
    if ($i == 2) {
        echo "<tr><td>";
        echo __("Additional search criteria");
        echo "</td></tr>";
    }
    ?>
    <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
        <th><select name="inputs[]" id="<?php echo 'tableid_' . $i; ?>" >
        <option value="<?php echo 'pma_null'; ?>"><?php echo __('None');  ?></option>
    <?php
    for ($j = 0 ; $j < $fields_cnt ; $j++) {
        if (isset($inputs[$i]) && $inputs[$i] == htmlspecialchars($fields_list[$j])) {?>
            <option value="<?php echo htmlspecialchars($fields_list[$j]);?>" selected="selected">
                <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
        } else { ?>
            <option value="<?php echo htmlspecialchars($fields_list[$j]);?>">
                <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
        }
    } ?>
        </select></th>
        <td><?php if (isset($tbl_fields_type[$i])) echo $tbl_fields_type[$i]; ?></td>
        <td><?php if (isset($tbl_fields_collation[$i])) echo $tbl_fields_collation[$i]; ?></td>
        <td>
    <?php
    if (isset($inputs) && $inputs[$i] != 'pma_null') { ?>
        <select name="zoomFunc[]">
        <?php
        if (strncasecmp($tbl_fields_type[$i], 'enum', 4) == 0) {
            foreach ($GLOBALS['cfg']['EnumOperators'] as $fc) {
                if (isset($zoomFunc[$i]) && $zoomFunc[$i] == htmlspecialchars($fc)) {
                    echo "\n" . '                        '
                    . '<option value="' . htmlspecialchars($fc) . '" selected="selected">'
                    . htmlspecialchars($fc) . '</option>';
                } else {
                    echo "\n" . '                        '
                    . '<option value="' . htmlspecialchars($fc) . '">'
                    . htmlspecialchars($fc) . '</option>';
                }
            }
        } elseif (preg_match('@char|blob|text|set@i', $tbl_fields_type[$i])) {
            foreach ($GLOBALS['cfg']['TextOperators'] as $fc) {
                if (isset($zoomFunc[$i]) && $zoomFunc[$i] == $fc) {
                    echo "\n" . '                        '
                    . '<option value="' . htmlspecialchars($fc) . '" selected="selected">'
                    . htmlspecialchars($fc) . '</option>';
                } else {
                    echo "\n" . '                        '
                    . '<option value="' . htmlspecialchars($fc) . '">'
                    . htmlspecialchars($fc) . '</option>';
                }
            }
        } else {
            foreach ($GLOBALS['cfg']['NumOperators'] as $fc) {
                if (isset($zoomFunc[$i]) && $zoomFunc[$i] == $fc) {
                    echo "\n" . '                        '
                    . '<option value="' .  htmlspecialchars($fc) . '" selected="selected">'
                    . htmlspecialchars($fc) . '</option>';
                } else {
                    echo "\n" . '                        '
                    . '<option value="' .  htmlspecialchars($fc) . '">'
                    . htmlspecialchars($fc) . '</option>';
                }
            }
        } // end if... else...

        if ($tbl_fields_null[$i]) {
            foreach ($GLOBALS['cfg']['NullOperators'] as $fc) {
                if (isset($zoomFunc[$i]) && $zoomFunc[$i] == $fc) {
                    echo "\n" . '                        '
                    . '<option value="' .  htmlspecialchars($fc) . '" selected="selected">'
                    . htmlspecialchars($fc) . '</option>';
                } else {
                    echo "\n" . '                        '
                    . '<option value="' .  htmlspecialchars($fc) . '">'
                    . htmlspecialchars($fc) . '</option>';
                }
            }
        }
        ?>
        </select>
        </td>
        <td>
        <?php
        $field = $inputs[$i];

        $foreignData = PMA_getForeignData($foreigners, $field, false, '', '');
        if (isset($fields)) {
            echo PMA_getForeignFields_Values(
                $foreigners, $foreignData, $field, $tbl_fields_type, $i, $db,
                $table, $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], $fields
            );
        } else {
            echo PMA_getForeignFields_Values(
                $foreigners, $foreignData, $field, $tbl_fields_type, $i, $db,
                $table, $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], ''
            );
        }
    } else { ?>

        </td><td></td>

        <?php
    } ?>

    </tr>
    <tr><td>
      <input type="hidden" name="types[<?php echo $i; ?>]" id="types_<?php echo $i; ?>"
        value="<?php if(isset($tbl_fields_type[$i]))echo $tbl_fields_type[$i]; ?>" />
      <input type="hidden" name="collations[<?php echo $i; ?>]"
        value="<?php if(isset($tbl_fields_collation[$i]))echo $tbl_fields_collation[$i]; ?>" />
    </td></tr>

    <?php
}//end for
?>
    </tbody>
    </table>

<?php
/*
 * Other inputs like data label and mode go after selection of column criteria
 */

//Set default datalabel if not selected
if (isset($zoom_submit) && $inputs[0] != 'pma_null' && $inputs[1] != 'pma_null') {
    if ($dataLabel == '') {
        $dataLabel = PMA_getDisplayField($db, $table);
    }
}
?>
    <table class="data">
    <tr><td><label for="dataLabel"><?php echo __("Use this column to label each point"); ?></label></td>
    <td><select name="dataLabel" id='dataLabel' >
        <option value = ''> <?php echo __('None');  ?> </option>
<?php
for ($j = 0; $j < $fields_cnt; $j++) {
    if (isset($dataLabel) && $dataLabel == htmlspecialchars($fields_list[$j])) {
        ?>
        <option value="<?php echo htmlspecialchars($fields_list[$j]);?>" selected="selected">
            <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
    } else {
        ?>
        <option value="<?php echo htmlspecialchars($fields_list[$j]);?>" >
            <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
    }
}
?>
    </select>
    </td></tr>
    <tr><td><label for="maxRowPlotLimit"><?php echo __("Maximum rows to plot"); ?></label></td>
    <td>
<?php
echo '<input type="text" name="maxPlotLimit" id="maxRowPlotLimit" value="';
if (! empty($maxPlotLimit)) {
    echo htmlspecialchars($maxPlotLimit);
} else {
    echo $GLOBALS['cfg']['maxRowPlotLimit'];
}
echo '" /></td></tr>';
?>
    </table>

</fieldset>
<fieldset class="tblFooters">
    <input type="hidden" name="max_number_of_fields"
        value="<?php echo $fields_cnt; ?>" />
    <input type="submit" name="zoom_submit" id="inputFormSubmitId" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>
</fieldset>

<?php

/*
 * Handle the input criteria and generate the query result
 * Form for displaying query results
 */
if (isset($zoom_submit) && $inputs[0] != 'pma_null' && $inputs[1] != 'pma_null' && $inputs[0] != $inputs[1]) {

    /*
     * Query generation part
     */
    $w = $data = array();
    $sql_query = 'SELECT *';

    //Add the table
    $sql_query .= ' FROM ' . PMA_backquote($table);
    for ($i = 0; $i < 4; $i++) {
        if ($inputs[$i] == 'pma_null') {
            continue;
        }
        $tmp = array();
        // The where clause
        $charsets = array();
        $cnt_func = count($zoomFunc[$i]);
        $func_type = $zoomFunc[$i];
        list($charsets[$i]) = explode('_', $collations[$i]);
        $unaryFlag = (isset($GLOBALS['cfg']['UnaryOperators'][$func_type])
                      && $GLOBALS['cfg']['UnaryOperators'][$func_type] == 1)
                      ? true
                      : false;
        $whereClause = PMA_tbl_search_getWhereClause(
            $fields[$i], $inputs[$i], $types[$i],
            $collations[$i], $func_type, $unaryFlag
        );
        if ($whereClause) {
            $w[] = $whereClause;
        }
    } // end for
    if ($w) {
        $sql_query .= ' WHERE ' . implode(' AND ', $w);
    }
    $sql_query .= ' LIMIT ' . $maxPlotLimit;

    /*
     * Query execution part
     */
    $result = PMA_DBI_query($sql_query . ";", null, PMA_DBI_QUERY_STORE);
    $fields_meta = PMA_DBI_get_fields_meta($result);
    while ($row = PMA_DBI_fetch_assoc($result)) {
        //Need a row with indexes as 0,1,2 for the PMA_getUniqueCondition hence using a temporary array
        $tmpRow = array();
        foreach ($row as $val) {
            $tmpRow[] = $val;
        }
        //Get unique conditon on each row (will be needed for row update)
        $uniqueCondition = PMA_getUniqueCondition($result, $fields_cnt, $fields_meta, $tmpRow, true);

        //Append it to row array as where_clause
        $row['where_clause'] = $uniqueCondition[0];
        if ($dataLabel == $inputs[0] || $dataLabel == $inputs[1]) {
            $data[] = array(
                $inputs[0]     => $row[$inputs[0]],
                $inputs[1]     => $row[$inputs[1]],
                'where_clause' => $uniqueCondition[0]
            );
        } elseif ($dataLabel) {
            $data[] = array(
                $inputs[0]     => $row[$inputs[0]],
                $inputs[1]     => $row[$inputs[1]],
                $dataLabel     => $row[$dataLabel],
                'where_clause' => $uniqueCondition[0]
            );
        } else {
            $data[] = array(
                $inputs[0]     => $row[$inputs[0]],
                $inputs[1]     => $row[$inputs[1]],
                $dataLabel     => '',
                'where_clause' => $uniqueCondition[0]
            );
        }
    }
    /*
     * Form for displaying point data and also the scatter plot
     */
    ?>
    <form method="post" action="tbl_zoom_select.php" name="displayResultForm" id="zoom_display_form"
        <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?>>
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="back" value="tbl_zoom_select.php" />

    <fieldset id="displaySection">
      <legend><?php echo __('Browse/Edit the points') ?></legend>
      <center>
    <?php
    //JSON encode the data(query result)
    if (isset($zoom_submit) && ! empty($data)) {
        ?>
        <div id="resizer">
          <center><a href="#" onclick="displayHelp();"><?php echo __('How to use'); ?></a></center>
          <div id="querydata" style="display:none">
        <?php
        echo json_encode($data);
        ?>
          </div>
          <div id="querychart"></div>
          <button class="button-reset"><?php echo __('Reset zoom'); ?></button>
        </div>
        <?php
    }
    ?>
      </center>
      <div id='dataDisplay' style="display:none">
        <table>
          <thead>
            <tr>
              <th> <?php echo __('Column'); ?> </th>
              <th> <?php echo __('Null'); ?> </th>
              <th> <?php echo __('Value'); ?> </th>
            </tr>
          </thead>
          <tbody>
    <?php
    $odd_row = true;
    for ($i = 4; $i < $fields_cnt + 4; $i++) {
        $tbl_fields_type[$i] = $fields_type[$i - 4];
        $fieldpopup = $fields_list[$i - 4];
        $foreignData = PMA_getForeignData($foreigners, $fieldpopup, false, '', '');
        ?>
            <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
              <th><?php echo htmlspecialchars($fields_list[$i - 4]); ?></th>
              <th><?php echo ($fields_null[$i - 4] == 'YES')
                  ? '<input type="checkbox" class="checkbox_null" name="fields_null[ '
                      . $i . ' ]" id="fields_null_id_' . $i . '" />'
                  : ''; ?>
              </th>
              <th> <?php
              echo PMA_getForeignFields_Values(
                  $foreigners, $foreignData, $fieldpopup, $tbl_fields_type,
                  $i, $db, $table, $titles,
                  $GLOBALS['cfg']['ForeignKeyMaxLimit'], '', false, true
              ); ?>
              </th>
            </tr>
        <?php
    }
    ?>
          </tbody>
        </table>
    </div>
    <input type="hidden" id="queryID" name="sql_query" />
    </form>
    <?php
}
require './libraries/footer.inc.php';
?>
