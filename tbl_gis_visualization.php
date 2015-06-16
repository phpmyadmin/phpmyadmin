<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the GIS visualizations.
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once './libraries/gis/GIS_Visualization.class.php';
require_once './libraries/gis/GIS_Factory.class.php';

// Runs common work
require_once 'libraries/db_common.inc.php';
$url_params['goto'] = PMA_Util::getScriptNameForOption(
    $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
);
$url_params['back'] = 'sql.php';

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
$pos = isset($_REQUEST['pos']) ? $_REQUEST['pos'] : $_SESSION['tmpval']['pos'];
if (isset($_REQUEST['session_max_rows'])) {
    $rows = $_REQUEST['session_max_rows'];
} else {
    if ($_SESSION['tmpval']['max_rows'] != 'all') {
        $rows = $_SESSION['tmpval']['max_rows'];
    } else {
        $rows = $GLOBALS['cfg']['MaxRows'];
    }
}

if (isset($_REQUEST['saveToFile'])) {
    $response->disable();
    $file_name = $visualizationSettings['spatialColumn'];
    $save_format = $_REQUEST['fileFormat'];
//    PMA_GIS_saveToFile($data, $visualizationSettings, $save_format, $file_name);
    $visualization = PMA_GIS_Visualization::get($sql_query, $visualizationSettings, $rows, $pos);
    if ($format == 'svg') {
        $visualization->toFileAsSvg($fileName);
    } elseif ($format == 'png') {
        $visualization->toFileAsPng($fileName);
    } elseif ($format == 'pdf') {
        $visualization->toFileAsPdf($fileName);
    }
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
    $visualization = PMA_GIS_Visualization::get($sql_query, $visualizationSettings, $rows, $pos);
    if ($visualization->hasSrid())
        unset($visualizationSettings['choice']);
    $visualizationSettings['choice'] = 'useBaseLayer';
}

$svgSupport = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER <= 8)
    ? false : true;
$format = $svgSupport ? 'svg' : 'png';

$visualization = PMA_GIS_Visualization::get($sql_query, $visualizationSettings, $rows, $pos);
if ($visualizationSettings != null) {
    foreach ($visualization->getSettings() as $setting => $val) {
        if (! isset($visualizationSettings[$setting])) {
            $visualizationSettings[$setting] = $val;
        }
    }
}

$result = null;
if ($format == 'svg') {
    $result = $visualization->asSvg();
} elseif ($format == 'png') {
    $result = $visualization->asPng();
} elseif ($format == 'ol') {
    $result = $visualization->asOl();
}

/**
 * Displays the page
 */
$url_params['sql_query'] = $sql_query;
$downloadUrl = 'tbl_gis_visualization.php' . PMA_URL_getCommon($url_params)
    . '&saveToFile=true';
$html = PMA\Template::get('gis_visualization/gis_visualization')->render(
    array(
        'url_params' => $url_params,
        'downloadUrl' => $downloadUrl,
        'labelCandidates' => $labelCandidates,
        'spatialCandidates' => $spatialCandidates,
        'visualizationSettings' => $visualizationSettings,
        'sql_query' => $sql_query,
        'visualization' => $result,
        'svgSupport' => $svgSupport,
        'drawOl' => $visualization->asOl()
    )
);

$response->addHTML($html);
