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

$GLOBALS['js_include'][] = 'pMap.js';

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

    <input type="hidden" name="sql_query" id="sql_query" value="<?php echo htmlspecialchars($sql_query); ?>" />

    <table>
    <tr><td><label for="width"><?php echo __("Width"); ?></label></td>
        <td><input type="text" name="chartSettings[width]" id="width" value="<?php echo (isset($chartSettings['width']) ? htmlspecialchars($chartSettings['width']) : ''); ?>" /></td>
    </tr>

    <tr><td><label for="height"><?php echo __("Height"); ?></label></td>
        <td><input type="text" name="chartSettings[height]" id="height" value="<?php echo (isset($chartSettings['height']) ? htmlspecialchars($chartSettings['height']) : ''); ?>" /></td>
    </tr>

    <tr><td><label for="titleText"><?php echo __("Title"); ?></label></td>
        <td><input type="text" name="chartSettings[titleText]" id="titleText" value="<?php echo (isset($chartSettings['titleText']) ? htmlspecialchars($chartSettings['titleText']) : ''); ?>" /></td>
    </tr>

    <?php if ($chartSettings['type'] != 'pie' && $chartSettings['type'] != 'radar') { ?>
    <tr><td><label for="xLabel"><?php echo __("X Axis label"); ?></label></td>
        <td><input type="text" name="chartSettings[xLabel]" id="xLabel" value="<?php echo (isset($chartSettings['xLabel']) ? htmlspecialchars($chartSettings['xLabel']) : ''); ?>" /></td>
    </tr>

    <tr><td><label for="yLabel"><?php echo __("Y Axis label"); ?></label></td>
        <td><input type="text" name="chartSettings[yLabel]" id="yLabel" value="<?php echo (isset($chartSettings['yLabel']) ? htmlspecialchars($chartSettings['yLabel']) : ''); ?>" /></td>
    </tr>
    <?php } ?>

    <tr><td><label for="areaMargins"><?php echo __("Area margins"); ?></label></td>
        <td>
            <input type="text" name="chartSettings[areaMargins][]" size="2" value="<?php echo (isset($chartSettings['areaMargins'][0]) ? htmlspecialchars($chartSettings['areaMargins'][0]) : ''); ?>" />
            <input type="text" name="chartSettings[areaMargins][]" size="2" value="<?php echo (isset($chartSettings['areaMargins'][1]) ? htmlspecialchars($chartSettings['areaMargins'][1]) : ''); ?>" />
            <input type="text" name="chartSettings[areaMargins][]" size="2" value="<?php echo (isset($chartSettings['areaMargins'][2]) ? htmlspecialchars($chartSettings['areaMargins'][2]) : ''); ?>" />
            <input type="text" name="chartSettings[areaMargins][]" size="2" value="<?php echo (isset($chartSettings['areaMargins'][3]) ? htmlspecialchars($chartSettings['areaMargins'][3]) : ''); ?>" />
        </td>
    </tr>

    <?php if ($chartSettings['legend'] == true) { ?>
    <tr><td><label for="legendMargins"><?php echo __("Legend margins"); ?></label></td>
        <td>
            <input type="text" name="chartSettings[legendMargins][]" size="2" value="<?php echo htmlspecialchars($chartSettings['legendMargins'][0]); ?>" />
            <input type="text" name="chartSettings[legendMargins][]" size="2" value="<?php echo htmlspecialchars($chartSettings['legendMargins'][1]); ?>" />
            <input type="text" name="chartSettings[legendMargins][]" size="2" value="<?php echo htmlspecialchars($chartSettings['legendMargins'][2]); ?>" />
            <input type="text" name="chartSettings[legendMargins][]" size="2" value="<?php echo htmlspecialchars($chartSettings['legendMargins'][3]); ?>" />
        </td>
    </tr>
    <?php } ?>

    <tr><td><label for="type"><?php echo __("Type"); ?></label></td>
        <td>
            <input type="radio" name="chartSettings[type]" value="bar" <?php echo ($chartSettings['type'] == 'bar' ? 'checked' : ''); ?>><?php echo __('Bar'); ?>
            <input type="radio" name="chartSettings[type]" value="line" <?php echo ($chartSettings['type'] == 'line' ? 'checked' : ''); ?>><?php echo __('Line'); ?>
            <input type="radio" name="chartSettings[type]" value="radar" <?php echo ($chartSettings['type'] == 'radar' ? 'checked' : ''); ?>><?php echo __('Radar'); ?>
            <?php if ($chartSettings['multi'] == false) { ?>
            <input type="radio" name="chartSettings[type]" value="pie" <?php echo ($chartSettings['type'] == 'pie' ? 'checked' : ''); ?>><?php echo __('Pie'); ?>
            <?php } ?>
        </td>
    </tr>

    <?php if ($chartSettings['type'] == 'bar' && isset($chartSettings['multi']) && $chartSettings['multi'] == true) { ?>
    <tr><td><label for="barType"><?php echo __("Bar type"); ?></label></td>
        <td>
            <input type="radio" name="chartSettings[barType]" value="stacked" <?php echo ($chartSettings['barType'] == 'stacked' ? 'checked' : ''); ?>><?php echo __('Stacked'); ?>
            <input type="radio" name="chartSettings[barType]" value="multi" <?php echo ($chartSettings['barType'] == 'multi' ? 'checked' : ''); ?>><?php echo __('Multi'); ?>
        </td>
    </tr>
    <?php } ?>

    <tr><td><label for="continuous"><?php echo __("Continuous image"); ?></label></td>
        <td>
            <input type="checkbox" name="chartSettings[continuous]" id="continuous" <?php echo ($chartSettings['continuous'] == 'on' ? 'checked="checked"' : ''); ?>>
        <?php echo PMA_showHint(PMA_sanitize(__('For compatibility reasons the chart image is segmented by default, select this to draw the whole chart in one image.'))) ?>
        </td>
    </tr>

    <tr><td><label for="fontSize"><?php echo __("Font size"); ?></label></td>
        <td><input type="text" name="chartSettings[fontSize]" id="fontSize" value="<?php echo (isset($chartSettings['fontSize']) ? htmlspecialchars($chartSettings['fontSize']) : ''); ?>" /></td>
    </tr>

    <?php if ($chartSettings['type'] == 'radar') { ?>
    <tr><td colspan="2">
        <p>
            <?php echo __('When drawing a radar chart all values are normalized to a range [0..10].'); ?>
        </p>
    </td></tr>
    <?php } ?>

    <tr><td colspan="2">
        <p>
            <?php echo __('Note that not every result table can be put to the chart. See <a href="./Documentation.html#faq6_29" target="Documentation">FAQ 6.29</a>'); ?>
        </p>
    </td></tr>

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
