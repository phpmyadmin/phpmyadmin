<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Server\Status\VariablesController;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Status\VariablesController
 */
class VariablesControllerTest extends AbstractTestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
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
        $response = new ResponseRenderer();

        $controller = new VariablesController($response, new Template(), $this->data, $GLOBALS['dbi']);

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        self::assertStringContainsString('<div class="card mb-3" id="tableFilter">', $html);
        self::assertStringContainsString('index.php?route=/server/status/variables', $html);

        self::assertStringContainsString(
            '<label class="col-12 col-form-label" for="filterText">Containing the word:</label>',
            $html
        );

        self::assertStringContainsString('<label class="form-check-label" for="filterAlert">', $html);
        self::assertStringContainsString('Show only alert values', $html);
        self::assertStringContainsString('Filter by category', $html);
        self::assertStringContainsString('Show unformatted values', $html);

        self::assertStringContainsString('<div id="linkSuggestions" class="defaultLinks hide"', $html);

        self::assertStringContainsString('Related links:', $html);
        self::assertStringContainsString('Flush (close) all tables', $html);
        self::assertStringContainsString('<span class="status_binlog_cache">', $html);

        self::assertStringContainsString(
            '<table class="table table-striped table-hover table-sm" id="serverStatusVariables">',
            $html
        );
        self::assertStringContainsString('<th scope="col">Variable</th>', $html);
        self::assertStringContainsString('<th scope="col">Value</th>', $html);
        self::assertStringContainsString('<th scope="col">Description</th>', $html);

        self::assertStringContainsString('Aborted clients', $html);
        self::assertStringContainsString('<span class="text-success">', $html);
        self::assertStringContainsString('Aborted connects', $html);
        self::assertStringContainsString('Com delete multi', $html);
        self::assertStringContainsString('Com create function', $html);
        self::assertStringContainsString('Com empty query', $html);
    }
}
