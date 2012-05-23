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

$scripts = PMA_Header::getInstance()->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('sql.js');
$scripts->addFile('date.js');
/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $scripts->addFile('canvg/flashcanvas.js');
}
$scripts->addFile('jqplot/jquery.jqplot.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasTextRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasAxisLabelRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.dateAxisRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.highlighter.js');
$scripts->addFile('jqplot/plugins/jqplot.cursor.js');
$scripts->addFile('canvg/canvg.js');
$scripts->addFile('jquery/timepicker.js');
$scripts->addFile('tbl_zoom_plot_jqplot.js');

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'dataLabel',
    'maxPlotLimit',
    'zoom_submit'
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
        $extra_data['field_value'] = '';
        PMA_ajaxResponse(null, true, $extra_data);
    }
    // Gets the list and number of fields
    list($columnNames, $columnTypes, $columnCollations, $columnNullFlags)
        = PMA_tbl_getFields($_REQUEST['db'], $_REQUEST['table']);
    $foreigners = PMA_getForeigners($db, $table);
    $key = array_search($field, $columnNames);
    $properties = PMA_tblSearchGetColumnProperties(
        $db, $table, $columnNames, $columnTypes, $columnCollations,
        $columnNullFlags, $foreigners, $_REQUEST['it'], $key
    );
    $extra_data['field_type'] = $properties['type'];
    $extra_data['field_collation'] = $properties['collation'];
    $extra_data['field_operators'] = $properties['func'];
    $extra_data['field_value'] = $properties['value'];
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

// retrieve keys into foreign fields, if any
// check also foreigners even if relwork is FALSE (to get
// foreign keys from innodb)
$foreigners = PMA_getForeigners($db, $table);

//Set default datalabel if not selected
if ( !isset($_POST['zoom_submit']) || $_POST['dataLabel'] == '') {
    $dataLabel = PMA_getDisplayField($db, $table);
}
echo PMA_tblSearchGetSelectionForm(
    $goto, $db, $table, $columnNames, $columnTypes, $columnCollations,
    $columnNullFlags, false, $foreigners, "zoom", $dataLabel
);
?>

<?php
/*
 * Handle the input criteria and generate the query result
 * Form for displaying query results
 */
if (isset($zoom_submit)
    && $_POST['criteriaColumnNames'][0] != 'pma_null'
    && $_POST['criteriaColumnNames'][1] != 'pma_null'
    && $_POST['criteriaColumnNames'][0] != $_POST['criteriaColumnNames'][1]
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
            $result, count($columnNames), $fields_meta, $tmpRow, true
        );

        //Append it to row array as where_clause
        $row['where_clause'] = $uniqueCondition[0];
        $tmpData = array(
            $_POST['criteriaColumnNames'][0] => $row[$_POST['criteriaColumnNames'][0]],
            $_POST['criteriaColumnNames'][1] => $row[$_POST['criteriaColumnNames'][1]],
            'where_clause' => $uniqueCondition[0]
        );
        $tmpData[$dataLabel] = ($dataLabel) ? $row[$dataLabel] : '';

        $data[] = $tmpData;
    }
    unset($tmpData);
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
    for ($column_index = 0; $column_index < count($columnNames); $column_index++) {
        $fieldpopup = $columnNames[$column_index];
        $foreignData = PMA_getForeignData($foreigners, $fieldpopup, false, '', '');
        ?>
            <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
              <th><?php echo htmlspecialchars($columnNames[$column_index]); ?></th>
              <th><?php echo ($columnNullFlags[$column_index] == 'YES')
                  ? '<input type="checkbox" class="checkbox_null" name="criteriaColumnNullFlags[ '
                      . $column_index . ' ]" id="edit_fields_null_id_' . $column_index . '" />'
                  : ''; ?>
              </th>
              <th> <?php
              echo PMA_getForeignFields_Values(
                  $foreigners, $foreignData, $fieldpopup, $columnTypes,
                  $column_index, $db, $table, $titles,
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
