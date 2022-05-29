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

        $this->assertStringContainsString(
            'Note: Enabling the auto refresh here might cause '
            . 'heavy traffic between the web server and the MySQL server.',
            $html
        );
        // Test tab links
        $this->assertStringContainsString('<div class="tabLinks row">', $html);
        $this->assertStringContainsString('<a id="toggleRefresh" href="#">', $html);
        $this->assertStringContainsString('play', $html);
        $this->assertStringContainsString('Start auto refresh', $html);
        $this->assertStringContainsString('<select id="id_refreshRate"', $html);
        $this->assertStringContainsString('<option value="5" selected>', $html);
        $this->assertStringContainsString('5 seconds', $html);

        $this->assertStringContainsString(
            '<table id="tableprocesslist" class="table table-striped table-hover sortable w-auto">',
            $html
        );
        $this->assertStringContainsString('<th>Processes</th>', $html);
        $this->assertStringContainsString('Show full queries', $html);
        $this->assertStringContainsString('index.php?route=/server/status/processes', $html);

        $_POST['full'] = '1';
        $_POST['column_name'] = 'Database';
        $_POST['order_by_field'] = 'Db';
        $_POST['sort_order'] = 'ASC';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString('Truncate shown queries', $html);
        $this->assertStringContainsString('Database', $html);
        $this->assertStringContainsString('DESC', $html);

        $_POST['column_name'] = 'Host';
        $_POST['order_by_field'] = 'Host';
        $_POST['sort_order'] = 'DESC';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString('Host', $html);
        $this->assertStringContainsString('ASC', $html);
    }
}
