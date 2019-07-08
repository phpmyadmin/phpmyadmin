<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Tracking
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
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
    protected function setUp(): void
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

        $_SESSION['relation'][$GLOBALS['server']] = [
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'tracking' => 'tracking',
            'trackingwork' => true,
        ];

        $GLOBALS['cfg']['Server']['tracking_default_statements'] = 'DELETE';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
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

        $this->tracking = new Tracking(new SqlQueryForm(), new Template(), new Relation($dbi));
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
                "statement" => "statement1",
            ],
            [
                "date" => "20130102",
                "username" => "username2",
                "statement" => "statement2",
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
                        "Name" => "hello_lovely_world",
                    ],
                    "hello_lovely_world2" => [
                        "Name" => "hello_lovely_world2",
                    ],
                ],
                "hello_world" => [
                    "Name" => "hello_world",
                ],
            ],
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
            'db_name' => 'PMA_db',
            'table_name' => 'PMA_table',
            'date_created' => 'date_created',
            'date_updated' => 'date_updated',
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

        /* Here, we need to overwrite the object written in the setUp function because $dbi object is not the one mocked
        at the beginning. */
        $this->tracking = new Tracking(new SqlQueryForm(), new Template(), new Relation($dbi));

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
        $this->assertStringContainsString(
            htmlspecialchars($fetchArray['db_name']) . '.' . htmlspecialchars($fetchArray['table_name']),
            $html
        );

        /*
         * test versions table
         */
         $this->assertStringContainsString(
             "<td>date_created</td>",
             $html
         );
         $this->assertStringContainsString(
             __('Delete version'),
             $html
         );

        /*
         * test create panel
         */
        $this->assertStringContainsString(
            '<div id="div_create_version">',
            $html
        );

        $this->assertStringContainsString(
            $url_query,
            $html
        );

        $this->assertStringContainsString(
            Url::getHiddenInputs($GLOBALS['db']),
            $html
        );

        $item = sprintf(
            __('Create version %1$s of %2$s'),
            ($last_version + 1),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertStringContainsString(
            $item,
            $html
        );

        $item = '<input type="checkbox" name="delete" value="true"'
        . ' checked="checked">' . "\n" . '            DELETE<br>';
        $this->assertStringContainsString(
            $item,
            $html
        );

        $this->assertStringContainsString(
            __('Create version'),
            $html
        );

        /*
         * test deactivate/activate panel
         */
        $this->assertStringContainsString(
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
        $this->assertStringContainsString(
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
                'Comment' => 'Comment1',
            ],
            [
                'Field' => 'Field2',
                'Type' => 'Type2',
                'Collation' => 'Collation2',
                "Null" => 'No',
                'Extra' => 'Extra2',
                'Key' => 'Key2',
                'Comment' => 'Comment2',
            ],
        ];

        $html = $this->tracking->getHtmlForColumns($columns);

        $this->assertStringContainsString(
            __('Column'),
            $html
        );
        $this->assertStringContainsString(
            __('Type'),
            $html
        );
        $this->assertStringContainsString(
            __('Collation'),
            $html
        );
        $this->assertStringContainsString(
            __('Default'),
            $html
        );
        $this->assertStringContainsString(
            __('Comment'),
            $html
        );

        //column1
        $item1 = $columns[0];
        $this->assertStringContainsString(
            htmlspecialchars($item1['Field']),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Type']),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Collation']),
            $html
        );
        $this->assertStringContainsString(
            '<em>NULL</em>',
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Comment']),
            $html
        );

        //column2
        $item1 = $columns[1];
        $this->assertStringContainsString(
            htmlspecialchars($item1['Field']),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Type']),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Collation']),
            $html
        );
        $this->assertStringContainsString(
            _pgettext('None for default', 'None'),
            $html
        );
        $this->assertStringContainsString(
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
        $_POST['version'] = 10;
        $_POST['date_from'] = "date_from";
        $_POST['date_to'] = "date_to";
        $_POST['users'] = "users";
        $_POST['logtype'] = 'logtype';
        $url_query = "select * from PMA";
        $data = [
            'tracking' => 'tracking',
            'ddlog' => ['ddlog'],
            'dmlog' => ['dmlog'],
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

        $this->assertStringContainsString(
            __('Tracking report'),
            $html
        );

        $this->assertStringContainsString(
            $url_query,
            $html
        );

        $this->assertStringContainsString(
            __('Tracking statements'),
            $html
        );

        $this->assertStringContainsString(
            $data['tracking'],
            $html
        );

        $version = Url::getHiddenInputs($url_params + [
            'report' => 'true',
            'version' => $_POST['version'],
        ]);

        $this->assertStringContainsString(
            $version,
            $html
        );

        $this->assertStringContainsString(
            $version,
            $html
        );

        $this->assertStringContainsString(
            __('Structure only'),
            $html
        );

        $this->assertStringContainsString(
            __('Data only'),
            $html
        );

        $this->assertStringContainsString(
            __('Structure and data'),
            $html
        );

        $this->assertStringContainsString(
            htmlspecialchars($_POST['date_from']),
            $html
        );

        $this->assertStringContainsString(
            htmlspecialchars($_POST['date_to']),
            $html
        );

        $this->assertStringContainsString(
            htmlspecialchars($_POST['users']),
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
        $_POST['version'] = "10";
        $data = [
            'tracking' => 'tracking',
            'dmlog' => [
                [
                    'statement' => 'statement',
                    'date' => 'date',
                    'username' => 'username',
                ],
            ],
            'ddlog' => ['ddlog'],
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

        $this->assertStringContainsString(
            __('Date'),
            $html
        );

        $this->assertStringContainsString(
            __('Username'),
            $html
        );

        $this->assertStringContainsString(
            __('Data manipulation statement'),
            $html
        );

        $this->assertStringContainsString(
            $data['dmlog'][0]['date'],
            $html
        );

        $this->assertStringContainsString(
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
        $_POST['version'] = "10";

        $data = [
            'tracking' => 'tracking',
            'ddlog' => [
                [
                    'statement' => 'statement',
                    'date' => 'date',
                    'username' => 'username',
                ],
            ],
            'dmlog' => ['dmlog'],
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

        $this->assertStringContainsString(
            __('Date'),
            $html
        );

        $this->assertStringContainsString(
            __('Username'),
            $html
        );

        $this->assertStringContainsString(
            __('Data definition statement'),
            $html
        );

        $this->assertStringContainsString(
            __('Action'),
            $html
        );

        //PMA_getHtmlForDataDefinitionStatement
        $this->assertStringContainsString(
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

        $this->assertStringContainsString(
            __('Indexes'),
            $html
        );
        $this->assertStringContainsString(
            __('Keyname'),
            $html
        );
        $this->assertStringContainsString(
            __('Type'),
            $html
        );
        $this->assertStringContainsString(
            __('Unique'),
            $html
        );
        $this->assertStringContainsString(
            __('Packed'),
            $html
        );
        $this->assertStringContainsString(
            __('Column'),
            $html
        );
        $this->assertStringContainsString(
            __('Cardinality'),
            $html
        );
        // items
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Key_name']),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Index_type']),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Column_name']),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Cardinality']),
            $html
        );
        $this->assertStringContainsString(
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

        $tracking_set = $this->tracking->getTrackingSet();
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
        $_POST['logtype'] = 'schema';
        $data = [
            'tracking' => 'tracking',
            'ddlog' => [
                [
                    'statement' => 'statement1',
                    'date' => 'date2',
                    'username' => 'username3',
                ],
            ],
            'dmlog' =>  [
                [
                    'statement' => 'statement1',
                    'date' => 'date2',
                    'username' => 'username3',
                ],
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
