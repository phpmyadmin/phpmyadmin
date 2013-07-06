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
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';

class PMA_Relation_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $GLOBALS['server'] = "table";
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $_SESSION['relation'][$GLOBALS['server']] = "PMA_relation";
        $GLOBALS['server'] = 1;

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
    	->will($this->returnValue('executed'));
    	
    	$dbi->expects($this->once())
    	->method('tryQuery')
    	->will($this->returnValue('executed'));
    	
    	$GLOBALS['dbi'] = $dbi;
    	
        $sql = "insert into PMA_bookmark A,B values(1, 2)";
        $this->assertEquals(
            'executed',
            PMA_queryAsControlUser($sql)
        );
        $this->assertEquals(
            'executed',
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
        $_SESSION['relation'][$GLOBALS['server']]['relwork'] = "relwork";
    	$GLOBALS['server'] = "table";
    	
    	$relationsPara = PMA_getRelationsParam();
        $this->assertEquals(
            false,
        	$relationsPara['relwork']
        );
        		
        $retval = PMA_getRelationsParamDiagnostic($GLOBALS['cfgRelation']);
        $this->assertContains(
            '<font color="green">Disabled</font>',
            $retval
        );
        $this->assertContains(
            'General relation features',
            $retval
        );
        $this->assertContains(
            '<strong>not OK</strong>',
            $retval
        );
    }
}
