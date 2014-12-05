<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/TableSearch.class.php with search type replace
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/Util.class.php';
/*
 * Include to test.
 */
require_once 'libraries/TableSearch.class.php';
require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';

/**
 * Tests for libraries/TableSearch.class.php with search type replace
 *
 * @package PhpMyAdmin-test
 */
class PMA_TableReplaceSearchTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMA_TableSearch
     */
    private $_object;

    /**
     * Sets up the environment for tests
     *
     * @return void
     */
    protected function setup()
    {
        $this->_object = $this->getMock(
            'PMA_TableSearch',
            array('_loadTableInfo'),
            array(),
            '',
            false
        );

        $reflection = new \ReflectionClass('PMA_TableSearch');

        // set database, table names
        $attrDb = $reflection->getProperty('_db');
        $attrDb->setAccessible(true);
        $attrDb->setValue($this->_object, 'dbName');
        $attrTable = $reflection->getProperty('_table');
        $attrTable->setAccessible(true);
        $attrTable->setValue($this->_object, 'tableName');

        // set column names list
        $attrColNames = $reflection->getProperty('_columnNames');
        $attrColNames->setAccessible(true);
        $columnNames = array('column1');
        $attrColNames->setValue($this->_object, $columnNames);
    }

    /**
     * Tests getReplacePreview() method
     *
     * @return void
     * @group medium
     */
    public function testGetReplacePreview()
    {
        //mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $find = 'findValue';
        $replaceWith = 'replaceWithValue';
        $useRegex = false;
        $charSet = 'charSetValue';

        // set expectations
        $dbi->expects($this->once())
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array(
                        array('val1', 'replace1', 5),
                        array('va<2', 'replac<2', 1)
                    )
                )
            );
        $GLOBALS['dbi'] = $dbi;

        $ret = $this->_object->getReplacePreview(
            0, $find, $replaceWith, $useRegex, $charSet
        );

        // assert whether hidden values are properly set
        $this->assertContains(
            '<input type="hidden" name="replace" value="true" />',
            $ret
        );
        $this->assertContains(
            '<input type="hidden" name="columnIndex" value="0" />',
            $ret
        );
        $this->assertContains(
            '<input type="hidden" name="findString"' . ' value="' . $find . '" />',
            $ret
        );
        $this->assertContains(
            '<input type="hidden" name="replaceWith"' . ' value="'
            . $replaceWith . '" />',
            $ret
        );

        // assert values displayed in the preview and escaping
        $this->assertContains(
            '<td class="right">5</td><td>val1</td><td>replace1</td>',
            $ret
        );
        $this->assertContains(
            '<td class="right">1</td><td>va&lt;2</td><td>replac&lt;2</td>',
            $ret
        );
    }

    /**
     * Tests replace() method
     *
     * @return void
     */
    public function testReplace()
    {
        //mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $find = 'findValue';
        $replaceWith = 'replaceWithValue';
        $useRegex = false;
        $charSet = 'charSetValue';

        $expectedQuery = "UPDATE `dbName`.`tableName`"
            . " SET `column1` = REPLACE(`column1`, '" . $find . "', '" . $replaceWith
            . "') WHERE `column1` LIKE '%" . $find . "%' COLLATE "
            . $charSet . "_bin";
        // set expectations
        $dbi->expects($this->once())
            ->method('query')
            ->with($expectedQuery);
        $GLOBALS['dbi'] = $dbi;

        $this->_object->replace(0, $find, $replaceWith, $useRegex, $charSet);
    }
}
?>
