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
/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $GLOBALS['js_include'][] = 'canvg/flashcanvas.js';
}
$GLOBALS['js_include'][] = 'canvg/canvg.js';

/**
 * Runs common work
 */
if (strlen($GLOBALS['table'])) {
    $url_params['goto'] = $cfg['DefaultTabTable'];
    $url_params['back'] = 'tbl_sql.php';
    require './libraries/tbl_common.php';
    require './libraries/tbl_info.inc.php';
    require './libraries/tbl_links.inc.php';
} elseif (strlen($GLOBALS['db'])) {
    $url_params['goto'] = $cfg['DefaultTabDatabase'];
    $url_params['back'] = 'sql.php';
    require './libraries/db_common.inc.php';
    require './libraries/db_info.inc.php';
} else {
    $url_params['goto'] = $cfg['DefaultTabServer'];
    $url_params['back'] = 'sql.php';
    require './libraries/server_common.inc.php';
    require './libraries/server_links.inc.php';
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
        <?php
        $keys = array_keys($data[0]);
        $yaxis = -1;
        if (count($keys) > 1) {
            echo '<br>';
            echo __('X-Axis:'); ?> <select name="chartXAxis">
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
        <?php echo __('Series:'); ?>
        <select name="chartSeries">
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
        <?php echo __('X-Axis label:'); ?> <input style="margin-top:0;" type="text" name="xaxis_label" 
            value="<?php echo ($yaxis == -1) ? __('X Values') : $keys[$yaxis]; ?>"><br />
        <?php echo __('Y-Axis label:'); ?> <input type="text" name="yaxis_label" value="<?php echo __('Y Values'); ?>">
    </div>
    <p style="clear:both;">&nbsp;</p>
    <div id="resizer" style="width:600px; height:400px;">
        <div id="inner-resizer">
            <div id="querychart" style="display:none;">
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
