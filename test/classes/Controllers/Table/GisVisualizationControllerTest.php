<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\GisVisualizationController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;

use const MYSQLI_TYPE_GEOMETRY;
use const MYSQLI_TYPE_VAR_STRING;

/** @covers \PhpMyAdmin\Controllers\Table\GisVisualizationController */
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
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_VAR_STRING]),
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_GEOMETRY]),
            ],
        );
        $dummyDbi->addResult(
            'SELECT ST_ASTEXT(`shape`) AS `shape`, ST_SRID(`shape`) AS `srid`'
            . ' FROM (SELECT * FROM `gis_all`) AS `temp_gis` LIMIT 25',
            [['POINT(100 250)', '0']],
            ['shape', 'srid'],
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $params = [
            'goto' => 'index.php?route=/database/structure&server=2&lang=en',
            'back' => 'index.php?route=/sql&server=2&lang=en',
            'sql_query' => 'SELECT * FROM `gis_all`',
            'sql_signature' => Core::signSqlQuery('SELECT * FROM `gis_all`'),
        ];
        $downloadParams = [
            'saveToFile' => true,
            'session_max_rows' => 25,
            'pos' => 0,
            'visualizationSettings[spatialColumn]' => 'shape',
            'visualizationSettings[labelColumn]' => null,
        ];
        $downloadUrl = Url::getFromRoute('/table/gis-visualization', $downloadParams + $params);

        $template = new Template();
        $expected = $template->render('table/gis_visualization/gis_visualization', [
            'url_params' => $params,
            'download_url' => $downloadUrl,
            'label_candidates' => ['name'],
            'spatial_candidates' => ['shape'],
            'spatialColumn' => 'shape',
            'labelColumn' => null,
            'width' => 600,
            'height' => 450,
            'start_and_number_of_rows_fieldset' => [
                'pos' => 0,
                'unlim_num_rows' => 0,
                'rows' => 25,
                'sql_query' => 'SELECT * FROM `gis_all`',
            ],
            'visualization' => '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n"
                . '<svg version="1.1" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg"'
                . ' width="600" height="450"><g id="groupPanel"><circle cx="300" cy="225" r="3" name=""'
                . ' id="1234567890" class="point vector" fill="white" stroke="#b02ee0" stroke-width="2"/></g></svg>',
            'draw_ol' => 'function drawOpenLayers() {if (typeof ol === "undefined") { return undefined; }'
                . 'var olCss = "js/vendor/openlayers/theme/ol.css";$(\'head\').append(\'<link rel="stylesheet" '
                . 'type="text/css" href=\'+olCss+\'>\');var vectorSource = new ol.source.Vector({});'
                . 'var map = new ol.Map({target: \'openlayersmap\',layers: [new ol.layer.Tile({source: '
                . 'new ol.source.OSM()}),new ol.layer.Vector({source: vectorSource})],view: new ol.View({center: '
                . '[0, 0],zoom: 4}),controls: [new ol.control.MousePosition({coordinateFormat: ol.coordinate.'
                . 'createStringXY(4),projection: \'EPSG:4326\'}),new ol.control.Zoom,new ol.control.Attribution]});'
                . 'var feature = new ol.Feature(new ol.geom.Point([100,250]).transform(\'EPSG:4326\', '
                . '\'EPSG:3857\'));feature.setStyle(new ol.style.Style({image: new ol.style.Circle({fill: '
                . 'new ol.style.Fill({"color":"white"}),stroke: new ol.style.Stroke({"color":[176,46,224],'
                . '"width":2}),radius: 3})}));vectorSource.addFeature(feature);var extent = vectorSource.getExtent();'
                . 'if (!ol.extent.isEmpty(extent)) {map.getView().fit(extent, {padding: [20, 20, 20, 20]});}'
                . 'return map;}',
        ]);

        $response = new ResponseRenderer();
        (new GisVisualizationController($response, $template, $dbi))($this->createStub(ServerRequest::class));
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
