<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Tracking
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

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
     * @var Tracking $tracking
     */
    private $tracking;

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
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['NavigationTreeTableSeparator'] = "_";

        $this->tracking = new Tracking();

        $_SESSION['relation'][$GLOBALS['server']] = [
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'tracking' => 'tracking',
            'trackingwork' => true
        ];

        $GLOBALS['cfg']['Server']['tracking_default_statements'] = 'DELETE';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchArray = ['version' => "10"];
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
     * Tests for filter() method.
     *
     * @return void
     * @test
     */
    public function testFilter()
    {
        $data = [
            [
                "date" => "20120102",
                "username" => "username1",
                "statement" => "statement1"
            ],
            [
                "date" => "20130102",
                "username" => "username2",
                "statement" => "statement2"
            ],
        ];
        $filter_ts_from = 0;
        $filter_ts_to = 999999999999;
        $filter_users = ["username1"];

        $ret = $this->tracking->filter(
            $data,
            $filter_ts_from,
            $filter_ts_to,
            $filter_users
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
     * Tests for extractTableNames() method from nested table_list.
     *
     * @return void
     * @test
     */
    public function testExtractTableNames()
    {
        $table_list = [
            "hello_" => [
                "is_group" => 1,
                "lovely_" => [
                    "is_group" => 1,
                    "hello_lovely_world" => [
                        "Name" => "hello_lovely_world"
                    ],
                    "hello_lovely_world2" => [
                        "Name" => "hello_lovely_world2"
                    ]
                ],
                "hello_world" => [
                    "Name" => "hello_world"
                ]
            ]
        ];
        $untracked_tables = $this->tracking->extractTableNames($table_list, 'db', true);
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
     * Tests for getHtmlForDataDefinitionAndManipulationStatements() method.
     *
     * @return void
     * @test
     */
    public function testGetHtmlForMain()
    {
        $sql_result = true;
        $last_version = 3;
        $url_params = [];
        $url_query = "select * from PMA";
        $pmaThemeImage = "themePath/img";
        $text_dir = "ltr";

        // Mock dbi
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchArray = [
            'tracking_active' => 1,
            'version' => 1,
            'db_name' => 'db_name',
            'table_name' => 'table_name',
            'date_created' => 'date_created',
            'date_updated' => 'date_updated'
        ];
        // return fetchArray for selectable entries
        for ($i = 2; $i < 6; $i++) {
            $dbi->expects($this->at($i))
                ->method('fetchArray')
                ->will($this->returnValue($fetchArray));
        }
        $dbi->expects($this->at(6))
            ->method('fetchArray')
            ->will($this->returnValue(false));
        // return fetchArray for Activate/Deactivate tracking
        for ($i = 7; $i < 13; $i++) {
            $dbi->expects($this->at($i))
                ->method('fetchArray')
                ->will($this->returnValue($fetchArray));
        }
        $dbi->expects($this->at(13))
            ->method('fetchArray')
            ->will($this->returnValue(false));

        $dbi->method('numRows')
            ->will($this->returnValue(1));

        $GLOBALS['dbi'] = $dbi;

        $html = $this->tracking->getHtmlForMainPage(
            $url_query,
            $url_params,
            $pmaThemeImage,
            $text_dir,
            $last_version
        );

        /*
         * test selectables panel
         */
        $this->assertContains(
            htmlspecialchars($fetchArray['db_name']).'.'.htmlspecialchars($fetchArray['table_name']),
            $html
        );

        /*
         * test versions table
         */
         $this->assertContains(
                "<td>date_created</td>",
                $html
         );
         $this->assertContains(
                __('Delete version'),
                $html
         );

        /*
         * test create panel
         */
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

        /*
         * test deactivate/activate panel
         */
        $this->assertContains(
            'Deactivate now',
            $html
        );
        $fetchArray['tracking_active'] = 0;
        $dbi->expects($this->at(9))
            ->method('fetchArray')
            ->will($this->returnValue($fetchArray));
        $GLOBALS['dbi'] = $dbi;
        $html = $this->tracking->getHtmlForMainPage(
            $url_query,
            $url_params,
            $pmaThemeImage,
            $text_dir,
            $last_version
        );
       $this->assertContains(
           'Activate now',
           $html
       );


        //restore DBI
        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Tests for getTableLastVersionNumber() method.
     *
     * @return void
     * @test
     */
    public function testGetTableLastVersionNumber()
    {
        $sql_result = "sql_result";
        $last_version = $this->tracking->getTableLastVersionNumber($sql_result);

        $this->assertEquals(
            "10",
            $last_version
        );
    }

    /**
     * Tests for getSqlResultForSelectableTables() method.
     *
     * @return void
     * @test
     */
    public function testGetSQLResultForSelectableTables()
    {
        $ret = $this->tracking->getSqlResultForSelectableTables();

        $this->assertEquals(
            true,
            $ret
        );
    }

    /**
     * Tests for getHtmlForColumns() method.
     *
     * @return void
     * @test
     */
    public function testGetHtmlForColumns()
    {
        $columns = [
            [
                'Field' => 'Field1',
                'Type' => 'Type1',
                'Collation' => 'Collation1',
                "Null" => 'YES',
                'Extra' => 'Extra1',
                'Key' => 'PRI',
                'Comment' => 'Comment1'
            ],
            [
                'Field' => 'Field2',
                'Type' => 'Type2',
                'Collation' => 'Collation2',
                "Null" => 'No',
                'Extra' => 'Extra2',
                'Key' => 'Key2',
                'Comment' => 'Comment2'
            ],
        ];

        $html = $this->tracking->getHtmlForColumns($columns);

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
        $item1 = $columns[0];
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
        $item1 = $columns[1];
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
     * Tests for getListOfVersionsOfTable() method.
     *
     * @return void
     * @test
     */
    public function testGetListOfVersionsOfTable()
    {
        $ret = $this->tracking->getListOfVersionsOfTable();

        $this->assertEquals(
            true,
            $ret
        );
    }

    /**
     * Tests for getHtmlForTrackingReport() method.
     *
     * @return void
     * @test
     */
    public function testGetHtmlForTrackingReportr()
    {
        $_REQUEST['version'] = 10;
        $_REQUEST['date_from'] = "date_from";
        $_REQUEST['date_to'] = "date_to";
        $_REQUEST['users'] = "users";
        $_REQUEST['logtype'] = 'logtype';
        $url_query = "select * from PMA";
        $data = [
            'tracking' => 'tracking',
            'ddlog' => ['ddlog'],
            'dmlog' => ['dmlog']
        ];
        $url_params = [];
        $selection_schema = [];
        $selection_data = [];
        $selection_both = [];
        $filter_ts_to = [];
        $filter_ts_from = [];
        $filter_users = [];

        $html = $this->tracking->getHtmlForTrackingReport(
            $url_query,
            $data,
            $url_params,
            $selection_schema,
            $selection_data,
            $selection_both,
            $filter_ts_to,
            $filter_ts_from,
            $filter_users
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
            . Url::getCommon(
                $url_params + [
                    'report' => 'true', 'version' => $_REQUEST['version']
                ]
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
     * Tests for getHtmlForDataManipulationStatements() method.
     *
     * @return void
     * @test
     */
    public function testGetHtmlForDataManipulationStatements()
    {
        $_REQUEST['version'] = "10";
        $data = [
            'tracking' => 'tracking',
            'dmlog' => [
                [
                    'statement' => 'statement',
                    'date' => 'date',
                    'username' => 'username',
                ]
            ],
            'ddlog' => ['ddlog']
        ];
        $url_params = [];
        $ddlog_count = 10;
        $drop_image_or_text = "text";
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;
        $filter_users = ["*"];

        $html = $this->tracking->getHtmlForDataManipulationStatements(
            $data,
            $filter_users,
            $filter_ts_from,
            $filter_ts_to,
            $url_params,
            $ddlog_count,
            $drop_image_or_text
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
     * Tests for getHtmlForDataDefinitionStatements() method.
     *
     * @return void
     * @test
     */
    public function testGetHtmlForDataDefinitionStatements()
    {
        $_REQUEST['version'] = "10";

        $data = [
            'tracking' => 'tracking',
            'ddlog' => [
                [
                    'statement' => 'statement',
                    'date' => 'date',
                    'username' => 'username',
                ]
            ],
            'dmlog' => ['dmlog']
        ];
        $filter_users = ["*"];
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;
        $url_params = [];
        $drop_image_or_text = "text";

        list($html, $count) = $this->tracking->getHtmlForDataDefinitionStatements(
            $data,
            $filter_users,
            $filter_ts_from,
            $filter_ts_to,
            $url_params,
            $drop_image_or_text
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
     * Tests for getHtmlForIndexes() method.
     *
     * @return void
     * @test
     */
    public function testGetHtmlForIndexes()
    {
        $indexs = [
            [
                'Non_unique' => 0,
                'Packed' => '',
                'Key_name' => 'Key_name1',
                'Index_type' => 'BTREE',
                'Column_name' => 'Column_name',
                'Cardinality' => 'Cardinality',
                'Collation' => 'Collation',
                'Null' => 'Null',
                'Comment' => 'Comment',
            ],
        ];

        $html = $this->tracking->getHtmlForIndexes($indexs);

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
     * Tests for getTrackingSet() method.
     *
     * @return void
     * @test
     */
    public function testGetTrackingSet()
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

        $tracking_set = $this->tracking->getTrackingSet();
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

        $tracking_set = $this->tracking->getTrackingSet();
        $this->assertEquals(
            'ALTER TABLE,CREATE INDEX,UPDATE',
            $tracking_set
        );
    }


    /**
     * Tests for getEntries() method.
     *
     * @return void
     * @test
     */
    public function testGetEntries()
    {
        $_REQUEST['logtype'] = 'schema';
        $data = [
            'tracking' => 'tracking',
            'ddlog' => [
                [
                    'statement' => 'statement1',
                    'date' => 'date2',
                    'username' => 'username3',
                ]
            ],
            'dmlog' =>  [
                [
                    'statement' => 'statement1',
                    'date' => 'date2',
                    'username' => 'username3',
                ]
            ],
        ];
        $filter_users = ["*"];
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;

        $entries = $this->tracking->getEntries(
            $data,
            $filter_ts_from,
            $filter_ts_to,
            $filter_users
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
