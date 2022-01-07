<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;

/**
 * @covers \PhpMyAdmin\Navigation\Navigation
 */
class NavigationTest extends AbstractTestCase
{
    /** @var Navigation */
    protected $object;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);
        $_SESSION = ['relation' => [$GLOBALS['server'] => $relationParameters->toArray()]];

        $this->object = new Navigation(
            new Template(),
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );
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
        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->object = new Navigation(new Template(), new Relation($dbi), $dbi);
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
        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery);

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;
        $this->object = new Navigation(new Template(), new Relation($dbi), $dbi);
        $this->object->unhideNavigationItem('itemName', 'itemType', 'db');
    }

    /**
     * Tests getItemUnhideDialog() method.
     */
    public function testGetItemUnhideDialog(): void
    {
        $html = $this->object->getItemUnhideDialog('db');
        $this->assertStringContainsString('<td>tableName</td>', $html);
        $this->assertStringContainsString(
            '<a class="unhideNavItem ajax" href="' . Url::getFromRoute('/navigation') . '" data-post="'
            . 'unhideNavItem=1&itemType=table&'
            . 'itemName=tableName&dbName=db&lang=en">',
            $html
        );
    }
}
