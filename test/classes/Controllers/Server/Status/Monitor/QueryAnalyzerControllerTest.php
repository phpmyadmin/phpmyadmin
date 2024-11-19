<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Controllers\Server\Status\Monitor\QueryAnalyzerController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Utils\SessionCache;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\Monitor\QueryAnalyzerController
 */
class QueryAnalyzerControllerTest extends AbstractTestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        $this->data = new Data();
    }

    public function testQueryAnalyzer(): void
    {
        global $cached_affected_rows;

        $cached_affected_rows = 'cached_affected_rows';
        SessionCache::set('profiling_supported', true);

        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 10,
            'argument' => 'argument argument2',
        ];

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new QueryAnalyzerController(
            $response,
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );

        $_POST['database'] = 'database';
        $_POST['query'] = 'query';

        $this->dummyDbi->addSelectDb('mysql');
        $this->dummyDbi->addSelectDb('database');
        $controller();
        $this->assertAllSelectsConsumed();
        $ret = $response->getJSONResult();

        self::assertSame('cached_affected_rows', $ret['message']['affectedRows']);
        self::assertSame([], $ret['message']['profiling']);
        self::assertSame([$value], $ret['message']['explain']);
    }
}
