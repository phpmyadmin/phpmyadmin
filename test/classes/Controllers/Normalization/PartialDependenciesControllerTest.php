<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\PartialDependenciesController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\PartialDependenciesController */
class PartialDependenciesControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT COUNT(*) FROM (SELECT * FROM `test_table` LIMIT 500) as dt;', [['0']], ['dt']);
        $dbiDummy->addResult(
            'SELECT COUNT(DISTINCT `id`) as \'`id`_cnt\', COUNT(DISTINCT `name`) as \'`name`_cnt\', COUNT(DISTINCT `datetimefield`) as \'`datetimefield`_cnt\' FROM (SELECT * FROM `test_table` LIMIT 500) as dt;',
            [],
            ['`id`_cnt', '`name`_cnt', '`datetimefield`_cnt', '`datetimefield`_cnt', 'dt'],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $controller = new PartialDependenciesController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($this->createStub(ServerRequest::class));

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertSame(
            'This list is based on a subset of the table\'s data and is not necessarily accurate. <div class="dependencies_box"><p class="d-block m-1">No partial dependencies found!</p></div>',
            $response->getHTMLResult(),
        );
        // phpcs:enable
    }
}
