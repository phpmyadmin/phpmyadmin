<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\GisVisualizationController;
use PhpMyAdmin\Core;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;

use function array_merge;

use const MYSQLI_TYPE_GEOMETRY;
use const MYSQLI_TYPE_VAR_STRING;

/**
 * @covers \PhpMyAdmin\Controllers\Table\GisVisualizationController
 */
class GisVisualizationControllerTest extends AbstractTestCase
{
    public function testGisVisualizationController(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['lang'] = 'en';
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $_GET['sql_query'] = null;
        $_POST['sql_query'] = 'SELECT * FROM `gis_all`';
        $_POST['pos'] = 0;
        $_REQUEST['pos'] = 0;
        $_REQUEST['unlim_num_rows'] = null;
        $_SESSION['tmpval'] = [];
        $_SESSION['tmpval']['max_rows'] = 'all';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SELECT * FROM `gis_all`',
            [['POINT', 'POINT(100 250)']],
            ['name', 'shape'],
            [
                new FieldMetadata(MYSQLI_TYPE_VAR_STRING, 0, (object) []),
                new FieldMetadata(MYSQLI_TYPE_GEOMETRY, 0, (object) []),
            ]
        );
        $dummyDbi->addResult(
            'SELECT ST_ASTEXT(`shape`) AS `shape`, ST_SRID(`shape`) AS `srid`'
            . ' FROM (SELECT * FROM `gis_all`) AS `temp_gis` LIMIT 0, 25',
            [['POINT(100 250)', '0']],
            ['shape', 'srid']
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $params = [
            'goto' => 'index.php?route=/database/structure&server=2&lang=en',
            'back' => 'index.php?route=/sql&server=2&lang=en',
            'sql_query' => 'SELECT * FROM `gis_all`',
            'sql_signature' => Core::signSqlQuery('SELECT * FROM `gis_all`'),
        ];
        $downloadUrl = Url::getFromRoute('/table/gis-visualization', array_merge($params, [
            'saveToFile' => true,
            'session_max_rows' => 25,
            'pos' => 0,
            'visualizationSettings[spatialColumn]' => 'shape',
            'visualizationSettings[labelColumn]' => '',
        ]));

        $template = new Template();
        $expected = $template->render('table/gis_visualization/gis_visualization', [
            'url_params' => $params,
            'download_url' => $downloadUrl,
            'label_candidates' => ['name'],
            'spatial_candidates' => ['shape'],
            'visualization_settings' => [
                'spatialColumn' => 'shape',
                'labelColumn' => '',
                'width' => '600',
                'height' => '450',
            ],
            'start_and_number_of_rows_fieldset' => [
                'pos' => 0,
                'unlim_num_rows' => 0,
                'rows' => 25,
                'sql_query' => 'SELECT * FROM `gis_all`',
            ],
            'visualization' => '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n"
                . '<svg version="1.1" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg"'
                . ' width="600" height="450"><g id="groupPanel"><circle cx="15" cy="240" r="3" name=""'
                . ' id="1234567890" class="point vector" fill="white" stroke="#B02EE0" stroke-width="2"/></g></svg>',
            'draw_ol' => 'function drawOpenLayers() {if (typeof ol !== "undefined") {var olCss ='
                . ' "js/vendor/openlayers/theme/ol.css";$(\'head\').append(\'<link rel="stylesheet" type="text/css"'
                . ' href=\'+olCss+\'>\');var vectorLayer = new ol.source.Vector({});var map = new ol.Map({target:'
                . ' \'openlayersmap\',layers: [new ol.layer.Tile({source: new ol.source.OSM()}),new ol.layer.'
                . 'Vector({source: vectorLayer})],view: new ol.View({center: ol.proj.fromLonLat([37.41, 8.82]),'
                . 'zoom: 4}),controls: [new ol.control.MousePosition({coordinateFormat: ol.coordinate.'
                . 'createStringXY(4),projection: \'EPSG:4326\'}),new ol.control.Zoom,new ol.control.Attribution'
                . ']});var fill = new ol.style.Fill({"color":"white"});var stroke = new ol.style.Stroke({"color"'
                . ':[176,46,224],"width":2});var style = new ol.style.Style({image: new ol.style.Circle({fill:'
                . ' fill,stroke: stroke,radius: 3}),fill: fill,stroke: stroke});var minLoc = [100, 250];var'
                . ' maxLoc = [100, 250];var ext = ol.extent.boundingExtent([minLoc, maxLoc]);ext = ol.proj.'
                . 'transformExtent(ext, ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'));map.getView().'
                . 'fit(ext, map.getSize());var point = new ol.Feature({geometry: (new ol.geom.Point([100,250]).'
                . 'transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\')))});point.setStyle(style);'
                . 'vectorLayer.addFeature(point);return map;}return undefined;}',
        ]);

        $response = new ResponseRenderer();
        (new GisVisualizationController($response, $template, $dbi))();
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
