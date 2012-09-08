<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */

/**
 * do not import request variable into global scope
 * @ignore
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}

/**
 *
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'tbl_chart.js';
$GLOBALS['js_include'][] = 'jqplot/jquery.jqplot.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.barRenderer.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.canvasAxisLabelRenderer.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.canvasTextRenderer.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.categoryAxisRenderer.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.pointLabels.js';
$GLOBALS['js_include'][] = 'jqplot/plugins/jqplot.pieRenderer.js';

/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $GLOBALS['js_include'][] = 'canvg/flashcanvas.js';
}
//$GLOBALS['js_include'][] = 'canvg/canvg.js';

/**
 * Runs common work
 */
if (strlen($GLOBALS['table'])) {
    $url_params['goto'] = $cfg['DefaultTabTable'];
    $url_params['back'] = 'tbl_sql.php';
    include './libraries/tbl_common.php';
    include './libraries/tbl_info.inc.php';
    include './libraries/tbl_links.inc.php';
} elseif (strlen($GLOBALS['db'])) {
    $url_params['goto'] = $cfg['DefaultTabDatabase'];
    $url_params['back'] = 'sql.php';
    include './libraries/db_common.inc.php';
    include './libraries/db_info.inc.php';
} else {
    $url_params['goto'] = $cfg['DefaultTabServer'];
    $url_params['back'] = 'sql.php';
    include './libraries/server_common.inc.php';
    include './libraries/server_links.inc.php';
}

/*
 * Execute the query and return the result
 */
$data = array();

$result = PMA_DBI_try_query($sql_query);
while ($row = PMA_DBI_fetch_assoc($result)) {
    $data[] = $row;
}

// get settings if any posted
$chartSettings = array();
if (PMA_isValid($_REQUEST['chartSettings'], 'array')) {
    $chartSettings = $_REQUEST['chartSettings'];
}

$url_params['db'] = $GLOBALS['db'];
$url_params['reload'] = 1;

/**
 * Displays the page
 */
// pma_token/url_query needed for chart export
?>
<script type="text/javascript">
pma_token = '<?php echo $_SESSION[' PMA_token ']; ?>';
url_query = '<?php echo $url_query;?>';
</script>
<!-- Display Chart options -->
<div id="div_view_options">
<form method="post" action="tbl_chart.php">
<?php echo PMA_generate_common_hidden_inputs($url_params); ?>
<fieldset>
    <legend><?php echo __('Display chart'); ?></legend>
    <div style="float:left;">
        <input type="radio" name="chartType" value="bar" id="radio_bar" />
        <label for ="radio_bar"><?php echo _pgettext('Chart type', 'Bar'); ?></label>
        <input type="radio" name="chartType" value="column" id="radio_column" />
        <label for ="radio_column"><?php echo _pgettext('Chart type', 'Column'); ?></label>
        <input type="radio" name="chartType" value="line" id="radio_line" checked="checked" />
        <label for ="radio_line"><?php echo _pgettext('Chart type', 'Line'); ?></label>
        <input type="radio" name="chartType" value="spline" id="radio_spline" />
        <label for ="radio_spline"><?php echo _pgettext('Chart type', 'Spline'); ?></label>
        <input type="radio" name="chartType" value="pie" id="radio_pie" />
        <label for ="radio_pie"><?php echo _pgettext('Chart type', 'Pie'); ?></label>
        <span class="barStacked" style="display:none;">
        <input type="checkbox" name="barStacked" value="1" id="checkbox_barStacked" />
        <label for ="checkbox_barStacked"><?php echo __('Stacked'); ?></label>
        </span>
        <br>
        <input type="text" name="chartTitle" value="<?php echo __('Chart title'); ?>">
        <?php
        $keys = array_keys($data[0]);
        $yaxis = -1;
        if (count($keys) > 1) { ?>
            <br />
            <label for="select_chartXAxis"><?php echo __('X-Axis:'); ?></label>
            <select name="chartXAxis" id="select_chartXAxis">
            <?php
            
            foreach ($keys as $idx => $key) {
                if ($yaxis == -1 && (($idx == count($data[0]) - 1) || preg_match("/(date|time)/i", $key))) {
                    echo '<option value="' . htmlspecialchars($idx) . '" selected>' . htmlspecialchars($key) . '</option>';
                    $yaxis=$idx;
                } else {
                    echo '<option value="' . htmlspecialchars($idx) . '">' . htmlspecialchars($key) . '</option>';
                }
            }
            
            ?>
        </select><br />
        <label for="select_chartSeries"><?php echo __('Series:'); ?></label>
        <select name="chartSeries" id="select_chartSeries">
            <option value="columns"><?php echo __('The remaining columns'); ?></option>
            <?php
            foreach ($keys as $idx => $key) {
                echo '<option>' . htmlspecialchars($key) . '</option>';
            }
        ?>
        </select>
        <?php
        }
        ?>
        
    </div>
    <div style="float:left; padding-left:40px;">
        <label for="xaxis_label"><?php echo __('X-Axis label:'); ?></label>
        <input style="margin-top:0;" type="text" name="xaxis_label" id="xaxis_label"
            value="<?php echo ($yaxis == -1) ? __('X Values') : htmlspecialchars($keys[$yaxis]); ?>" /><br />
        <label for="yaxis_label"><?php echo __('Y-Axis label:'); ?></label>
        <input type="text" name="yaxis_label" id="yaxis_label" value="<?php echo __('Y Values'); ?>" />
    </div>
    <p style="clear:both;">&nbsp;</p>
    <div id="resizer" style="width:600px; height:400px;">
        <div id="querychart">
<?php
$sanitized_data = array();
foreach ($data as $data_row_number => $data_row) {
    $tmp_row = array();
    foreach ($data_row as $data_column => $data_value) {
        $tmp_row[htmlspecialchars($data_column)] = htmlspecialchars($data_value);
    }
    $sanitized_data[] = $tmp_row;
} 
echo json_encode($sanitized_data); 
unset($sanitized_data);
?>
        </div>
    </div>
</fieldset>
</form>
</div>
<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';

?>
