<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\Monitor\QueryAnalyzerController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(QueryAnalyzerController::class)]
class QueryAnalyzerControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testQueryAnalyzer(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['host'] = 'localhost';
        $GLOBALS['cached_affected_rows'] = 'cached_affected_rows';
        SessionCache::set('profiling_supported', true);

        $value = ['sql_text' => 'insert sql_text', '#' => 10, 'argument' => 'argument argument2'];

        $response = new ResponseRenderer();

        $dummyDbi = new DbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $statusData = new Data($dbi, $config);
        $controller = new QueryAnalyzerController($response, new Template(), $statusData, new Monitor($dbi), $dbi);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'ajax_request' => 'true',
                'database' => 'database',
                'query' => 'query',
            ]);

        $dummyDbi->addSelectDb('mysql');
        $dummyDbi->addSelectDb('database');
        $controller($request);
        $dummyDbi->assertAllSelectsConsumed();
        $ret = $response->getJSONResult();

        self::assertSame('cached_affected_rows', $ret['message']['affectedRows']);
        self::assertSame(
            [],
            $ret['message']['profiling'],
        );
        self::assertEquals(
            [$value],
            $ret['message']['explain'],
        );
    }
}
