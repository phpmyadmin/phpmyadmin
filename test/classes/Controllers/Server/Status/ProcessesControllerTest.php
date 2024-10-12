<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\ProcessesController
 */
class ProcessesControllerTest extends AbstractTestCase
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

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new ProcessesController(
            $response,
            new Template(),
            $this->data,
            $GLOBALS['dbi'],
            new Processes($GLOBALS['dbi'])
        );

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('Note: Enabling the auto refresh here might cause '
        . 'heavy traffic between the web server and the MySQL server.', $html);
        // Test tab links
        self::assertStringContainsString('<div class="tabLinks row">', $html);
        self::assertStringContainsString('<a id="toggleRefresh" href="#">', $html);
        self::assertStringContainsString('play', $html);
        self::assertStringContainsString('Start auto refresh', $html);
        self::assertStringContainsString('<select id="id_refreshRate"', $html);
        self::assertStringContainsString('<option value="5" selected>', $html);
        self::assertStringContainsString('5 seconds', $html);

        self::assertStringContainsString(
            '<table id="tableprocesslist" class="table table-striped table-hover sortable w-auto">',
            $html
        );
        self::assertStringContainsString('<th>Processes</th>', $html);
        self::assertStringContainsString('Show full queries', $html);
        self::assertStringContainsString('index.php?route=/server/status/processes', $html);

        $_POST['full'] = '1';
        $_POST['column_name'] = 'Database';
        $_POST['order_by_field'] = 'Db';
        $_POST['sort_order'] = 'ASC';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('Truncate shown queries', $html);
        self::assertStringContainsString('Database', $html);
        self::assertStringContainsString('DESC', $html);

        $_POST['column_name'] = 'Host';
        $_POST['order_by_field'] = 'Host';
        $_POST['sort_order'] = 'DESC';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('Host', $html);
        self::assertStringContainsString('ASC', $html);
    }
}
