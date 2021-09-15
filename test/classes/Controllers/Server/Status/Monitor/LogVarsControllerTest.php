<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Controllers\Server\Status\Monitor\LogVarsController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\Monitor\LogVarsController
 */
class LogVarsControllerTest extends AbstractTestCase
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

    public function testLogVars(): void
    {
        $value = [
            'general_log' => 'OFF',
            'log_output' => 'FILE',
            'long_query_time' => '10.000000',
            'slow_query_log' => 'OFF',
        ];

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new LogVarsController(
            $response,
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );

        $_POST['varName'] = 'varName';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $ret = $response->getJSONResult();

        $this->assertEquals($value, $ret['message']);
    }
}
