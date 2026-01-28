<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\NavigationTree;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\UserPrivileges;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NavigationTree::class)]
class NavigationTreeTest extends AbstractTestCase
{
    protected NavigationTree $object;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $config = Config::getInstance();
        $config->selectedServer['host'] = 'localhost';
        $config->selectedServer['user'] = 'user';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NavigationTreeEnableGrouping'] = true;
        $config->settings['ShowDatabasesNavigationAsTree'] = true;

        Current::$database = 'db';
        Current::$table = '';

        $this->object = new NavigationTree(new Template(), $dbi, new Relation($dbi), $config);
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * Very basic rendering test.
     */
    public function testRenderState(): void
    {
        $result = $this->object->renderState(new UserPrivileges());
        self::assertStringContainsString('recentFavoriteTablesWrapper', $result);
    }

    /**
     * Very basic path rendering test.
     */
    public function testRenderPath(): void
    {
        $result = $this->object->renderPath(new UserPrivileges());
        self::assertIsString($result);
        self::assertStringContainsString('list_container', $result);
    }

    /**
     * Very basic select rendering test.
     */
    public function testRenderDbSelect(): void
    {
        $result = $this->object->renderDbSelect(new UserPrivileges());
        self::assertStringContainsString('pma_navigation_select_database', $result);
    }

    public function testDatabaseGrouping(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->settings['NavigationTreeDbSeparator'] = '__';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, (SELECT DB_first_level FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'__\', 1) DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE(CONCAT(DB_first_level, \'__\'), CONCAT(SCHEMA_NAME, \'__\')) ORDER BY SCHEMA_NAME ASC',
            [['functions__a'], ['functions__b']],
            ['SCHEMA_NAME'],
        );
        $dummyDbi->addResult(
            'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'__\', 1) DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t',
            [['2']],
        );
        $dummyDbi->addResult(
            'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'__\', 1) DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t',
            [['2']],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $object = new NavigationTree(new Template(), $dbi, new Relation($dbi), $config);
        $result = $object->renderState(new UserPrivileges());
        self::assertStringContainsString('<li class="first navGroup">', $result);
        self::assertStringContainsString('functions' . "\n", $result);
        self::assertStringContainsString('<div class="list_container" style="display: none;">', $result);
        self::assertStringContainsString('functions__a', $result);
        self::assertStringContainsString('functions__b', $result);
    }
}
