<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\VariablesController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;

class VariablesControllerTest extends AbstractTestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        parent::setTheme();

        $GLOBALS['text_dir'] = 'ltr';
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

        $controller = new VariablesController($response, new Template(), $this->data, $GLOBALS['dbi']);

        $controller->index();
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            '<fieldset id="tableFilter">',
            $html
        );
        $this->assertStringContainsString(
            'index.php?route=/server/status/variables',
            $html
        );

        $this->assertStringContainsString(
            '<label for="filterText">Containing the word:</label>',
            $html
        );

        $this->assertStringContainsString(
            '<label for="filterAlert">',
            $html
        );
        $this->assertStringContainsString(
            'Show only alert values',
            $html
        );
        $this->assertStringContainsString(
            'Filter by category',
            $html
        );
        $this->assertStringContainsString(
            'Show unformatted values',
            $html
        );

        $this->assertStringContainsString(
            '<div id="linkSuggestions" class="defaultLinks hide"',
            $html
        );

        $this->assertStringContainsString(
            'Related links:',
            $html
        );
        $this->assertStringContainsString(
            'Flush (close) all tables',
            $html
        );
        $this->assertStringContainsString(
            '<span class="status_binlog_cache">',
            $html
        );

        $this->assertStringContainsString(
            '<table class="table table-light table-striped table-hover table-sm" id="serverStatusVariables">',
            $html
        );
        $this->assertStringContainsString(
            '<th scope="col">Variable</th>',
            $html
        );
        $this->assertStringContainsString(
            '<th scope="col">Value</th>',
            $html
        );
        $this->assertStringContainsString(
            '<th scope="col">Description</th>',
            $html
        );

        $this->assertStringContainsString(
            'Aborted clients',
            $html
        );
        $this->assertStringContainsString(
            '<span class="allfine">',
            $html
        );
        $this->assertStringContainsString(
            'Aborted connects',
            $html
        );
        $this->assertStringContainsString(
            'Com delete multi',
            $html
        );
        $this->assertStringContainsString(
            'Com create function',
            $html
        );
        $this->assertStringContainsString(
            'Com empty query',
            $html
        );
    }
}
