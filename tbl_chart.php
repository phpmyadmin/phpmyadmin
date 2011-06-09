<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package phpMyAdmin
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

$GLOBALS['js_include'][] = 'highcharts/highcharts.js';
/* Files required for chart exporting */
$GLOBALS['js_include'][] = 'highcharts/exporting.js';
$GLOBALS['js_include'][] = 'canvg/canvg.js';
$GLOBALS['js_include'][] = 'canvg/rgbcolor.js';




/**
 * Runs common work
 */
require './libraries/db_common.inc.php';
$url_params['goto'] = $cfg['DefaultTabDatabase'];
$url_params['back'] = 'sql.php';

/*
 * Import chart functions
 */
require_once './libraries/chart.lib.php';

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

// get the chart and settings after chart generation
$chart = PMA_chart_results($data, $chartSettings);

if (!empty($chart)) {
    $message = PMA_Message::success(__('Chart generated successfully.'));
}
else {
    $message = PMA_Message::error(__('The result of this query can\'t be used for a chart. See [a@./Documentation.html#faq6_29@Documentation]FAQ 6.29[/a]'));
}

/**
 * Displays top menu links
 * We use db links because a chart is not necessarily on a single table
 */
$num_tables = 0;
require_once './libraries/db_links.inc.php';

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
        <input type="radio" name="chartType" value="bar"><?php echo __('Bar'); ?>
        <input type="radio" name="chartType" value="column"><?php echo __('Column'); ?>
        <input type="radio" name="chartType" value="line" checked><?php echo __('Line'); ?>
        <input type="radio" name="chartType" value="spline"><?php echo __('Spline'); ?>
        <input type="radio" name="chartType" value="pie"><?php echo __('Pie'); ?>
        <span class="barStacked" style="display:none;">
        <input type="checkbox" name="barStacked" value="1"><?php echo __('Stacked'); ?>
        </span>
        <br>
        <input type="text" name="chartTitle" value="<?php echo __('Chart title'); ?>">
        <?php $keys = array_keys($data[0]);
		if(count($keys)>1) {
			echo __('X-Axis:'); ?> <select name="chartXAxis">
            <?php
            $yaxis=-1;
            
            foreach($keys as $idx=>$key) {
                if($yaxis==-1 && ($idx==count($data[0])-1 || preg_match("/(date|time)/i",$key))) {
                    echo '<option value="'.$idx.'" selected>'.$key.'</option>';
                    $yaxis=$idx;
                } else {
                    echo '<option value="'.$idx.'">'.$key.'</option>';
                }
            }
            
            ?>
        </select>
		<?php
		}
		?>
    </div>
    <div style="float:left; padding-left:40px;">
        <?php echo __('X-Axis label:'); ?> <input style="margin-top:0;" type="text" name="xaxis_label" value="<?php echo $keys[$yaxis]; ?>"><br>
        <?php echo __('Y-Axis label:'); ?> <input type="text" name="yaxis_label" value="<?php echo __('Y Values'); ?>">
    </div>
    <p style="clear:both;">&nbsp;</p>
    <div id="resizer" style="width:600px; height:400px;">
        <div id="inner-resizer">
            <div id="querychart">
                <?php echo json_encode($data); ?>
            </div>
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
