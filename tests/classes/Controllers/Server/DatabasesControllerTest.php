<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UserPrivileges;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;

#[CoversClass(DatabasesController::class)]
class DatabasesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        parent::setGlobalConfig();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'pma_test';
        Current::$table = '';
        Config::getInstance()->selectedServer['DisableIS'] = false;
    }

    public function testIndexAction(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['only_db'] = '';
        $template = new Template();

        $response = new ResponseRenderer();

        $controller = new DatabasesController($response, $template, $this->dbi);

        $this->dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['sakila'], ['employees']],
            ['SCHEMA_NAME'],
        );
        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        self::assertStringContainsString('data-filter-row="SAKILA"', $actual);
        self::assertStringContainsString('sakila', $actual);
        self::assertStringContainsString('utf8_general_ci', $actual);
        self::assertStringContainsString('title="Unicode, case-insensitive"', $actual);
        self::assertStringContainsString('data-filter-row="SAKILA"', $actual);
        self::assertStringContainsString('employees', $actual);
        self::assertStringContainsString('latin1_swedish_ci', $actual);
        self::assertStringContainsString('title="Swedish, case-insensitive"', $actual);
        self::assertStringContainsString('<span id="filter-rows-count">2</span>', $actual);
        self::assertStringContainsString('name="pos" value="0"', $actual);
        self::assertStringContainsString('name="sort_by" value="SCHEMA_NAME"', $actual);
        self::assertStringContainsString('name="sort_order" value="asc"', $actual);
        self::assertStringContainsString(__('Enable statistics'), $actual);
        self::assertStringContainsString(__('No privileges to create databases'), $actual);
        self::assertStringNotContainsString(__('Indexes'), $actual);

        $response = new ResponseRenderer();

        $controller = new DatabasesController($response, $template, DatabaseInterface::getInstance());

        $config->settings['ShowCreateDb'] = true;
        UserPrivileges::$isCreateDatabase = true;
        $_REQUEST['statistics'] = '1';
        $_REQUEST['sort_by'] = 'SCHEMA_TABLES';
        $_REQUEST['sort_order'] = 'desc';

        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        self::assertStringNotContainsString(__('Enable statistics'), $actual);
        self::assertStringContainsString(__('Indexes'), $actual);
        self::assertStringContainsString('name="sort_by" value="SCHEMA_TABLES"', $actual);
        self::assertStringContainsString('name="sort_order" value="desc"', $actual);
        self::assertStringContainsString('name="statistics" value="1"', $actual);
        self::assertStringContainsString('title="3912174"', $actual);
        self::assertStringContainsString('3,912,174', $actual);
        self::assertStringContainsString('title="4358144"', $actual);
        self::assertStringContainsString('4.2', $actual);
        self::assertStringContainsString('MiB', $actual);
        self::assertStringContainsString('name="db_collation"', $actual);
    }
}
