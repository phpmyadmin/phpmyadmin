<?php
/**
 * handles creation of the GIS visualizations
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

$GLOBALS['js_include'][] = 'flot/jquery.flot.js';
$GLOBALS['js_include'][] = 'flot/jquery.flot.navigate.js';
$GLOBALS['js_include'][] = 'tbl_gis_visualization.js';

// Runs common work
 require './libraries/db_common.inc.php';
$url_params['goto'] = $cfg['DefaultTabDatabase'];
$url_params['back'] = 'sql.php';


// Import chart functions
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
if(! isset($visualizationSettings['labelColumn']) && isset($labelCandidates[0])) {
    $visualizationSettings['labelColumn'] = $labelCandidates[0];
}

// If spatial column is not set, use first geometric colum as spatial column
if(! isset($visualizationSettings['spatialColumn'])) {
    $visualizationSettings['spatialColumn'] = $spatialCandidates[0];
}

// Convert geometric columns from bytes to text.
$modified_query = PMA_GIS_modify_query($sql_query, $visualizationSettings);
$modified_result = PMA_DBI_try_query($modified_query);

$data = array();
while ($row = PMA_DBI_fetch_assoc($modified_result)) {
    $data[] = $row;
}

// get the chart and settings after chart generation
$visualization = PMA_GIS_visualization_results($data, $visualizationSettings);

if (! empty($visualization)) {
    $message = PMA_Message::success(__('GIS visualization generated successfully.'));
}
else {
    $message = PMA_Message::error(__('Some error occured while generating the GIS visualization'));
}

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

    <div id="placeholder"></div>
    <script language="javascript" type="text/javascript">
    $(function () {
        <?php echo $visualization; ?>

        var placeholder = $("#placeholder");
        var plot = $.plot(placeholder, data, options);

        // add zoom out button
        $('<div class="button" style="right:20px;top:20px">zoom out</div>').appendTo(placeholder).click(function (e) {
            e.preventDefault();
            plot.zoomOut();
        });

        // helper function for placing panning arrows
        function addArrow(dir, right, top, offset) {
            $('<img class="button" src="<?php echo($GLOBALS['pmaThemeImage'])?>arrow-' + dir + '.gif" style="right:' + right + 'px;top:' + top + 'px">').appendTo(placeholder).click(function (e) {
                e.preventDefault();
                plot.pan(offset);
            });
        }

        // add panning arrows
        addArrow('left', 55, 60, { left: -100 });
        addArrow('right', 25, 60, { left: 100 });
        addArrow('up', 40, 45, { top: -100 });
        addArrow('down', 40, 75, { top: 100 });
    });
    </script>

    <input type="hidden" name="sql_query" id="sql_query" value="<?php echo htmlspecialchars($sql_query); ?>" />

    <table>
    <tr><td><label for="width"><?php echo __("Width"); ?></label></td>
        <td><input type="text" name="visualizationSettings[width]" id="width" value="<?php echo (isset($visualizationSettings['width']) ? htmlspecialchars($visualizationSettings['width']) : ''); ?>" /></td>
    </tr>

    <tr><td><label for="height"><?php echo __("Height"); ?></label></td>
        <td><input type="text" name="visualizationSettings[height]" id="height" value="<?php echo (isset($visualizationSettings['height']) ? htmlspecialchars($visualizationSettings['height']) : ''); ?>" /></td>
    </tr>

    <tr><td><label for="labelColumn"><?php echo __("Label Column"); ?></label></td>
        <td><select name="visualizationSettings[labelColumn]" id="labelColumn" style="min-width:155px;">
        <?php
            foreach($labelCandidates as $labelCandidate) {
                echo('<option value="' . htmlspecialchars($labelCandidate) . '"');
                if($labelCandidate == $visualizationSettings['labelColumn']) {
                    echo(' selected="selected"');
                }
                echo('>' . htmlspecialchars($labelCandidate) . '</option>');
            }
        ?>
        </select></td>
    </tr>

    <tr><td><label for="spatial Column"><?php echo __("spatial Column"); ?></label></td>
        <td><select name="visualizationSettings[spatialColumn]" id="spatialColumn" style="min-width:155px;">
        <?php
            foreach($spatialCandidates as $spatialCandidate) {
                echo('<option value="' . htmlspecialchars($spatialCandidate) . '"');
                if($spatialCandidate == $visualizationSettings['spatialColumn']) {
                    echo(' selected="selected"');
                }
                echo('>' . htmlspecialchars($spatialCandidate) . '</option>');
            }
        ?>
        </select></td>
    </tr>
    </table>

</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="displayVisualization" value="<?php echo __('Redraw'); ?>" />
</fieldset>
</form>
</div>
<?php
/**
 * Displays the footer
 */
require './libraries/footer.inc.php';

?>
