<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\RecentFavoriteController;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use Psr\Container\ContainerInterface;

/**
 * @covers \PhpMyAdmin\Controllers\Table\RecentFavoriteController
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RecentFavoriteControllerTest extends AbstractTestCase
{
    public function testRecentFavoriteControllerWithValidDbAndTable(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $_REQUEST['db'] = 'test_db';
        $_REQUEST['table'] = 'test_table';
        $_SESSION['relation'] = [];

        $_SESSION['tmpval'] = [];
        $_SESSION['tmpval']['recentTables'][2] = [['db' => 'test_db', 'table' => 'test_table']];
        $_SESSION['tmpval']['favoriteTables'][2] = [['db' => 'test_db', 'table' => 'test_table']];

        $controller = $this->createMock(SqlController::class);
        $controller->expects($this->once())->method('__invoke');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with(SqlController::class)->willReturn($controller);
        $GLOBALS['containerBuilder'] = $container;

        $recent = RecentFavoriteTable::getInstance('recent');
        $favorite = RecentFavoriteTable::getInstance('favorite');

        $this->assertSame([['db' => 'test_db', 'table' => 'test_table']], $recent->getTables());
        $this->assertSame([['db' => 'test_db', 'table' => 'test_table']], $favorite->getTables());

        (new RecentFavoriteController(new ResponseRenderer(), new Template()))();

        $this->assertSame([['db' => 'test_db', 'table' => 'test_table']], $recent->getTables());
        $this->assertSame([['db' => 'test_db', 'table' => 'test_table']], $favorite->getTables());
    }

    public function testRecentFavoriteControllerWithInvalidDbAndTable(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $_REQUEST['db'] = 'invalid_db';
        $_REQUEST['table'] = 'invalid_table';
        $_SESSION['relation'] = [];

        $_SESSION['tmpval'] = [];
        $_SESSION['tmpval']['recentTables'][2] = [['db' => 'invalid_db', 'table' => 'invalid_table']];
        $_SESSION['tmpval']['favoriteTables'][2] = [['db' => 'invalid_db', 'table' => 'invalid_table']];

        $this->dummyDbi->addResult('SHOW COLUMNS FROM `invalid_db`.`invalid_table`', false);
        $this->dummyDbi->addResult('SHOW COLUMNS FROM `invalid_db`.`invalid_table`', false);

        $controller = $this->createMock(SqlController::class);
        $controller->expects($this->once())->method('__invoke');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with(SqlController::class)->willReturn($controller);
        $GLOBALS['containerBuilder'] = $container;

        $recent = RecentFavoriteTable::getInstance('recent');
        $favorite = RecentFavoriteTable::getInstance('favorite');

        $this->assertSame([['db' => 'invalid_db', 'table' => 'invalid_table']], $recent->getTables());
        $this->assertSame([['db' => 'invalid_db', 'table' => 'invalid_table']], $favorite->getTables());

        (new RecentFavoriteController(new ResponseRenderer(), new Template()))();

        $this->assertSame([], $recent->getTables());
        $this->assertSame([], $favorite->getTables());
    }
}
