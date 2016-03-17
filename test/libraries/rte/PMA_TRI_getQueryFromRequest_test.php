<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating CREATE TRIGGER query from HTTP request
 *
 * @package PhpMyAdmin-test
 */

/*
 * Needed for backquote()
 */


/*
 * Needed by PMA_TRI_getQueryFromRequest()
 */

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_triggers.lib.php';
require_once 'libraries/database_interface.inc.php';


/**
 * Test for generating CREATE TRIGGER query from HTTP request
 *
 * @package PhpMyAdmin-test
 */
class PMA_TRI_GetQueryFromRequest_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['cfg']['DBG']['sql'] = false;
    }

    /**
     * Test for PMA_TRI_getQueryFromRequest
     *
     * @param string $definer    Definer
     * @param string $name       Name
     * @param string $timing     Timing
     * @param string $event      Event
     * @param string $table      Table
     * @param string $definition Definition
     * @param string $query      Query
     * @param string $num_err    Error number
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testgetQueryFromRequest(
        $definer, $name, $timing, $event, $table, $definition, $query, $num_err
    ) {
        global $_REQUEST, $errors;

        $errors = array();
        PMA_TRI_setGlobals();

        $_REQUEST['item_definer']    = $definer;
        $_REQUEST['item_name']       = $name;
        $_REQUEST['item_timing']     = $timing;
        $_REQUEST['item_event']      = $event;
        $_REQUEST['item_table']      = $table;
        $_REQUEST['item_definition'] = $definition;
        $GLOBALS['server'] = 1;

        $this->assertEquals($query, PMA_TRI_getQueryFromRequest());
        $this->assertEquals($num_err, count($errors));
    }

    /**
     * Data provider for testgetQueryFromRequest
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array('',
                '',
                '',
                '',
                '',
                '',
                'CREATE TRIGGER ON  FOR EACH ROW ',
                5
            ),
            array(
                'root',
                'trigger',
                'BEFORE',
                'INSERT',
                'table`2',
                'SET @A=NULL',
                'CREATE TRIGGER `trigger` BEFORE INSERT ON  FOR EACH ROW SET @A=NULL',
                2
            ),
            array(
                'foo`s@host',
                'trigger`s test',
                'AFTER',
                'foo',
                'table3',
                'BEGIN SET @A=1; SET @B=2; END',
                'CREATE DEFINER=`foo``s`@`host` TRIGGER `trigger``s test` AFTER ON  FOR EACH ROW BEGIN SET @A=1; SET @B=2; END',
                2
            ),
            array(
                'root@localhost',
                'trigger',
                'BEFORE',
                'INSERT',
                'table1',
                'SET @A=NULL',
                'CREATE DEFINER=`root`@`localhost` TRIGGER `trigger` BEFORE INSERT ON `table1` FOR EACH ROW SET @A=NULL',
                0
            ),
        );
    }
}
