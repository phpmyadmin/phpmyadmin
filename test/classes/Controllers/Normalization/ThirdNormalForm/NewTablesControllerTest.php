<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\ThirdNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\ThirdNormalForm\NewTablesController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function json_encode;

/** @covers \PhpMyAdmin\Controllers\Normalization\ThirdNormalForm\NewTablesController */
class NewTablesControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $tables = json_encode([
            'test_table' => ['event', 'event', 'event', 'event', 'NameOfVenue', 'event', 'period', 'event', 'event'],
        ]);
        $pd = json_encode([
            '' => [],
            'event' => ['TypeOfEvent', 'period', 'Start_time', 'NameOfVenue', 'LocationOfVenue'],
            'NameOfVenue' => ['DateOfEvent'],
            'period' => ['NumberOfGuests'],
        ]);

        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['tables', null, $tables], ['pd', null, $pd]]);

        $controller = new NewTablesController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($request);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertSame([
            'html' => '<p><b>In order to put the original table \'test_table\' into Third normal form we need to create the following tables:</b></p><p><input type="text" name="test_table" value="test_table">( <u>event</u>, TypeOfEvent, period, Start_time, NameOfVenue, LocationOfVenue )<p><input type="text" name="table2" value="table2">( <u>NameOfVenue</u>, DateOfEvent )<p><input type="text" name="table3" value="table3">( <u>period</u>, NumberOfGuests )',
            'newTables' => [
                'test_table' => [
                    'test_table' => [
                        'pk' => 'event',
                        'nonpk' => 'TypeOfEvent, period, Start_time, NameOfVenue, LocationOfVenue',
                    ],
                    'table2' => ['pk' => 'NameOfVenue', 'nonpk' => 'DateOfEvent'],
                    'table3' => ['pk' => 'period', 'nonpk' => 'NumberOfGuests'],
                ],
            ],
            'success' => true,
        ], $response->getJSONResult());
        // phpcs:enable
    }
}
