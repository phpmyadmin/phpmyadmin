<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Url;
use function htmlspecialchars;

class ProcessesControllerTest extends AbstractTestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
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
        $response = new Response();

        $controller = new ProcessesController($response, new Template(), $this->data, $GLOBALS['dbi']);

        $controller->index();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Note: Enabling the auto refresh here might cause '
            . 'heavy traffic between the web server and the MySQL server.',
            $html
        );
        // Test tab links
        $this->assertStringContainsString(
            '<div class="tabLinks row">',
            $html
        );
        $this->assertStringContainsString(
            '<a id="toggleRefresh" href="#">',
            $html
        );
        $this->assertStringContainsString(
            'play',
            $html
        );
        $this->assertStringContainsString(
            'Start auto refresh',
            $html
        );
        $this->assertStringContainsString(
            '<select id="id_refreshRate"',
            $html
        );
        $this->assertStringContainsString(
            '<option value="5" selected>',
            $html
        );
        $this->assertStringContainsString(
            '5 seconds',
            $html
        );

        $this->assertStringContainsString(
            '<table id="tableprocesslist" class="table table-light table-striped table-hover sortable w-auto">',
            $html
        );
        $this->assertStringContainsString(
            '<th>Processes</th>',
            $html
        );
        $this->assertStringContainsString(
            'Show full queries',
            $html
        );
        $this->assertStringContainsString(
            'index.php?route=/server/status/processes',
            $html
        );

        $_POST['full'] = '1';
        $_POST['column_name'] = 'Database';
        $_POST['order_by_field'] = 'db';
        $_POST['sort_order'] = 'ASC';

        $controller->index();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Truncate shown queries',
            $html
        );
        $this->assertStringContainsString(
            'Database',
            $html
        );
        $this->assertStringContainsString(
            'DESC',
            $html
        );

        $_POST['column_name'] = 'Host';
        $_POST['order_by_field'] = 'Host';
        $_POST['sort_order'] = 'DESC';

        $controller->index();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Host',
            $html
        );
        $this->assertStringContainsString(
            'ASC',
            $html
        );
    }

    public function testRefresh(): void
    {
        $process = [
            'User' => 'User1',
            'Host' => 'Host1',
            'Id' => 'Id1',
            'db' => 'db1',
            'Command' => 'Command1',
            'Info' => 'Info1',
            'State' => 'State1',
            'Time' => 'Time1',
        ];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;

        $response = new Response();
        $response->setAjax(true);

        $controller = new ProcessesController($response, new Template(), $this->data, $GLOBALS['dbi']);

        $_POST['full'] = '1';
        $_POST['order_by_field'] = 'process';
        $_POST['sort_order'] = 'DESC';

        $controller->refresh();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'index.php?route=/server/status/processes',
            $html
        );
        $killProcess = 'data-post="'
            . Url::getCommon(['kill' => $process['Id']], '') . '"';
        $this->assertStringContainsString(
            $killProcess,
            $html
        );
        $this->assertStringContainsString(
            'ajax kill_process',
            $html
        );
        $this->assertStringContainsString(
            __('Kill'),
            $html
        );

        //validate 2: $process['User']
        $this->assertStringContainsString(
            htmlspecialchars($process['User']),
            $html
        );

        //validate 3: $process['Host']
        $this->assertStringContainsString(
            htmlspecialchars($process['Host']),
            $html
        );

        //validate 4: $process['db']
        $this->assertStringContainsString(
            __('None'),
            $html
        );

        //validate 5: $process['Command']
        $this->assertStringContainsString(
            htmlspecialchars($process['Command']),
            $html
        );

        //validate 6: $process['Time']
        $this->assertStringContainsString(
            $process['Time'],
            $html
        );

        //validate 7: $process['state']
        $this->assertStringContainsString(
            $process['State'],
            $html
        );

        //validate 8: $process['info']
        $this->assertStringContainsString(
            $process['Info'],
            $html
        );
    }
}
