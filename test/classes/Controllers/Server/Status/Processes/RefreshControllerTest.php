<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status\Processes;

use PhpMyAdmin\Controllers\Server\Status\Processes\RefreshController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;

use function __;
use function htmlspecialchars;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\Processes\RefreshController
 */
class RefreshControllerTest extends AbstractTestCase
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

    public function testRefresh(): void
    {
        $process = [
            'User' => 'User1',
            'Host' => 'Host1',
            'Id' => 'Id1',
            'Db' => 'db1',
            'Command' => 'Command1',
            'Info' => 'Info1',
            'State' => 'State1',
            'Time' => 'Time1',
        ];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new RefreshController(
            $response,
            new Template(),
            $this->data,
            new Processes($GLOBALS['dbi'])
        );

        $_POST['full'] = '1';
        $_POST['order_by_field'] = 'process';
        $_POST['sort_order'] = 'DESC';

        $controller();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('index.php?route=/server/status/processes', $html);
        $killProcess = 'data-post="'
            . Url::getCommon(['kill' => $process['Id']], '') . '"';
        self::assertStringContainsString($killProcess, $html);
        self::assertStringContainsString('ajax kill_process', $html);
        self::assertStringContainsString(__('Kill'), $html);

        //validate 2: $process['User']
        self::assertStringContainsString(htmlspecialchars($process['User']), $html);

        //validate 3: $process['Host']
        self::assertStringContainsString(htmlspecialchars($process['Host']), $html);

        //validate 4: $process['db']
        self::assertStringContainsString($process['Db'], $html);

        //validate 5: $process['Command']
        self::assertStringContainsString(htmlspecialchars($process['Command']), $html);

        //validate 6: $process['Time']
        self::assertStringContainsString($process['Time'], $html);

        //validate 7: $process['state']
        self::assertStringContainsString($process['State'], $html);

        //validate 8: $process['info']
        self::assertStringContainsString($process['Info'], $html);
    }
}
