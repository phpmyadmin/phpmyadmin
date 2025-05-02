<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Table\GisVisualizationController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;

use const MYSQLI_TYPE_GEOMETRY;
use const MYSQLI_TYPE_VAR_STRING;

#[CoversClass(GisVisualizationController::class)]
class GisVisualizationControllerTest extends AbstractTestCase
{
    public function testGisVisualizationController(): void
    {
        Current::$server = 2;
        Current::$lang = 'en';
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $_GET['sql_query'] = null;
        $_POST['sql_query'] = 'SELECT * FROM `gis_all`';
        $_POST['pos'] = 0;
        $_REQUEST['pos'] = 0;
        $_REQUEST['unlim_num_rows'] = null;
        $_SESSION['tmpval'] = [];
        $_SESSION['tmpval']['max_rows'] = 'all';
        UrlParams::$params = [];

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
        DatabaseInterface::$instance = $dbi;

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
                . ' width="600" height="450"><g><circle cx="300" cy="225" r="3" data-label=""'
                . ' id="1234567890" class="point vector" fill="white" stroke="#b02ee0" stroke-width="2"/></g></svg>',
            'open_layers_data' => [
                [
                    'geometry' => ['type' => 'Point', 'coordinates' => [100.0, 250.0], 'srid' => 0],
                    'style' => [
                        'circle' => [
                            'fill' => ['color' => 'white'],
                            'stroke' => ['color' => [176, 46, 224], 'width' => 2],
                            'radius' => 3,
                        ],
                    ],
                ],
            ],
        ]);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $responseRenderer = new ResponseRenderer();
        $controller = new GisVisualizationController(
            $responseRenderer,
            $template,
            $dbi,
            new DbTableExists($dbi),
            ResponseFactory::create(),
            new Config(),
        );
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame($expected, $responseRenderer->getHTMLResult());
    }
}
