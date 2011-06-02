<?php
/**
 * handles creation of the GIS visualizations.
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

require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'jquery/jquery.svg.js';
$GLOBALS['js_include'][] = 'jquery/jquery.mousewheel.js';
$GLOBALS['js_include'][] = 'jquery/jquery.event.drag-2.0.min.js';
$GLOBALS['js_include'][] = 'tbl_gis_visualization.js';

// Runs common work
require './libraries/db_common.inc.php';
$url_params['goto'] = $cfg['DefaultTabDatabase'];
$url_params['back'] = 'sql.php';


// Import visualization functions
require_once './libraries/gis_visualization.lib.php';


// Execute the query and return the result
$result = PMA_DBI_try_query($sql_query);
// Get the meta data of results
$meta = PMA_DBI_get_fields_meta($result);

// Find the candidate fields for label column and spatial column
$labelCandidates = array(); $spatialCandidates = array();
foreach ($meta as $column_meta) {
    if ($column_meta->type == 'geometry') {
        $spatialCandidates[] = $column_meta->name;
    } else {
        $labelCandidates[] = $column_meta->name;
    }
}

// Get settings if any posted
$visualizationSettings = array();
if (PMA_isValid($_REQUEST['visualizationSettings'], 'array')) {
    $visualizationSettings = $_REQUEST['visualizationSettings'];
}

// If label column is not set, use first non-geometric colum as label column
if (! isset($visualizationSettings['labelColumn']) && isset($labelCandidates[0])) {
    $visualizationSettings['labelColumn'] = $labelCandidates[0];
}

// If spatial column is not set, use first geometric colum as spatial column
if (! isset($visualizationSettings['spatialColumn'])) {
    $visualizationSettings['spatialColumn'] = $spatialCandidates[0];
}

// Convert geometric columns from bytes to text.
$modified_query = PMA_GIS_modify_query($sql_query, $visualizationSettings);
$modified_result = PMA_DBI_try_query($modified_query);

$data = array();
while ($row = PMA_DBI_fetch_assoc($modified_result)) {
    $data[] = $row;
}

if (isset($_REQUEST['saveToFile'])) {
    $file_name = $_REQUEST['fileName'];
    if ($file_name == '') {
        $file_name = $visualizationSettings['spatialColumn'];
    }

    $save_format = $_REQUEST['fileFormat'];
    PMA_GIS_save_to_file($data, $visualizationSettings, $save_format, $file_name);
    exit();
}

$svg_support = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER <= 8) ? false : true;
$format = $svg_support ? 'svg' : 'png';

// get the chart and settings after chart generation
$visualization = PMA_GIS_visualization_results($data, $visualizationSettings, $format);

/**
 * Displays the page
 */
?>
<!-- Display visulalization options -->
<div id="div_view_options">
<form method="post" action="tbl_gis_visualization.php">
<?php echo PMA_generate_common_hidden_inputs($url_params); ?>
<fieldset>
    <legend><?php echo __('Display GIS Visualization'); ?></legend>
    <div id="placeholder" style="width:<?php echo($visualizationSettings['width']); ?>px;height:<?php echo($visualizationSettings['height']); ?>px;border:1px solid #484;float:right">
        <?php echo $visualization; ?>
    </div>
<?php
if ($format == 'svg') {
?>
    <script language="javascript" type="text/javascript">

    $(document).ready(function(){
        var $placeholder = $('#placeholder');
        // add zoom out button
        $('<div class="button" id="zoom_out"><?php echo __("zoom out"); ?></div>').appendTo($placeholder);
        // add panning arrows
        $('<img class="button" id="left_arrow" src="<?php echo($GLOBALS['pmaThemeImage']); ?>arrow-left.gif">').appendTo($placeholder);
        $('<img class="button" id="right_arrow" src="<?php echo($GLOBALS['pmaThemeImage']); ?>arrow-right.gif">').appendTo($placeholder);
        $('<img class="button" id="up_arrow" src="<?php echo($GLOBALS['pmaThemeImage']); ?>arrow-up.gif">').appendTo($placeholder);
        $('<img class="button" id="down_arrow" src="<?php echo($GLOBALS['pmaThemeImage']); ?>arrow-down.gif">').appendTo($placeholder);
    });

    </script>
<?php
}
?>
    <input type="hidden" name="sql_query" id="sql_query" value="<?php echo htmlspecialchars($sql_query); ?>" />

    <table class="gis_table">
    <tr><td><label for="width"><?php echo __("Width"); ?></label></td>
        <td><input type="text" name="visualizationSettings[width]" id="width" value="<?php echo (isset($visualizationSettings['width']) ? htmlspecialchars($visualizationSettings['width']) : ''); ?>" /></td>
    </tr>

    <tr><td><label for="height"><?php echo __("Height"); ?></label></td>
        <td><input type="text" name="visualizationSettings[height]" id="height" value="<?php echo (isset($visualizationSettings['height']) ? htmlspecialchars($visualizationSettings['height']) : ''); ?>" /></td>
    </tr>

    <tr><td><label for="labelColumn"><?php echo __("Label Column"); ?></label></td>
        <td><select name="visualizationSettings[labelColumn]" id="labelColumn">
        <?php
            foreach ($labelCandidates as $labelCandidate) {
                echo('<option value="' . htmlspecialchars($labelCandidate) . '"');
                if ($labelCandidate == $visualizationSettings['labelColumn']) {
                    echo(' selected="selected"');
                }
                echo('>' . htmlspecialchars($labelCandidate) . '</option>');
            }
        ?>
        </select></td>
    </tr>

    <tr><td><label for="spatial Column"><?php echo __("Spatial Column"); ?></label></td>
        <td><select name="visualizationSettings[spatialColumn]" id="spatialColumn">
        <?php
            foreach ($spatialCandidates as $spatialCandidate) {
                echo('<option value="' . htmlspecialchars($spatialCandidate) . '"');
                if ($spatialCandidate == $visualizationSettings['spatialColumn']) {
                    echo(' selected="selected"');
                }
                echo('>' . htmlspecialchars($spatialCandidate) . '</option>');
            }
        ?>
        </select></td>
    </tr>
    <tr><td></td>
        <td class="button"><input type="submit" name="displayVisualization" value="<?php echo __('Redraw'); ?>" /></td>
    </tr>
    <tr><td class="save"><?php echo __("Save to file"); ?></td></tr>
    <tr><td><label for="fileName"><?php echo __("File name"); ?></label></td>
        <td><input type="text" name="fileName" id="fileName" /></td>
    </tr>
    <tr><td><label for="fileFormat"><?php echo __("Format"); ?></label></td>
        <td><select name="fileFormat" id="fileFormat">
            <option value="png">PNG</option>
            <option value="pdf">PDF</option>
            <?php
            if ($svg_support) {
                echo ('<option value="svg" selected="selected">SVG</option>');
            }
            ?>
        </select></td>
    </tr>
    <tr><td></td>
        <td class="button"><input type="submit" name="saveToFile" value="<?php echo __('Save'); ?>" /></td>
    </tr>
    </table>
</fieldset>
</form>
</div>
<?php
/**
 * Displays the footer
 */
require './libraries/footer.inc.php';

?>
