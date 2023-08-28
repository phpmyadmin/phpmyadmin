<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Navigation\NavigationTree;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
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

        parent::setLanguage();

        parent::setGlobalConfig();

        parent::setTheme();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $GLOBALS['server'] = 1;
        $config = Config::getInstance();
        $config->selectedServer['host'] = 'localhost';
        $config->selectedServer['user'] = 'user';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NavigationTreeEnableGrouping'] = true;
        $config->settings['ShowDatabasesNavigationAsTree'] = true;

        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';

        $this->object = new NavigationTree(new Template(), $dbi, new Relation($dbi));
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
        $result = $this->object->renderState();
        $this->assertStringContainsString('pma_quick_warp', $result);
    }

    /**
     * Very basic path rendering test.
     */
    public function testRenderPath(): void
    {
        $result = $this->object->renderPath();
        $this->assertIsString($result);
        $this->assertStringContainsString('list_container', $result);
    }

    /**
     * Very basic select rendering test.
     */
    public function testRenderDbSelect(): void
    {
        $result = $this->object->renderDbSelect();
        $this->assertStringContainsString('pma_navigation_select_database', $result);
    }

    public function testDatabaseGrouping(): void
    {
        $GLOBALS['db'] = '';
        Config::getInstance()->settings['NavigationTreeDbSeparator'] = '__';

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

        $object = new NavigationTree(new Template(), $dbi, new Relation($dbi));
        $result = $object->renderState();
        $this->assertStringContainsString('<li class="first navGroup">', $result);
        $this->assertStringContainsString('functions' . "\n", $result);
        $this->assertStringContainsString('<div class="list_container" style="display: none;">', $result);
        $this->assertStringContainsString('functions__a', $result);
        $this->assertStringContainsString('functions__b', $result);
    }
}
