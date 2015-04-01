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
require_once 'libraries/tbl_gis_visualization.lib.php';

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
$result = $GLOBALS['dbi']->tryQuery($sql_query);
// Get the meta data of results
$meta = $GLOBALS['dbi']->getFieldsMeta($result);

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

// If spatial column is not set, use first geometric column as spatial column
if (! isset($visualizationSettings['spatialColumn'])) {
    $visualizationSettings['spatialColumn'] = $spatialCandidates[0];
}

// Convert geometric columns from bytes to text.
$modified_query = PMA_GIS_modifyQuery($sql_query, $visualizationSettings);
$modified_result = $GLOBALS['dbi']->tryQuery($modified_query);

$data = array();
while ($row = $GLOBALS['dbi']->fetchAssoc($modified_result)) {
    $data[] = $row;
}

if (isset($_REQUEST['saveToFile'])) {
    $response->disable();
    $file_name = $visualizationSettings['spatialColumn'];
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

$html = PMA_getHtmlForGisVisualization(
    $url_params, $labelCandidates, $spatialCandidates,
    $visualizationSettings, $sql_query, $visualization, $svg_support,
    $data
);

$response->addHTML($html);

?>
