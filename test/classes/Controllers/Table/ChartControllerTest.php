<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\ChartController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use const MYSQLI_NOT_NULL_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_DATE;
use const MYSQLI_TYPE_LONG;

/** @covers \PhpMyAdmin\Controllers\Table\ChartController */
class ChartControllerTest extends AbstractTestCase
{
    public function testChartController(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'table_for_chart';
        $GLOBALS['sql_query'] = 'SELECT * FROM `test_db`.`table_for_chart`;';
        $_POST = [
            'db' => 'test_db',
            'table' => 'table_for_chart',
            'printview' => '1',
            'sql_query' => 'SELECT * FROM `test_db`.`table_for_chart`;',
            'single_table' => 'true',
            'unlim_num_rows' => '4',
        ];
        $_REQUEST['unlim_num_rows'] = '4';
        $_REQUEST['pos'] = '0';

        $fieldsMeta = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'flags' => MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                'name' => 'id',
                'table' => 'table_for_chart',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'flags' => MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                'name' => 'amount',
                'table' => 'table_for_chart',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_DATE,
                'flags' => MYSQLI_NOT_NULL_FLAG,
                'name' => 'date',
                'table' => 'table_for_chart',
            ]),
        ];

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SHOW TABLES LIKE \'table_for_chart\';', [['table_for_chart']]);
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SELECT * FROM `test_db`.`table_for_chart`;',
            [['1', '7', '2022-02-08'], ['2', '5', '2022-02-09'], ['3', '3', '2022-02-10'], ['4', '9', '2022-02-11']],
            ['id', 'amount', 'date'],
            $fieldsMeta,
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $response = new ResponseRenderer();
        $template = new Template();
        $expected = $template->render('table/chart/tbl_chart', [
            'url_params' => [
                'db' => 'test_db',
                'table' => 'table_for_chart',
                'goto' => 'index.php?route=/sql&lang=en',
                'back' => 'index.php?route=/table/sql&lang=en',
                'reload' => 1,
            ],
            'keys' => ['id', 'amount', 'date'],
            'fields_meta' => $fieldsMeta,
            'table_has_a_numeric_column' => true,
            'start_and_number_of_rows_fieldset' => [
                'pos' => 0,
                'unlim_num_rows' => 4,
                'rows' => 25,
                'sql_query' => 'SELECT * FROM `test_db`.`table_for_chart`;',
            ],
        ]);

        (new ChartController($response, $template, $dbi))($this->createStub(ServerRequest::class));
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
