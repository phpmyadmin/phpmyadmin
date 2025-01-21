<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use stdClass;

use function __;

/**
 * @covers \PhpMyAdmin\Controllers\Server\DatabasesController
 */
class DatabasesControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['text_dir'] = 'text_dir';
    }

    public function testIndexAction(): void
    {
        global $cfg, $dblist, $is_create_db_priv;

        $dblist = new stdClass();
        $dblist->databases = [
            'sakila',
            'employees',
        ];

        $template = new Template();
        $transformations = new Transformations();
        $relationCleanup = new RelationCleanup(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi'])
        );

        $response = new ResponseRenderer();

        $controller = new DatabasesController(
            $response,
            $template,
            $transformations,
            $relationCleanup,
            $GLOBALS['dbi']
        );

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
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

        $controller = new DatabasesController(
            $response,
            $template,
            $transformations,
            $relationCleanup,
            $GLOBALS['dbi']
        );

        $cfg['ShowCreateDb'] = true;
        $is_create_db_priv = true;
        $_REQUEST['statistics'] = '1';
        $_REQUEST['sort_by'] = 'SCHEMA_TABLES';
        $_REQUEST['sort_order'] = 'desc';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
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
