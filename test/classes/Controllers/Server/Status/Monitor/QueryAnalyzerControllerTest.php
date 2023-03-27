<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Controllers\Server\Status\Monitor\QueryAnalyzerController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Utils\SessionCache;

/** @covers \PhpMyAdmin\Controllers\Server\Status\Monitor\QueryAnalyzerController */
class QueryAnalyzerControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
    }

    public function testQueryAnalyzer(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cached_affected_rows'] = 'cached_affected_rows';
        SessionCache::set('profiling_supported', true);

        $value = ['sql_text' => 'insert sql_text', '#' => 10, 'argument' => 'argument argument2'];

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $dummyDbi = new DbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $statusData = new Data($dbi, $GLOBALS['config']);
        $controller = new QueryAnalyzerController($response, new Template(), $statusData, new Monitor($dbi), $dbi);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['database', '', 'database'], ['query', '', 'query']]);

        $dummyDbi->addSelectDb('mysql');
        $dummyDbi->addSelectDb('database');
        $controller($request);
        $dummyDbi->assertAllSelectsConsumed();
        $ret = $response->getJSONResult();

        $this->assertEquals('cached_affected_rows', $ret['message']['affectedRows']);
        $this->assertEquals(
            [],
            $ret['message']['profiling'],
        );
        $this->assertEquals(
            [$value],
            $ret['message']['explain'],
        );
    }
}
