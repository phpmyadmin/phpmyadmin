<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_chart.js');
$scripts->addFile('highcharts/highcharts.js');
/* Files required for chart exporting */
$scripts->addFile('highcharts/exporting.js');
/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $scripts->addFile('canvg/flashcanvas.js');
}
$scripts->addFile('canvg/canvg.js');

/**
 * Runs common work
 */
if (strlen($GLOBALS['table'])) {
    $url_params['goto'] = $cfg['DefaultTabTable'];
    $url_params['back'] = 'tbl_sql.php';
    include 'libraries/tbl_common.inc.php';
    include 'libraries/tbl_info.inc.php';
} elseif (strlen($GLOBALS['db'])) {
    $url_params['goto'] = $cfg['DefaultTabDatabase'];
    $url_params['back'] = 'sql.php';
    include 'libraries/db_common.inc.php';
    include 'libraries/db_info.inc.php';
} else {
    $url_params['goto'] = $cfg['DefaultTabServer'];
    $url_params['back'] = 'sql.php';
    include 'libraries/server_common.inc.php';
}

/*
 * Execute the query and return the result
 */
$data = array();

$result = PMA_DBI_try_query($sql_query);
$fields_meta = PMA_DBI_get_fields_meta($result);
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
    <div style="float:left; width:370px;">
        <input type="radio" name="chartType" value="bar" id="radio_bar" />
        <label for ="radio_bar"><?php echo _pgettext('Chart type', 'Bar'); ?></label>
        <input type="radio" name="chartType" value="column" id="radio_column" />
        <label for ="radio_column"><?php echo _pgettext('Chart type', 'Column'); ?></label>
        <input type="radio" name="chartType" value="line" id="radio_line" checked="checked" />
        <label for ="radio_line"><?php echo _pgettext('Chart type', 'Line'); ?></label>
        <input type="radio" name="chartType" value="spline" id="radio_spline" />
        <label for ="radio_spline"><?php echo _pgettext('Chart type', 'Spline'); ?></label>
        <span class="span_pie" style="display:none;">
        <input type="radio" name="chartType" value="pie" id="radio_pie" />
        <label for ="radio_pie"><?php echo _pgettext('Chart type', 'Pie'); ?></label>
        </span>
        <span class="barStacked" style="display:none;">
        <input type="checkbox" name="barStacked" value="1" id="checkbox_barStacked" />
        <label for ="checkbox_barStacked"><?php echo __('Stacked'); ?></label>
        </span>
        <br>
        <input type="text" name="chartTitle" value="<?php echo __('Chart title'); ?>">
    </div>
        <?php
        $keys = array_keys($data[0]);
        $yaxis = -1;
        if (count($keys) > 1) { ?>
            <div style="float:left; padding-left:40px;">
            <label for="select_chartXAxis"><?php echo __('X-Axis:'); ?></label>
            <select name="chartXAxis" id="select_chartXAxis">
            <?php

            foreach ($keys as $idx => $key) {
                if ($yaxis == -1 && (($idx == count($data[0]) - 1) || preg_match("/(date|time)/i", $key))) {
                    echo '<option value="' . htmlspecialchars($idx) . '" selected="selected">' . htmlspecialchars($key) . '</option>';
                    $yaxis = $idx;
                } else {
                    echo '<option value="' . htmlspecialchars($idx) . '">' . htmlspecialchars($key) . '</option>';
                }
            }

            ?>
        </select><br />
        <label for="select_chartSeries"><?php echo __('Series:'); ?></label>
        <select name="chartSeries" id="select_chartSeries" multiple="multiple">
            <?php
            $numeric_types = array('int', 'real', 'year', 'bit');
            foreach ($keys as $idx => $key) {
                if (in_array($fields_meta[$idx]->type, $numeric_types)) {
                    if ($idx == $yaxis) {
                        echo '<option value"' . htmlspecialchars($key) . '">' . htmlspecialchars($key) . '</option>';
                    } else {
                        echo '<option value"' . htmlspecialchars($key) . '" selected="selected">' . htmlspecialchars($key) . '</option>';
                    }
                }
            }
        ?>
        </select>
        </div>
        <?php
        }
        ?>
    <div style="float:left; padding-left:40px;">
        <label for="xaxis_label"><?php echo __('X-Axis label:'); ?></label>
        <input style="margin-top:0;" type="text" name="xaxis_label" id="xaxis_label"
            value="<?php echo ($yaxis == -1) ? __('X Values') : htmlspecialchars($keys[$yaxis]); ?>" /><br />
        <label for="yaxis_label"><?php echo __('Y-Axis label:'); ?></label>
        <input type="text" name="yaxis_label" id="yaxis_label" value="<?php echo __('Y Values'); ?>" />
    </div>
    <p style="clear:both;">&nbsp;</p>
    <div id="resizer" style="width:600px; height:400px;">
        <div id="inner-resizer">
            <div id="querychart" style="display:none;"></div>
        </div>
    </div>
</fieldset>
</form>
</div>
<script type="text/javascript">
//<![CDATA[
    chart_data = <?php echo strtr(json_encode($data), array('<' => '&lt;', '>' => '&gt;')); ?>;
//]]>
</script>
