<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tracking.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tracking.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/js_escape.lib.php';

/**
 * Tests for libraries/tracking.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblTrackingTest extends PHPUnit_Framework_TestCase
{

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        /**
         * SET these to avoid undefined index error
         */
        $_REQUEST['db'] = "db";
        $_REQUEST['table'] = "table";

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = "PMA_db";
        $GLOBALS['table'] = "PMA_table";
        $GLOBALS['pmaThemeImage'] = "image";
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;

        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'tracking' => 'tracking',
            'trackingwork' => true
        );

        $GLOBALS['cfg']['Server']['tracking_default_statements'] = 'DELETE';

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchArray = array('version' => "10");
        $dbi->expects($this->any())
            ->method('fetchArray')
            ->will($this->returnValue($fetchArray));
        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Tests for PMA_filterTracking() method.
     *
     * @return void
     * @test
     */
    public function testPMAFilterTracking()
    {
        $data = array(
            array(
                "date" => "20120102",
                "username"=> "username1",
                "statement"=>"statement1"
            ),
            array(
                "date" => "20130102",
                "username"=> "username2",
                "statement"=>"statement2"
            ),
        );
        $filter_ts_from = 0;
        $filter_ts_to = 999999999999;
        $filter_users = array("username1");

        $ret = PMA_filterTracking(
            $data, $filter_ts_from, $filter_ts_to, $filter_users
        );

        $this->assertEquals(
            'username1',
            $ret[0]['username']
        );
        $this->assertEquals(
            'statement1',
            $ret[0]['statement']
        );
    }

    /**
     * Tests for PMA_getHtmlForDataDefinitionAndManipulationStatements() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDataDefinitionAndManipulationStatements()
    {
        $url_query = "url_query";
        $last_version = 10;
        $html = PMA_getHtmlForDataDefinitionAndManipulationStatements(
            $url_query, $last_version, $GLOBALS['db'], array($GLOBALS['table'])
        );

        $this->assertContains(
            '<div id="div_create_version">',
            $html
        );

        $this->assertContains(
            $url_query,
            $html
        );

        $this->assertContains(
            PMA_URL_getHiddenInputs($GLOBALS['db']),
            $html
        );

        $item = sprintf(
            __('Create version %1$s of %2$s'),
            ($last_version + 1),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertContains(
            $item,
            $html
        );

        $item = '<input type="checkbox" name="delete" value="true"'
        . ' checked="checked" /> DELETE<br/>';
        $this->assertContains(
            $item,
            $html
        );

        $this->assertContains(
            __('Create version'),
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForActivateDeactivateTracking() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForActivateDeactivateTracking()
    {
        $url_query = "url_query";
        $last_version = "10";
        $html = PMA_getHtmlForActivateDeactivateTracking(
            'activate', $url_query, $last_version
        );

        $this->assertContains(
            $url_query,
            $html
        );

        $item = sprintf(
            __('Activate tracking for %s'),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertContains(
            $item,
            $html
        );

        $this->assertContains(
            $last_version,
            $html
        );

        $this->assertContains(
            __('Activate now'),
            $html
        );

        $html = PMA_getHtmlForActivateDeactivateTracking(
            'deactivate', $url_query, $last_version
        );

        $this->assertContains(
            $url_query,
            $html
        );

        $item = sprintf(
            __('Deactivate tracking for %s'),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertContains(
            $item,
            $html
        );

        $this->assertContains(
            $last_version,
            $html
        );

        $this->assertContains(
            __('Deactivate now'),
            $html
        );
    }

    /**
     * Tests for PMA_getTableLastVersionNumber() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetTableLastVersionNumber()
    {
        $sql_result = "sql_result";
        $last_version = PMA_getTableLastVersionNumber($sql_result);

        $this->assertEquals(
            "10",
            $last_version
        );
    }

    /**
     * Tests for PMA_getSQLResultForSelectableTables() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetSQLResultForSelectableTables()
    {
        $ret = PMA_getSQLResultForSelectableTables();

        $this->assertEquals(
            true,
            $ret
        );
    }

    /**
     * Tests for PMA_getHtmlForColumns() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForColumns()
    {
        $columns = array(
            array(
                'Field'=>'Field1',
                'Type'=>'Type1',
                'Collation'=>'Collation1',
                "Null"=>'YES',
                'Extra'=>'Extra1',
                'Key'=>'PRI',
                'Comment'=>'Comment1'
            ),
            array(
                'Field'=>'Field2',
                'Type'=>'Type2',
                'Collation'=>'Collation2',
                "Null"=>'No',
                'Extra'=>'Extra2',
                'Key'=>'Key2',
                'Comment'=>'Comment2'
            ),
        );

        $html = PMA_getHtmlForColumns($columns);

        $this->assertContains(
            __('Column'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Collation'),
            $html
        );
        $this->assertContains(
            __('Default'),
            $html
        );
        $this->assertContains(
            __('Comment'),
            $html
        );

        //column1
        $item1= $columns[0];
        $this->assertContains(
            htmlspecialchars($item1['Field']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Type']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Collation']),
            $html
        );
        $this->assertContains(
            '<i>NULL</i>',
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Comment']),
            $html
        );

        //column2
        $item1= $columns[1];
        $this->assertContains(
            htmlspecialchars($item1['Field']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Type']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Collation']),
            $html
        );
        $this->assertContains(
            _pgettext('None for default', 'None'),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Comment']),
            $html
        );
    }

    /**
     * Tests for PMA_getListOfVersionsOfTable() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetListOfVersionsOfTable()
    {
        $ret = PMA_getListOfVersionsOfTable();

        $this->assertEquals(
            true,
            $ret
        );
    }

    /**
     * Tests for PMA_getHtmlForTableVersionDetails() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForTableVersionDetails()
    {
        $sql_result = true;
        $last_version = "10";
        $url_params = array();
        $url_query = "select * from PMA";
        $pmaThemeImage = "themePath/img";
        $text_dir = "ltr";

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchArray = array(
                'tracking_active' => 1,
                'version' => "10",
                'db_name' => 'db_name',
                'table_name' => 'table_name',
                'date_created' => 'date_created',
                'date_updated' => 'date_updated'
        );
        $dbi->expects($this->at(0))
            ->method('fetchArray')
            ->will($this->returnValue($fetchArray));
        $dbi->expects($this->at(1))
            ->method('fetchArray')
            ->will($this->returnValue($fetchArray));
        $dbi->expects($this->at(2))
            ->method('fetchArray')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        $ret = PMA_getHtmlForTableVersionDetails(
            $sql_result, $last_version, $url_params, $url_query,
            $pmaThemeImage, $text_dir
        );

        $this->assertContains(
            __('Version'),
            $ret
        );
        $this->assertContains(
            __('Created'),
            $ret
        );
        $this->assertContains(
            __('Updated'),
            $ret
        );
        $this->assertContains(
            __('Status'),
            $ret
        );
        $this->assertContains(
            __('Action'),
            $ret
        );
        $this->assertContains(
            __('Show'),
            $ret
        );
        $this->assertContains(
            $fetchArray['version'],
            $ret
        );
        $this->assertContains(
            $fetchArray['date_created'],
            $ret
        );
        $this->assertContains(
            $fetchArray['date_updated'],
            $ret
        );
        $this->assertContains(
            __('Tracking report'),
            $ret
        );
        $this->assertContains(
            __('Structure snapshot'),
            $ret
        );
        $html = sprintf(
            __('Deactivate tracking for %s'),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertContains(
            $html,
            $ret
        );

        //restore DBI
        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Tests for PMA_getHtmlForSelectableTables() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForSelectableTables()
    {
        $selectable_tables_sql_result = true;
        $url_query = "select * from PMA";

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchArray = array(
                'tracking_active' => 1,
                'version' => "10",
                'db_name' => 'db_name',
                'table_name' => 'table_name',
                'date_created' => 'date_created',
                'date_updated' => 'date_updated'
        );
        $dbi->expects($this->at(0))
            ->method('fetchArray')
            ->will($this->returnValue($fetchArray));
        $dbi->expects($this->at(1))
            ->method('fetchArray')
            ->will($this->returnValue($fetchArray));
        $dbi->expects($this->at(2))
            ->method('fetchArray')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        $ret = PMA_getHtmlForSelectableTables(
            $selectable_tables_sql_result, $url_query
        );

        $this->assertContains(
            htmlspecialchars($fetchArray['table_name']),
            $ret
        );
        $this->assertContains(
            htmlspecialchars($fetchArray['db_name']),
            $ret
        );

        //restore DBI
        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Tests for PMA_getHtmlForTrackingReport() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForTrackingReportr()
    {
        $_REQUEST['version'] = 10;
        $_REQUEST['date_from'] = "date_from";
        $_REQUEST['date_to'] = "date_to";
        $_REQUEST['users'] = "users";
        $_REQUEST['logtype'] = 'logtype';
        $url_query = "select * from PMA";
        $data = array(
            'tracking'=>'tracking',
            'ddlog' => array('ddlog'),
            'dmlog' => array('dmlog')
        );
        $url_params = array();
        $selection_schema = array();
        $selection_data = array();
        $selection_both = array();
        $filter_ts_to = array();
        $filter_ts_from = array();
        $filter_users = array();

        $html = PMA_getHtmlForTrackingReport(
            $url_query, $data, $url_params,
            $selection_schema, $selection_data,
            $selection_both, $filter_ts_to,
            $filter_ts_from, $filter_users
        );

        $this->assertContains(
            __('Tracking report'),
            $html
        );

        $this->assertContains(
            $url_query,
            $html
        );

        $this->assertContains(
            __('Tracking statements'),
            $html
        );

        $this->assertContains(
            $data['tracking'],
            $html
        );

        $version = '<form method="post" action="tbl_tracking.php'
            . PMA_URL_getCommon(
                $url_params + array(
                    'report' => 'true', 'version' => $_REQUEST['version']
                )
            );

        $this->assertContains(
            $version,
            $html
        );

        $this->assertContains(
            $version,
            $html
        );

        $this->assertContains(
            __('Structure only'),
            $html
        );

        $this->assertContains(
            __('Data only'),
            $html
        );

        $this->assertContains(
            __('Structure and data'),
            $html
        );

        $this->assertContains(
            htmlspecialchars($_REQUEST['date_from']),
            $html
        );

        $this->assertContains(
            htmlspecialchars($_REQUEST['date_to']),
            $html
        );

        $this->assertContains(
            htmlspecialchars($_REQUEST['users']),
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForDataManipulationStatements() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDataManipulationStatements()
    {
        $_REQUEST['version'] = "10";
        $data = array(
            'tracking'=>'tracking',
            'dmlog' => array(
                array(
                    'statement' => 'statement',
                    'date' => 'date',
                    'username' => 'username',
                )
            ),
            'ddlog' => array('ddlog')
        );
        $url_params = array();
        $ddlog_count = 10;
        $drop_image_or_text = "text";
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;
        $filter_users = array("*");

        $html = PMA_getHtmlForDataManipulationStatements(
            $data, $filter_users,
            $filter_ts_from, $filter_ts_to, $url_params,
            $ddlog_count, $drop_image_or_text
        );

        $this->assertContains(
            __('Date'),
            $html
        );

        $this->assertContains(
            __('Username'),
            $html
        );

        $this->assertContains(
            __('Data manipulation statement'),
            $html
        );

        $this->assertContains(
            $data['dmlog'][0]['date'],
            $html
        );

        $this->assertContains(
            $data['dmlog'][0]['username'],
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForDataDefinitionStatements() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDataDefinitionStatements()
    {
        $_REQUEST['version'] = "10";

        $data = array(
            'tracking'=>'tracking',
            'ddlog' => array(
                array(
                    'statement' => 'statement',
                    'date' => 'date',
                    'username' => 'username',
                )
            ),
            'dmlog' => array('dmlog')
        );
        $filter_users = array("*");
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;
        $url_params = array();
        $drop_image_or_text = "text";

        list($html, $count) = PMA_getHtmlForDataDefinitionStatements(
            $data, $filter_users,
            $filter_ts_from, $filter_ts_to, $url_params, $drop_image_or_text
        );

        $this->assertContains(
            __('Date'),
            $html
        );

        $this->assertContains(
            __('Username'),
            $html
        );

        $this->assertContains(
            __('Data definition statement'),
            $html
        );

        $this->assertContains(
            __('Action'),
            $html
        );

        //PMA_getHtmlForDataDefinitionStatement
        $this->assertContains(
            htmlspecialchars($data['ddlog'][0]['username']),
            $html
        );

        $this->assertEquals(
            2,
            $count
        );

    }

    /**
     * Tests for PMA_getHtmlForIndexes() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForIndexes()
    {
        $indexs = array(
            array(
                'Non_unique' => 0,
                'Packed' => '',
                'Key_name' => 'Key_name1',
                'Index_type' => 'BTREE',
                'Column_name' => 'Column_name',
                'Cardinality' => 'Cardinality',
                'Collation' => 'Collation',
                'Null' => 'Null',
                'Comment' => 'Comment',
            ),
        );

        $html = PMA_getHtmlForIndexes($indexs);

        $this->assertContains(
            __('Indexes'),
            $html
        );
        $this->assertContains(
            __('Keyname'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Unique'),
            $html
        );
        $this->assertContains(
            __('Packed'),
            $html
        );
        $this->assertContains(
            __('Column'),
            $html
        );
        $this->assertContains(
            __('Cardinality'),
            $html
        );
        // items
        $this->assertContains(
            htmlspecialchars($indexs[0]['Key_name']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($indexs[0]['Index_type']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($indexs[0]['Column_name']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($indexs[0]['Cardinality']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($indexs[0]['Collation']),
            $html
        );
    }

    /**
     * Tests for PMA_getTrackingSet() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetTrackingSet()
    {
        $_REQUEST['alter_table'] = false;
        $_REQUEST['rename_table'] = true;
        $_REQUEST['create_table'] = true;
        $_REQUEST['drop_table'] = true;
        $_REQUEST['create_index'] = false;
        $_REQUEST['drop_index'] = true;
        $_REQUEST['insert'] = true;
        $_REQUEST['update'] = false;
        $_REQUEST['delete'] = true;
        $_REQUEST['truncate'] = true;

        $tracking_set = PMA_getTrackingSet();
        $this->assertEquals(
            'RENAME TABLE,CREATE TABLE,DROP TABLE,DROP INDEX,INSERT,DELETE,TRUNCATE',
            $tracking_set
        );

        //other set to true
        $_REQUEST['alter_table'] = true;
        $_REQUEST['rename_table'] = false;
        $_REQUEST['create_table'] = false;
        $_REQUEST['drop_table'] = false;
        $_REQUEST['create_index'] = true;
        $_REQUEST['drop_index'] = false;
        $_REQUEST['insert'] = false;
        $_REQUEST['update'] = true;
        $_REQUEST['delete'] = false;
        $_REQUEST['truncate'] = false;

        $tracking_set = PMA_getTrackingSet();
        $this->assertEquals(
            'ALTER TABLE,CREATE INDEX,UPDATE',
            $tracking_set
        );
    }


    /**
     * Tests for PMA_getEntries() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetEntries()
    {
        $_REQUEST['logtype'] = 'schema';
        $data = array(
            'tracking'=>'tracking',
            'ddlog' => array(
                array(
                    'statement' => 'statement1',
                    'date' => 'date2',
                    'username' => 'username3',
                )
            ),
            'dmlog' =>  array(
                array(
                    'statement' => 'statement1',
                    'date' => 'date2',
                    'username' => 'username3',
                )
            ),
        );
        $filter_users = array("*");
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;

        $entries = PMA_getEntries(
            $data, $filter_ts_from, $filter_ts_to, $filter_users
        );
        $this->assertEquals(
            'username3',
            $entries[0]['username']
        );
        $this->assertEquals(
            'statement1',
            $entries[0]['statement']
        );
    }
}

