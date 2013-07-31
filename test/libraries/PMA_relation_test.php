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
        $_SESSION['relation'] = array();

        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['ServerDefault'] = 0;

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

    /**
     * Test for PMA_getDisplayField
     *
     * @return void
     */
    public function testPMA_getDisplayField()
    {
        
        $db = 'information_schema';
        $table = 'CHARACTER_SETS';
        $this->assertEquals(
            'DESCRIPTION',
            PMA_getDisplayField($db, $table)
        );  
              
        $db = 'information_schema';
        $table = 'TABLES';
        $this->assertEquals(
            'TABLE_COMMENT',
            PMA_getDisplayField($db, $table)
        );
              
        $db = 'information_schema';
        $table = 'PMA';
        $this->assertEquals(
            false,
            PMA_getDisplayField($db, $table)
        );
        
    }

    /**
     * Test for PMA_getComments
     *
     * @return void
     */
    public function testPMA_getComments()
    {
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $_SESSION['relation'] = array();
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        
        $getColumnsResult = array(
                array(
                        'Field' => 'field1',
                        'Type' => 'int(11)',
                        'Comment' => 'Comment1'
                ),
                array(
                        'Field' => 'field2',
                        'Type' => 'text',
                        'Comment' => 'Comment1'
                )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($getColumnsResult));
        
        $GLOBALS['dbi'] = $dbi;
        
        $db = 'information_schema';
        $this->assertEquals(
            array(''),
            PMA_getComments($db)
        );  
              
        $db = 'information_schema';
        $table = 'TABLES';
        $this->assertEquals(
            array(
                'field1' => 'Comment1',
                'field2' => 'Comment1'
            ),
            PMA_getComments($db, $table)
        );    
    }
}

