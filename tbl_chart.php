<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @version $Id$
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

/**
 * Runs common work
 */
require './libraries/db_common.inc.php';
$url_params['goto'] = $cfg['DefaultTabDatabase'];
$url_params['back'] = 'view_create.php';

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
    /*foreach ($row as $key => $value) {
        $chartData[$key][] = $value;
    }*/
}

// get settings if any posted
$chartSettings = array();
if (PMA_isValid($_REQUEST['chartSettings'], 'array')) {
    $chartSettings = $_REQUEST['chartSettings'];
}

// get the chart and settings used to generate chart
$chart = PMA_chart_results($data, $chartSettings);

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
?>
<!-- Display Chart options -->
<div id="div_view_options">
<form method="post" action="tbl_chart.php">
<?php echo PMA_generate_common_hidden_inputs($url_params); ?>
<fieldset>
    <legend><?php echo __('Display chart'); ?></legend>

    <div style="float: right">
        <?php echo $chart; ?>
    </div>

    <input type="hidden" name="sql_query" id="sql_query" value="<?php echo $sql_query; ?>" />

    <table>
    <tr><td><label for="width"><?php echo __("Width"); ?></label></td>
        <td><input type="text" name="chartSettings[width]" id="width" value="<?php echo $chartSettings['width']; ?>" /></td>
    </tr>

    <tr><td><label for="height"><?php echo __("Height"); ?></label></td>
        <td><input type="text" name="chartSettings[height]" id="height" value="<?php echo $chartSettings['height']; ?>" /></td>
    </tr>
    </table>

</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="displayChart" value="<?php echo __('Redraw'); ?>" />
</fieldset>
</form>
</div>
<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';

?>
