<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerDatabasesControllerTest class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\ServerDatabasesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ServerDatabasesController class
 *
 * @package PhpMyAdmin-test
 */
class ServerDatabasesControllerTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['text_dir'] = "text_dir";
    }

    /**
     * @return void
     */
    public function testIndexAction(): void
    {
        global $cfg, $dblist, $is_create_db_priv;

        $databases = [
            [
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'SCHEMA_TABLES' => '23',
                'SCHEMA_TABLE_ROWS' => '47274',
                'SCHEMA_DATA_LENGTH' => '4358144',
                'SCHEMA_INDEX_LENGTH' => '2392064',
                'SCHEMA_LENGTH' => '6750208',
                'SCHEMA_DATA_FREE' => '0',
                'SCHEMA_NAME' => 'sakila',
            ],
            [
                'DEFAULT_COLLATION_NAME' => 'utf8mb4_general_ci',
                'SCHEMA_TABLES' => '8',
                'SCHEMA_TABLE_ROWS' => '3912174',
                'SCHEMA_DATA_LENGTH' => '148111360',
                'SCHEMA_INDEX_LENGTH' => '5816320',
                'SCHEMA_LENGTH' => '153927680',
                'SCHEMA_DATA_FREE' => '0',
                'SCHEMA_NAME' => 'employees',
            ],
        ];

        $dblist = new \stdClass();
        $dblist->databases = $databases;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->method('getDatabasesFull')
            ->willReturn($databases);

        $controller = new ServerDatabasesController(
            Response::getInstance(),
            $dbi
        );

        $actual = $controller->indexAction([
            'statistics' => null,
            'pos' => null,
            'sort_by' => null,
            'sort_order' => null,
        ]);

        $this->assertContains('data-filter-row="SAKILA"', $actual);
        $this->assertContains('sakila', $actual);
        $this->assertContains('utf8_general_ci', $actual);
        $this->assertContains('title="Unicode, case-insensitive"', $actual);
        $this->assertContains('data-filter-row="SAKILA"', $actual);
        $this->assertContains('sakila', $actual);
        $this->assertContains('utf8mb4_general_ci', $actual);
        $this->assertContains('title="Unicode (UCA 4.0.0), case-insensitive"', $actual);
        $this->assertContains('2 databases', $actual);
        $this->assertContains('name="pos" value="0"', $actual);
        $this->assertContains('name="sort_by" value="SCHEMA_NAME"', $actual);
        $this->assertContains('name="sort_order" value="asc"', $actual);
        $this->assertContains(__('Enable statistics'), $actual);
        $this->assertContains(__('No privileges to create databases'), $actual);
        $this->assertNotContains(__('Indexes'), $actual);

        $cfg['ShowCreateDb'] = true;
        $is_create_db_priv = true;

        $actual = $controller->indexAction([
            'statistics' => '1',
            'pos' => null,
            'sort_by' => 'SCHEMA_TABLES',
            'sort_order' => 'desc',
        ]);

        $this->assertNotContains(__('Enable statistics'), $actual);
        $this->assertContains(__('Indexes'), $actual);
        $this->assertContains('name="sort_by" value="SCHEMA_TABLES"', $actual);
        $this->assertContains('name="sort_order" value="desc"', $actual);
        $this->assertContains('name="statistics" value="1"', $actual);
        $this->assertContains('title="3912174"', $actual);
        $this->assertContains('3,912,174', $actual);
        $this->assertContains('title="4358144"', $actual);
        $this->assertContains('4.2', $actual);
        $this->assertContains('MiB', $actual);
        $this->assertContains('name="db_collation"', $actual);
    }

    /**
     * @return void
     */
    public function testCreateDatabaseAction()
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->method('getError')
            ->willReturn('CreateDatabaseError');

        $controller = new ServerDatabasesController(
            Response::getInstance(),
            $dbi
        );

        $actual = $controller->createDatabaseAction([
            'new_db' => 'pma_test',
            'db_collation' => null,
        ]);

        $this->assertArrayHasKey('message', $actual);
        $this->assertInstanceOf(Message::class, $actual['message']);
        $this->assertContains('<div class="error">', $actual['message']->getDisplay());
        $this->assertContains('CreateDatabaseError', $actual['message']->getDisplay());

        $dbi->method('tryQuery')
            ->willReturn(true);

        $actual = $controller->createDatabaseAction([
            'new_db' => 'pma_test',
            'db_collation' => 'utf8_general_ci',
        ]);

        $this->assertArrayHasKey('message', $actual);
        $this->assertInstanceOf(Message::class, $actual['message']);
        $this->assertContains('<div class="success">', $actual['message']->getDisplay());
        $this->assertContains(
            sprintf(__('Database %1$s has been created.'), 'pma_test'),
            $actual['message']->getDisplay()
        );
    }

    /**
     * @return void
     */
    public function testDropDatabasesAction()
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new ServerDatabasesController(
            Response::getInstance(),
            $dbi
        );

        $actual = $controller->dropDatabasesAction([
            'drop_selected_dbs' => true,
            'selected_dbs' => null,
        ]);

        $this->assertArrayHasKey('message', $actual);
        $this->assertInstanceOf(Message::class, $actual['message']);
        $this->assertContains('<div class="error">', $actual['message']->getDisplay());
        $this->assertContains(__('No databases selected.'), $actual['message']->getDisplay());
    }
}
