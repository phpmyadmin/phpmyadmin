<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Navigation\Navigation class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Navigation\Navigation class
 *
 * @package PhpMyAdmin-test
 */
class NavigationTest extends PmaTestCase
{
    /**
     * @var PhpMyAdmin\Navigation\Navigation
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new Navigation();
        $GLOBALS['cfgRelation']['db'] = 'pmadb';
        $GLOBALS['cfgRelation']['navigationhiding'] = 'navigationhiding';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['pmaThemeImage'] = '';
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
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
        $expectedQuery = "SELECT `item_name`, `item_type`"
            . " FROM `pmadb`.`navigationhiding`"
            . " WHERE `username`='user' AND `db_name`='db' AND `table_name`=''";
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($expectedQuery)
            ->will($this->returnValue(true));
        $dbi->expects($this->at(3))
            ->method('fetchArray')
            ->will(
                $this->returnValue(
                    array(
                        'item_name' => 'tableName',
                        'item_type' => 'table'
                    )
                )
            );
        $dbi->expects($this->at(4))
            ->method('fetchArray')
            ->will(
                $this->returnValue(
                    array(
                        'item_name' => 'viewName',
                        'item_type' => 'view'
                    )
                )
            );
        $dbi->expects($this->at(5))
            ->method('fetchArray')
            ->will($this->returnValue(false));
        $dbi->expects($this->once())
            ->method('freeResult');
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $html = $this->object->getItemUnhideDialog('db');
        $this->assertContains(
            '<td>tableName</td>',
            $html
        );
        $this->assertContains(
            '<a href="navigation.php" data-post="'
            . 'unhideNavItem=1&amp;itemType=table&amp;'
            . 'itemName=tableName&amp;dbName=db&amp;lang=en"'
            . ' class="unhideNavItem ajax">',
            $html
        );
    }
}
