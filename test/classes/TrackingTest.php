<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Tracking
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PhpMyAdmin\Tracking
 *
 * @package PhpMyAdmin-test
 */
class TrackingTest extends TestCase
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
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = "PMA_db";
        $GLOBALS['table'] = "PMA_table";
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['NavigationTreeTableSeparator'] = "_";

        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'tracking' => 'tracking',
            'trackingwork' => true
        );

        $GLOBALS['cfg']['Server']['tracking_default_statements'] = 'DELETE';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
     * Tests for Tracking::filterTracking() method.
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

        $ret = Tracking::filterTracking(
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
     * Tests for Tracking::extractTableNames() method from nested table_list.
     *
     * @return void
     * @test
     */
    public function testPMAextractTableNames()
    {
        $table_list = array(
            "hello_"=>array(
                "is_group"=>1,
                "lovely_"=>array(
                    "is_group"=>1,
                    "hello_lovely_world"=>array(
                        "Name"=>"hello_lovely_world"
                    ),
                    "hello_lovely_world2"=>array(
                        "Name"=>"hello_lovely_world2"
                    )
                ),
                "hello_world"=>array(
                    "Name"=>"hello_world"
                )
            )
        );
        $untracked_tables = Tracking::extractTableNames($table_list, 'db', true);
        $this->assertContains(
            "hello_world",
            $untracked_tables
        );
        $this->assertContains(
            "hello_lovely_world",
            $untracked_tables
        );
        $this->assertContains(
            "hello_lovely_world2",
            $untracked_tables
        );
    }

    /**
     * Tests for Tracking::getHtmlForDataDefinitionAndManipulationStatements() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDataDefinitionAndManipulationStatements()
    {
        $url_query = "url_query";
        $last_version = 10;
        $html = Tracking::getHtmlForDataDefinitionAndManipulationStatements(
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
            Url::getHiddenInputs($GLOBALS['db']),
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
        . ' checked="checked">' . "\n" . '            DELETE<br/>';
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
     * Tests for Tracking::getHtmlForActivateDeactivateTracking() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForActivateDeactivateTracking()
    {
        $url_query = "url_query";
        $last_version = "10";
        $html = Tracking::getHtmlForActivateDeactivateTracking(
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

        $html = Tracking::getHtmlForActivateDeactivateTracking(
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
     * Tests for Tracking::getTableLastVersionNumber() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetTableLastVersionNumber()
    {
        $sql_result = "sql_result";
        $last_version = Tracking::getTableLastVersionNumber($sql_result);

        $this->assertEquals(
            "10",
            $last_version
        );
    }

    /**
     * Tests for Tracking::getSqlResultForSelectableTables() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetSQLResultForSelectableTables()
    {
        $ret = Tracking::getSqlResultForSelectableTables();

        $this->assertEquals(
            true,
            $ret
        );
    }

    /**
     * Tests for Tracking::getHtmlForColumns() method.
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

        $html = Tracking::getHtmlForColumns($columns);

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
            '<em>NULL</em>',
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
     * Tests for Tracking::getListOfVersionsOfTable() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetListOfVersionsOfTable()
    {
        $ret = Tracking::getListOfVersionsOfTable();

        $this->assertEquals(
            true,
            $ret
        );
    }

    /**
     * Tests for Tracking::getHtmlForTableVersionDetails() method.
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
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $ret = Tracking::getHtmlForTableVersionDetails(
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
     * Tests for Tracking::getHtmlForSelectableTables() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForSelectableTables()
    {
        $selectable_tables_sql_result = true;
        $url_query = "select * from PMA";

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $ret = Tracking::getHtmlForSelectableTables(
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
     * Tests for Tracking::getHtmlForTrackingReport() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForTrackingReportr()
    {
        $_POST['version'] = 10;
        $_POST['date_from'] = "date_from";
        $_POST['date_to'] = "date_to";
        $_POST['users'] = "users";
        $_POST['logtype'] = 'logtype';
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

        $html = Tracking::getHtmlForTrackingReport(
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

        $version = Url::getHiddenInputs($url_params + [
            'report' => 'true',
            'version' => $_POST['version'],
        ]);

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
            htmlspecialchars($_POST['date_from']),
            $html
        );

        $this->assertContains(
            htmlspecialchars($_POST['date_to']),
            $html
        );

        $this->assertContains(
            htmlspecialchars($_POST['users']),
            $html
        );
    }

    /**
     * Tests for Tracking::getHtmlForDataManipulationStatements() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDataManipulationStatements()
    {
        $_POST['version'] = "10";
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

        $html = Tracking::getHtmlForDataManipulationStatements(
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
     * Tests for Tracking::getHtmlForDataDefinitionStatements() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDataDefinitionStatements()
    {
        $_POST['version'] = "10";

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

        list($html, $count) = Tracking::getHtmlForDataDefinitionStatements(
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
     * Tests for Tracking::getHtmlForIndexes() method.
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

        $html = Tracking::getHtmlForIndexes($indexs);

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
     * Tests for Tracking::getTrackingSet() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetTrackingSet()
    {
        $_POST['alter_table'] = false;
        $_POST['rename_table'] = true;
        $_POST['create_table'] = true;
        $_POST['drop_table'] = true;
        $_POST['create_index'] = false;
        $_POST['drop_index'] = true;
        $_POST['insert'] = true;
        $_POST['update'] = false;
        $_POST['delete'] = true;
        $_POST['truncate'] = true;

        $tracking_set = Tracking::getTrackingSet();
        $this->assertEquals(
            'RENAME TABLE,CREATE TABLE,DROP TABLE,DROP INDEX,INSERT,DELETE,TRUNCATE',
            $tracking_set
        );

        //other set to true
        $_POST['alter_table'] = true;
        $_POST['rename_table'] = false;
        $_POST['create_table'] = false;
        $_POST['drop_table'] = false;
        $_POST['create_index'] = true;
        $_POST['drop_index'] = false;
        $_POST['insert'] = false;
        $_POST['update'] = true;
        $_POST['delete'] = false;
        $_POST['truncate'] = false;

        $tracking_set = Tracking::getTrackingSet();
        $this->assertEquals(
            'ALTER TABLE,CREATE INDEX,UPDATE',
            $tracking_set
        );
    }


    /**
     * Tests for Tracking::getEntries() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetEntries()
    {
        $_POST['logtype'] = 'schema';
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

        $entries = Tracking::getEntries(
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
