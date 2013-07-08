<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for relation.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';

class PMA_Relation_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $_SESSION['relation'][$GLOBALS['server']] = "PMA_relation";
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';

        include_once 'libraries/relation.lib.php';
    }

    /**
     * Test for PMA_queryAsControlUser
     *
     * @return void
     */
    public function testPMA_queryAsControlUser()
    {
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
            
        $dbi->expects($this->once())
            ->method('query')
            ->will($this->returnValue('executeResult1'));
            
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->will($this->returnValue('executeResult2'));

        $GLOBALS['dbi'] = $dbi;

        $sql = "insert into PMA_bookmark A,B values(1, 2)";
        $this->assertEquals(
            'executeResult1',
            PMA_queryAsControlUser($sql)
        );
        $this->assertEquals(
            'executeResult2',
            PMA_queryAsControlUser($sql, false)
        );
    }

    /**
     * Test for PMA_getRelationsParam & PMA_getRelationsParamDiagnostic
     *
     * @return void
     */
    public function testPMA_getRelationsParam()
    {
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $_SESSION['relation'] = array();

        $relationsPara = PMA_getRelationsParam();
        $this->assertEquals(
            false,
            $relationsPara['relwork']
        );
        $this->assertEquals(
            false,
            $relationsPara['bookmarkwork']
        );
        $this->assertEquals(
            'root',
            $relationsPara['user']
        );
        $this->assertEquals(
            'phpmyadmin',
            $relationsPara['db']
        );

        $retval = PMA_getRelationsParamDiagnostic($relationsPara);
        //check $cfg['Servers'][$i]['pmadb']
        $this->assertContains(
            "\$cfg['Servers'][\$i]['pmadb']",
            $retval
        );
        $this->assertContains(
            '<strong>OK</strong>',
            $retval
        );
        
        //$cfg['Servers'][$i]['relation']
        $result = "\$cfg['Servers'][\$i]['pmadb']  ... </th><td class=\"right\">" 
            . "<font color=\"green\"><strong>OK</strong></font>";
        $this->assertContains(
            $result,
            $retval
        );
        // $cfg['Servers'][$i]['relation']
        $result = "\$cfg['Servers'][\$i]['relation']  ... </th><td class=\"right\">" 
            . "<font color=\"red\"><strong>not OK</strong></font>";
        $this->assertContains(
            $result,
            $retval
        );
        // General relation features
        $result = 'General relation features: <font color="red">Disabled</font>';
        $this->assertContains(
            $result,
            $retval
        );
        // $cfg['Servers'][$i]['table_info'] 
        $result = "\$cfg['Servers'][\$i]['table_info']  ... </th><td class=\"right\">" 
            . "<font color=\"red\"><strong>not OK</strong></font>";
        $this->assertContains(
            $result,
            $retval
        );
        // Display Features:
        $result = 'Display Features: <font color="red">Disabled</font>';
        $this->assertContains(
            $result,
            $retval
        );
    }
}

