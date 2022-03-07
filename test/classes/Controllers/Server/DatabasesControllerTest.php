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
        $GLOBALS['dblist'] = new stdClass();
        $GLOBALS['dblist']->databases = [
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

        $this->assertStringContainsString('data-filter-row="SAKILA"', $actual);
        $this->assertStringContainsString('sakila', $actual);
        $this->assertStringContainsString('utf8_general_ci', $actual);
        $this->assertStringContainsString('title="Unicode, case-insensitive"', $actual);
        $this->assertStringContainsString('data-filter-row="SAKILA"', $actual);
        $this->assertStringContainsString('employees', $actual);
        $this->assertStringContainsString('latin1_swedish_ci', $actual);
        $this->assertStringContainsString('title="Swedish, case-insensitive"', $actual);
        $this->assertStringContainsString('<span id="filter-rows-count">2</span>', $actual);
        $this->assertStringContainsString('name="pos" value="0"', $actual);
        $this->assertStringContainsString('name="sort_by" value="SCHEMA_NAME"', $actual);
        $this->assertStringContainsString('name="sort_order" value="asc"', $actual);
        $this->assertStringContainsString(__('Enable statistics'), $actual);
        $this->assertStringContainsString(__('No privileges to create databases'), $actual);
        $this->assertStringNotContainsString(__('Indexes'), $actual);

        $response = new ResponseRenderer();

        $controller = new DatabasesController(
            $response,
            $template,
            $transformations,
            $relationCleanup,
            $GLOBALS['dbi']
        );

        $GLOBALS['cfg']['ShowCreateDb'] = true;
        $GLOBALS['is_create_db_priv'] = true;
        $_REQUEST['statistics'] = '1';
        $_REQUEST['sort_by'] = 'SCHEMA_TABLES';
        $_REQUEST['sort_order'] = 'desc';

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        $this->assertStringNotContainsString(__('Enable statistics'), $actual);
        $this->assertStringContainsString(__('Indexes'), $actual);
        $this->assertStringContainsString('name="sort_by" value="SCHEMA_TABLES"', $actual);
        $this->assertStringContainsString('name="sort_order" value="desc"', $actual);
        $this->assertStringContainsString('name="statistics" value="1"', $actual);
        $this->assertStringContainsString('title="3912174"', $actual);
        $this->assertStringContainsString('3,912,174', $actual);
        $this->assertStringContainsString('title="4358144"', $actual);
        $this->assertStringContainsString('4.2', $actual);
        $this->assertStringContainsString('MiB', $actual);
        $this->assertStringContainsString('name="db_collation"', $actual);
    }
}
