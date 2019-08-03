<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Navigation\Navigation class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Navigation\Navigation class
 *
 * @package PhpMyAdmin-test
 */
class NavigationTest extends PmaTestCase
{
    /**
     * @var Navigation
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        $GLOBALS['cfgRelation']['db'] = 'pmadb';
        $GLOBALS['cfgRelation']['navigationhiding'] = 'navigationhiding';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['pmaThemeImage'] = '';

        $this->object = new Navigation(
            new Template(),
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Tests hideNavigationItem() method.
     *
     * @return void
     * @test
     */
    public function testHideNavigationItem()
    {
        $expectedQuery = "INSERT INTO `pmadb`.`navigationhiding`"
            . "(`username`, `item_name`, `item_type`, `db_name`, `table_name`)"
            . " VALUES ('user','itemName','itemType','db','')";
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($expectedQuery);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->object = new Navigation(new Template(), new Relation($dbi), $dbi);
        $this->object->hideNavigationItem('itemName', 'itemType', 'db');
    }

    /**
     * Tests unhideNavigationItem() method.
     *
     * @return void
     * @test
     */
    public function testUnhideNavigationItem()
    {
        $expectedQuery = "DELETE FROM `pmadb`.`navigationhiding`"
            . " WHERE `username`='user' AND `item_name`='itemName'"
            . " AND `item_type`='itemType' AND `db_name`='db'";
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($expectedQuery);

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;
        $this->object = new Navigation(new Template(), new Relation($dbi), $dbi);
        $this->object->unhideNavigationItem('itemName', 'itemType', 'db');
    }

    /**
     * Tests getItemUnhideDialog() method.
     *
     * @return void
     * @test
     */
    public function testGetItemUnhideDialog()
    {
        $html = $this->object->getItemUnhideDialog('db');
        $this->assertStringContainsString(
            '<td>tableName</td>',
            $html
        );
        $this->assertStringContainsString(
            '<a class="unhideNavItem ajax" href="navigation.php" data-post="'
            . 'unhideNavItem=1&amp;itemType=table&amp;'
            . 'itemName=tableName&amp;dbName=db&amp;lang=en">',
            $html
        );
    }
}
