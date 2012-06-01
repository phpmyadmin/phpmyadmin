<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table zoom search tab
 *
 * display table zoom search form, create SQL queries from form data
 *
 * @package PhpMyAdmin
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
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'tbl_zoom_plot_jqplot.js';

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'criteriaColumnCollations',
    'dataLabel',
    'criteriaValues',
    'criteriaColumnNullFlags',
    'criteriaColumnNames',
    'maxPlotLimit',
    'criteriaColumnTypes',
    'zoom_submit',
    'criteriaColumnOperators'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

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
                $row[$col] = PMA_printableBitValue($val, $fields_meta[$i]->length);
            }
            $i++;
        }
        $extra_data['row_info'] = $row;
    }
    PMA_ajaxResponse(null, true, $extra_data);
}

/**
 * Handle AJAX request for changing field information
 * (value,collation,operators,field values) in input form
 * @var post_params Object containing parameters for the POST request
 */

if (isset($_REQUEST['change_tbl_info']) && $_REQUEST['change_tbl_info'] == true) {
    $extra_data = array();
    $field = $_REQUEST['field'];
    if ($field == 'pma_null') {
        $extra_data['field_type'] = '';
        $extra_data['field_collation'] = '';
        $extra_data['field_operators'] = '';
        PMA_ajaxResponse(null, true, $extra_data);
    }


    // Gets the list and number of fields
    list($columnNames, $columnTypes, $columnCollations, $columnNullFlags)
        = PMA_tbl_getFields($_REQUEST['db'], $_REQUEST['table']);

    $foreigners = PMA_getForeigners($db, $table);
    $titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));
    $key = array_search($field, $columnNames);
    $extra_data['field_type'] = $columnTypes[$key];
    $extra_data['field_collation'] = $columnCollations[$key];

    // HTML for operators
    $html = '<select name="criteriaColumnOperators[]">';
    $html .= $GLOBALS['PMA_Types']->getTypeOperatorsHtml(
        preg_replace('@\(.*@s', '', $columnTypes[$key]),
        $columnNullFlags[$key]
    );
    $html .= '</select>';
    $extra_data['field_operators'] = $html;

    // retrieve keys into foreign fields, if any
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    $foreignData = PMA_getForeignData($foreigners, $field, false, '', '');

    // HTML for field values
    $html = PMA_getForeignFields_Values(
        $foreigners,
        $foreignData,
        $field,
        array($_REQUEST['it'] => $columnTypes[$key]),
        $_REQUEST['it'],
        $_REQUEST['db'],
        $_REQUEST['table'],
        $titles,
        $GLOBALS['cfg']['ForeignKeyMaxLimit'],
        ''
    );
    $extra_data['field_value'] = $html;
    PMA_ajaxResponse(null, true, $extra_data);
}

$titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));
/**
 * Not selection yet required -> displays the selection form
 */

// Gets some core libraries
require_once './libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

/**
 * Gets tables informations
 */
require_once './libraries/tbl_info.inc.php';

if (! isset($goto)) {
    $goto = $GLOBALS['cfg']['DefaultTabTable'];
}
// Defines the url to return to in case of error in the next sql statement
$err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

// Gets the list and number of fields

list($columnNames, $columnTypes, $columnCollations, $columnNullFlags)
    = PMA_tbl_getFields($db, $table);
$columnCount = count($columnNames);

// retrieve keys into foreign fields, if any
// check also foreigners even if relwork is FALSE (to get
// foreign keys from innodb)
$foreigners = PMA_getForeigners($db, $table);
$tbl_fields_type = $tbl_fields_collation = $tbl_criteriaColumnNullFlags = array();

if (! isset($zoom_submit) && ! isset($criteriaColumnNames)) {
    $dataLabel = PMA_getDisplayField($db, $table);
}
?>
<div id="sqlqueryresults"></div>
<fieldset id="fieldset_subtab">
<?php
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;
echo PMA_generateHtmlTabs(PMA_tbl_getSubTabs(), $url_params, 'topmenu2');

/**
 *  Set the field name,type,collation and whether null on select of a coulmn
 */
if (isset($criteriaColumnNames) && ($criteriaColumnNames[0] != 'pma_null' || $criteriaColumnNames[1] != 'pma_null')) {
    for ($i = 0 ; $i < 4 ; $i++) {
        if ($criteriaColumnNames[$i] != 'pma_null') {
            $key = array_search($criteriaColumnNames[$i], $columnNames);
            $tbl_fields_type[$i] = $columnTypes[$key];
            $tbl_fields_collation[$i] = $columnCollations[$key];
            $tbl_fields_func[$i] = '<select name="criteriaColumnOperators[]">';
            $tbl_fields_func[$i] .= $GLOBALS['PMA_Types']->getTypeOperatorsHtml(
                preg_replace('@\(.*@s', '', $columnTypes[$key]),
                $columnNullFlags[$key], $criteriaColumnOperators[$i]
            );
            $tbl_fields_func[$i] .= '</select>';
            $foreignData = PMA_getForeignData($foreigners, $criteriaColumnNames[$i], false, '', '');
            $tbl_fields_value[$i] =  PMA_getForeignFields_Values(
                $foreigners,
                $foreignData,
                $criteriaColumnNames[$i],
                $tbl_fields_type,
                $i,
                $db,
                $table,
                $titles,
                $GLOBALS['cfg']['ForeignKeyMaxLimit'],
                $criteriaValues
            );
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

<fieldset id="inputSection">

<legend><?php echo __('Do a "query by example" (wildcard: "%") for two different columns') ?></legend>
<table class="data" id="tableFieldsId">
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
        <th><select name="criteriaColumnNames[]" id="<?php echo 'tableid_' . $i; ?>" >
        <option value="<?php echo 'pma_null'; ?>"><?php echo __('None');  ?></option>
    <?php
    for ($j = 0 ; $j < $columnCount ; $j++) {
        if (isset($criteriaColumnNames[$i]) && $criteriaColumnNames[$i] == htmlspecialchars($columnNames[$j])) {?>
            <option value="<?php echo htmlspecialchars($columnNames[$j]);?>" selected="selected">
                <?php echo htmlspecialchars($columnNames[$j]);?></option>
        <?php
        } else { ?>
            <option value="<?php echo htmlspecialchars($columnNames[$j]);?>">
                <?php echo htmlspecialchars($columnNames[$j]);?></option>
        <?php
        }
    } ?>
        </select></th>
        <td><?php if (isset($tbl_fields_type[$i])) echo $tbl_fields_type[$i]; ?></td>
        <td><?php if (isset($tbl_fields_collation[$i])) echo $tbl_fields_collation[$i]; ?></td>
        <td><?php if (isset($tbl_fields_func[$i])) echo $tbl_fields_func[$i]; ?></td>
        <td><?php if (isset($tbl_fields_value[$i])) echo $tbl_fields_value[$i]; ?></td>
    </tr>
    <tr><td>
    <input type="hidden" name="criteriaColumnTypes[<?php echo $i; ?>]" id="types_<?php echo $i; ?>"
    <?php
    if (isset($_POST['criteriaColumnTypes'][$i])) {
        echo 'value="' . $_POST['criteriaColumnTypes'][$i] . '"';
    }
    ?> />
      <input type="hidden" name="criteriaColumnCollations[<?php echo $i;?>]" id="collations_<?php echo $i; ?>" />
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
if (isset($zoom_submit) && $criteriaColumnNames[0] != 'pma_null' && $criteriaColumnNames[1] != 'pma_null') {
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
for ($j = 0; $j < $columnCount; $j++) {
    if (isset($dataLabel) && $dataLabel == htmlspecialchars($columnNames[$j])) {
        ?>
        <option value="<?php echo htmlspecialchars($columnNames[$j]);?>" selected="selected">
            <?php echo htmlspecialchars($columnNames[$j]);?></option>
        <?php
    } else {
        ?>
        <option value="<?php echo htmlspecialchars($columnNames[$j]);?>" >
            <?php echo htmlspecialchars($columnNames[$j]);?></option>
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
    <input type="submit" name="zoom_submit" id="inputFormSubmitId" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>
</fieldset>

<?php

/*
 * Handle the input criteria and generate the query result
 * Form for displaying query results
 */
if (isset($zoom_submit)
    && $criteriaColumnNames[0] != 'pma_null'
    && $criteriaColumnNames[1] != 'pma_null'
    && $criteriaColumnNames[0] != $criteriaColumnNames[1]
) {

    /*
     * Query generation part
     */
    $sql_query = PMA_tblSearchBuildSqlQuery();
    $sql_query .= ' LIMIT ' . $maxPlotLimit;

    /*
     * Query execution part
     */
    $result = PMA_DBI_query($sql_query . ";", null, PMA_DBI_QUERY_STORE);
    $fields_meta = PMA_DBI_get_fields_meta($result);
    while ($row = PMA_DBI_fetch_assoc($result)) {
        //Need a row with indexes as 0,1,2 for the PMA_getUniqueCondition
        // hence using a temporary array
        $tmpRow = array();
        foreach ($row as $val) {
            $tmpRow[] = $val;
        }
        //Get unique conditon on each row (will be needed for row update)
        $uniqueCondition = PMA_getUniqueCondition(
            $result, $columnCount, $fields_meta, $tmpRow, true
        );

        //Append it to row array as where_clause
        $row['where_clause'] = $uniqueCondition[0];
        if ($dataLabel == $criteriaColumnNames[0] || $dataLabel == $criteriaColumnNames[1]) {
            $data[] = array(
                $criteriaColumnNames[0]     => $row[$criteriaColumnNames[0]],
                $criteriaColumnNames[1]     => $row[$criteriaColumnNames[1]],
                'where_clause' => $uniqueCondition[0]
            );
        } elseif ($dataLabel) {
            $data[] = array(
                $criteriaColumnNames[0]     => $row[$criteriaColumnNames[0]],
                $criteriaColumnNames[1]     => $row[$criteriaColumnNames[1]],
                $dataLabel     => $row[$dataLabel],
                'where_clause' => $uniqueCondition[0]
            );
        } else {
            $data[] = array(
                $criteriaColumnNames[0]     => $row[$criteriaColumnNames[0]],
                $criteriaColumnNames[1]     => $row[$criteriaColumnNames[1]],
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
    for ($i = 4; $i < $columnCount + 4; $i++) {
        $tbl_fields_type[$i] = $columnTypes[$i - 4];
        $fieldpopup = $columnNames[$i - 4];
        $foreignData = PMA_getForeignData($foreigners, $fieldpopup, false, '', '');
        ?>
            <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
              <th><?php echo htmlspecialchars($columnNames[$i - 4]); ?></th>
              <th><?php echo ($columnNullFlags[$i - 4] == 'YES')
                  ? '<input type="checkbox" class="checkbox_null" name="criteriaColumnNullFlags[ '
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
