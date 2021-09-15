<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Controllers\Server\Status\Monitor\GeneralLogController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\Monitor\GeneralLogController
 */
class GeneralLogControllerTest extends AbstractTestCase
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

    public function testGeneralLog(): void
    {
        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 10,
            'argument' => 'argument argument2',
        ];

        $value2 = [
            'sql_text' => 'update sql_text',
            '#' => 11,
            'argument' => 'argument3 argument4',
        ];

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new GeneralLogController(
            $response,
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );

        $_POST['time_start'] = '0';
        $_POST['time_end'] = '10';
        $_POST['limitTypes'] = '1';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $ret = $response->getJSONResult();

        $resultRows = [
            $value,
            $value2,
        ];
        $resultSum = [
            'argument' => 10,
            'TOTAL' => 21,
            'argument3' => 11,
        ];

        $this->assertEquals(2, $ret['message']['numRows']);
        $this->assertEquals($resultRows, $ret['message']['rows']);
        $this->assertEquals($resultSum, $ret['message']['sum']);
    }
}
