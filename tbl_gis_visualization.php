<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the GIS visualizations.
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';

// Runs common work
require_once 'libraries/db_common.inc.php';
$url_params['goto'] = $cfg['DefaultTabDatabase'];
$url_params['back'] = 'sql.php';

// Import visualization functions
require_once 'libraries/gis_visualization.lib.php';

$response = PMA_Response::getInstance();
// Throw error if no sql query is set
if (! isset($sql_query) || $sql_query == '') {
    $response->isSuccess(false);
    $response->addHTML(
        PMA_Message::error(__('No SQL query was set to fetch data.'))
    );
    exit;
}

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

if (! isset($visualizationSettings['labelColumn']) && isset($labelCandidates[0])) {
    $visualizationSettings['labelColumn'] = '';
}

// If spatial column is not set, use first geometric colum as spatial column
if (! isset($visualizationSettings['spatialColumn'])) {
    $visualizationSettings['spatialColumn'] = $spatialCandidates[0];
}

// Convert geometric columns from bytes to text.
$modified_query = PMA_GIS_modifyQuery($sql_query, $visualizationSettings);
$modified_result = PMA_DBI_try_query($modified_query);

$data = array();
while ($row = PMA_DBI_fetch_assoc($modified_result)) {
    $data[] = $row;
}

if (isset($_REQUEST['saveToFile'])) {
    $response->disable();
    $file_name = $_REQUEST['fileName'];
    if ($file_name == '') {
        $file_name = $visualizationSettings['spatialColumn'];
    }

    $save_format = $_REQUEST['fileFormat'];
    PMA_GIS_saveToFile($data, $visualizationSettings, $save_format, $file_name);
    exit();
}

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('openlayers/OpenLayers.js');
$scripts->addFile('jquery/jquery.svg.js');
$scripts->addFile('tbl_gis_visualization.js');
$scripts->addFile('OpenStreetMap.js');

// If all the rows contain SRID, use OpenStreetMaps on the initial loading.
if (! isset($_REQUEST['displayVisualization'])) {
    $visualizationSettings['choice'] = 'useBaseLayer';
    foreach ($data as $row) {
        if ($row['srid'] == 0) {
            unset($visualizationSettings['choice']);
            break;
        }
    }
}

$svg_support = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER <= 8)
    ? false : true;
$format = $svg_support ? 'svg' : 'png';

// get the chart and settings after chart generation
$visualization = PMA_GIS_visualizationResults(
    $data, $visualizationSettings, $format
);

/**
 * Displays the page
 */
?>
<!-- Display visualization options -->
<div id="div_view_options">
<fieldset>
    <legend><?php echo __('Display GIS Visualization'); ?></legend>
    <div style="width: 400px; float: left;">
    <form method="post" action="tbl_gis_visualization.php">
    <?php echo PMA_generate_common_hidden_inputs($url_params); ?>
    <table class="gis_table">
    <tr><td><label for="labelColumn"><?php echo __("Label column"); ?></label></td>
        <td><select name="visualizationSettings[labelColumn]" id="labelColumn">
            <option value=""><?php echo __("-- None --"); ?></option>
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

    <tr><td><label for="spatial Column"><?php echo __("Spatial column"); ?></label></td>
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
        <td class="button"><input type="submit" name="displayVisualizationBtn" value="<?php echo __('Redraw'); ?>" /></td>
    </tr>
<?php
if (! $GLOBALS['PMA_Config']->isHttps()) {
    ?>
    <tr><td class="choice" colspan="2">
        <input type="checkbox" name="visualizationSettings[choice]" id="choice" value="useBaseLayer"
    <?php
    if (isset($visualizationSettings['choice'])) {
        echo(' checked="checked"');
    }
    ?>
        />
        <label for="choice"><?php echo __("Use OpenStreetMaps as Base Layer"); ?></label>
    </td></tr>
    <?php
}
?>
    </table>
    <input type="hidden" name="displayVisualization" value="redraw">
    <input type="hidden" name="sql_query" value="<?php echo htmlspecialchars($sql_query); ?>" />
    </form>
    </div>

    <div  style="float:left;">
    <form method="post" class="disableAjax"  action="tbl_gis_visualization.php">
    <?php echo PMA_generate_common_hidden_inputs($url_params); ?>
    <table class="gis_table">
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
        <td class="button"><input type="submit" name="saveToFileBtn" value="<?php echo __('Download'); ?>" /></td>
    </tr>
    </table>
    <input type="hidden" name="saveToFile" value="download">
    <input type="hidden" name="sql_query" value="<?php echo htmlspecialchars($sql_query); ?>" />
    </form>
    </div>

    <div style="clear:both;">&nbsp;</div>

    <div id="placeholder" style="width:<?php echo htmlspecialchars($visualizationSettings['width']); ?>px;height:<?php echo htmlspecialchars($visualizationSettings['height']); ?>px;">
        <?php echo $visualization; ?>
    </div>
    <div id="openlayersmap"></div>
    <input type="hidden" id="pmaThemeImage" value="<?php echo($GLOBALS['pmaThemeImage']); ?>" />
    <script language="javascript" type="text/javascript">
        function drawOpenLayers()
        {
            <?php
            if (! $GLOBALS['PMA_Config']->isHttps()) {
                echo (PMA_GIS_visualizationResults($data, $visualizationSettings, 'ol'));
            }
            ?>
        }
    </script>
</fieldset>
</div>
