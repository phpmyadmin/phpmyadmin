<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(Navigation::class)]
class NavigationTest extends AbstractTestCase
{
    protected Navigation $object;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        Current::$database = 'db';
        Current::$table = '';
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'user';
        $config->selectedServer['DisableIS'] = false;
        $config->settings['ActionLinksMode'] = 'both';

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::NAV_WORK => true,
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $this->object = new Navigation(new Template(), new Relation($dbi), $dbi, $config);
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
     * Tests hideNavigationItem() method.
     */
    public function testHideNavigationItem(): void
    {
        $expectedQuery = 'INSERT INTO `pmadb`.`navigationhiding`'
            . '(`username`, `item_name`, `item_type`, `db_name`, `table_name`)'
            . " VALUES ('user','itemName','itemType','db','')";
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        $this->object = new Navigation(new Template(), new Relation($dbi), $dbi, Config::getInstance());
        $this->object->hideNavigationItem('itemName', 'itemType', 'db');
    }

    /**
     * Tests unhideNavigationItem() method.
     */
    public function testUnhideNavigationItem(): void
    {
        $expectedQuery = 'DELETE FROM `pmadb`.`navigationhiding`'
            . " WHERE `username`='user' AND `item_name`='itemName'"
            . " AND `item_type`='itemType' AND `db_name`='db'";
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery);

        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        DatabaseInterface::$instance = $dbi;
        $this->object = new Navigation(new Template(), new Relation($dbi), $dbi, Config::getInstance());
        $this->object->unhideNavigationItem('itemName', 'itemType', 'db');
    }

    /**
     * Tests getItemUnhideDialog() method.
     */
    public function testGetItemUnhideDialog(): void
    {
        $html = $this->object->getItemUnhideDialog('db');
        self::assertStringContainsString('<td>tableName</td>', $html);
        self::assertStringContainsString(
            '<a class="unhideNavItem ajax" href="' . Url::getFromRoute('/navigation') . '" data-post="'
            . 'unhideNavItem=1&itemType=table&'
            . 'itemName=tableName&dbName=db&lang=en">',
            $html,
        );
    }
}
