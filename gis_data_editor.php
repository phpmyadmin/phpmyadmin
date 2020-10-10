<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Editor for Geometry data types.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Template $template */
$template = $containerBuilder->get('template');

if (! isset($_POST['field'])) {
    Util::checkParameters(['field']);
}

// Get data if any posted
$gis_data = [];
if (Core::isValid($_POST['gis_data'], 'array')) {
    $gis_data = $_POST['gis_data'];
}

$gis_types = [
    'POINT',
    'MULTIPOINT',
    'LINESTRING',
    'MULTILINESTRING',
    'POLYGON',
    'MULTIPOLYGON',
    'GEOMETRYCOLLECTION',
];

// Extract type from the initial call and make sure that it's a valid one.
// Extract from field's values if available, if not use the column type passed.
if (! isset($gis_data['gis_type'])) {
    if (isset($_POST['type']) && $_POST['type'] != '') {
        $gis_data['gis_type'] = mb_strtoupper($_POST['type']);
    }
    if (isset($_POST['value']) && trim($_POST['value']) != '') {
        $start = (substr($_POST['value'], 0, 1) == "'") ? 1 : 0;
        $gis_data['gis_type'] = mb_substr(
            $_POST['value'],
            $start,
            mb_strpos($_POST['value'], "(") - $start
        );
    }
    if (! isset($gis_data['gis_type'])
        || (! in_array($gis_data['gis_type'], $gis_types))
    ) {
        $gis_data['gis_type'] = $gis_types[0];
    }
}
$geom_type = $gis_data['gis_type'];

// Generate parameters from value passed.
$gis_obj = GisFactory::factory($geom_type);
if (isset($_POST['value'])) {
    $gis_data = array_merge(
        $gis_data,
        $gis_obj->generateParams($_POST['value'])
    );
}

// Generate Well Known Text
$srid = (isset($gis_data['srid']) && $gis_data['srid'] != '') ? $gis_data['srid'] : 0;
$wkt = $gis_obj->generateWkt($gis_data, 0);
$wkt_with_zero = $gis_obj->generateWkt($gis_data, 0, '0');
$result = "'" . $wkt . "'," . $srid;

// Generate SVG based visualization
$visualizationSettings = [
    'width' => 450,
    'height' => 300,
    'spatialColumn' => 'wkt',
    'mysqlVersion' => $GLOBALS['dbi']->getVersion(),
    'isMariaDB' => $GLOBALS['dbi']->isMariaDB(),
];
$data = [
    [
        'wkt' => $wkt_with_zero,
        'srid' => $srid,
    ],
];
$visualization = GisVisualization::getByData($data, $visualizationSettings)
    ->toImage('svg');

$open_layers = GisVisualization::getByData($data, $visualizationSettings)
    ->asOl();

// If the call is to update the WKT and visualization make an AJAX response
if (isset($_POST['generate']) && $_POST['generate'] == true) {
    $extra_data = [
        'result'        => $result,
        'visualization' => $visualization,
        'openLayers'    => $open_layers,
    ];
    $response = Response::getInstance();
    $response->addJSON($extra_data);
    exit;
}

$geom_count = 1;
if ($geom_type == 'GEOMETRYCOLLECTION') {
    $geom_count = isset($gis_data[$geom_type]['geom_count'])
        ? intval($gis_data[$geom_type]['geom_count']) : 1;
    if (isset($gis_data[$geom_type]['add_geom'])) {
        $geom_count++;
    }
}

$templateOutput = $template->render('gis_data_editor_form', [
    'width' => $visualizationSettings['width'],
    'height' => $visualizationSettings['height'],
    'pma_theme_image' => $GLOBALS['pmaThemeImage'],
    'field' => $_POST['field'],
    'input_name' => $_POST['input_name'],
    'srid' => $srid,
    'visualization' => $visualization,
    'open_layers' => $open_layers,
    'gis_types' => $gis_types,
    'geom_type' => $geom_type,
    'geom_count' => $geom_count,
    'gis_data' => $gis_data,
    'result' => $result,
]);
Response::getInstance()->addJSON('gis_editor', $templateOutput);
