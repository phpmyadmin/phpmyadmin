<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;

use function __;
use function _pgettext;
use function htmlspecialchars;
use function sprintf;

/**
 * @covers \PhpMyAdmin\Tracking
 */
class TrackingTest extends AbstractTestCase
{
    /** @var Tracking $tracking */
    private $tracking;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'PMA_db';
        $GLOBALS['table'] = 'PMA_table';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['cfg']['Server']['tracking_default_statements'] = 'DELETE';

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'tracking' => 'tracking',
            'trackingwork' => true,
        ])->toArray();

        $template = new Template();
        $this->tracking = new Tracking(
            new SqlQueryForm($template),
            $template,
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );
    }

    /**
     * Tests for filter() method.
     */
    public function testFilter(): void
    {
        $data = [
            [
                'date' => '20120102',
                'username' => 'username1',
                'statement' => 'statement1',
            ],
            [
                'date' => '20130102',
                'username' => 'username2',
                'statement' => 'statement2',
            ],
        ];
        $filter_ts_from = 0;
        $filter_ts_to = 999999999999;
        $filter_users = ['username1'];

        $ret = $this->tracking->filter($data, $filter_ts_from, $filter_ts_to, $filter_users);

        self::assertSame('username1', $ret[0]['username']);
        self::assertSame('statement1', $ret[0]['statement']);
    }

    /**
     * Tests for extractTableNames() method from nested table_list.
     */
    public function testExtractTableNames(): void
    {
        $GLOBALS['cfg']['NavigationTreeTableSeparator'] = '_';

        $table_list = [
            'hello_' => [
                'is_group' => 1,
                'lovely_' => [
                    'is_group' => 1,
                    'hello_lovely_world' => ['Name' => 'hello_lovely_world'],
                    'hello_lovely_world2' => ['Name' => 'hello_lovely_world2'],
                ],
                'hello_world' => ['Name' => 'hello_world'],
            ],
        ];
        $untracked_tables = $this->tracking->extractTableNames($table_list, 'db', true);
        self::assertContains('hello_world', $untracked_tables);
        self::assertContains('hello_lovely_world', $untracked_tables);
        self::assertContains('hello_lovely_world2', $untracked_tables);
    }

    public function testGetHtmlForMain(): void
    {
        $html = $this->tracking->getHtmlForMainPage('PMA_db', 'PMA_table', [], 'ltr');

        self::assertStringContainsString('PMA_db.PMA_table', $html);
        self::assertStringContainsString('<td>date_created</td>', $html);
        self::assertStringContainsString(__('Delete version'), $html);
        self::assertStringContainsString('<div class="card mt-3">', $html);
        self::assertStringContainsString('<div class="card-header">', $html);
        self::assertStringContainsString('<div class="card-body">', $html);
        self::assertStringContainsString('<div class="card-footer">', $html);
        self::assertStringContainsString(Url::getHiddenInputs($GLOBALS['db']), $html);
        self::assertStringContainsString(sprintf(
            __('Create version %1$s of %2$s'),
            2,
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        ), $html);
        self::assertStringContainsString('<input type="checkbox" name="delete" value="true"'
            . ' checked="checked">' . "\n" . '            DELETE<br>', $html);
        self::assertStringContainsString(__('Create version'), $html);
        self::assertStringContainsString('Deactivate now', $html);
    }

    /**
     * Tests for getTableLastVersionNumber() method.
     */
    public function testGetTableLastVersionNumber(): void
    {
        $sql_result = $this->tracking->getSqlResultForSelectableTables('PMA_db');
        self::assertNotFalse($sql_result);

        $last_version = $this->tracking->getTableLastVersionNumber($sql_result);
        self::assertSame(10, $last_version);
    }

    /**
     * Tests for getSqlResultForSelectableTables() method.
     */
    public function testGetSQLResultForSelectableTables(): void
    {
        $ret = $this->tracking->getSqlResultForSelectableTables('PMA_db');

        self::assertNotFalse($ret);
    }

    /**
     * Tests for getHtmlForColumns() method.
     */
    public function testGetHtmlForColumns(): void
    {
        $columns = [
            [
                'Field' => 'Field1',
                'Type' => 'Type1',
                'Collation' => 'Collation1',
                'Null' => 'YES',
                'Extra' => 'Extra1',
                'Key' => 'PRI',
                'Comment' => 'Comment1',
            ],
            [
                'Field' => 'Field2',
                'Type' => 'Type2',
                'Collation' => 'Collation2',
                'Null' => 'No',
                'Extra' => 'Extra2',
                'Key' => 'Key2',
                'Comment' => 'Comment2',
            ],
        ];

        $html = $this->tracking->getHtmlForColumns($columns);

        self::assertStringContainsString(__('Column'), $html);
        self::assertStringContainsString(__('Type'), $html);
        self::assertStringContainsString(__('Collation'), $html);
        self::assertStringContainsString(__('Default'), $html);
        self::assertStringContainsString(__('Comment'), $html);

        //column1
        $item1 = $columns[0];
        self::assertStringContainsString(htmlspecialchars($item1['Field']), $html);
        self::assertStringContainsString(htmlspecialchars($item1['Type']), $html);
        self::assertStringContainsString(htmlspecialchars($item1['Collation']), $html);
        self::assertStringContainsString('<em>NULL</em>', $html);
        self::assertStringContainsString(htmlspecialchars($item1['Comment']), $html);

        //column2
        $item1 = $columns[1];
        self::assertStringContainsString(htmlspecialchars($item1['Field']), $html);
        self::assertStringContainsString(htmlspecialchars($item1['Type']), $html);
        self::assertStringContainsString(htmlspecialchars($item1['Collation']), $html);
        self::assertStringContainsString(_pgettext('None for default', 'None'), $html);
        self::assertStringContainsString(htmlspecialchars($item1['Comment']), $html);
    }

    /**
     * Tests for getListOfVersionsOfTable() method.
     */
    public function testGetListOfVersionsOfTable(): void
    {
        $ret = $this->tracking->getListOfVersionsOfTable('PMA_db', 'PMA_table');

        self::assertNotFalse($ret);
    }

    /**
     * Tests for getHtmlForTrackingReport() method.
     */
    public function testGetHtmlForTrackingReportr(): void
    {
        $_POST['version'] = 10;
        $_POST['date_from'] = 'date_from';
        $_POST['date_to'] = 'date_to';
        $_POST['users'] = 'users';
        $_POST['logtype'] = 'logtype';
        $data = [
            'tracking' => 'tracking',
            'ddlog' => ['ddlog'],
            'dmlog' => ['dmlog'],
        ];
        $url_params = [];
        $selection_schema = false;
        $selection_data = false;
        $selection_both = false;
        $filter_ts_to = 0;
        $filter_ts_from = 0;
        $filter_users = [];

        $html = $this->tracking->getHtmlForTrackingReport(
            $data,
            $url_params,
            $selection_schema,
            $selection_data,
            $selection_both,
            $filter_ts_to,
            $filter_ts_from,
            $filter_users
        );

        self::assertStringContainsString(__('Tracking report'), $html);

        self::assertStringContainsString(__('Tracking statements'), $html);

        self::assertStringContainsString($data['tracking'], $html);

        $version = Url::getHiddenInputs($url_params + [
            'report' => 'true',
            'version' => $_POST['version'],
        ]);

        self::assertStringContainsString($version, $html);

        self::assertStringContainsString($version, $html);

        self::assertStringContainsString(__('Structure only'), $html);

        self::assertStringContainsString(__('Data only'), $html);

        self::assertStringContainsString(__('Structure and data'), $html);

        self::assertStringContainsString(htmlspecialchars($_POST['date_from']), $html);

        self::assertStringContainsString(htmlspecialchars($_POST['date_to']), $html);

        self::assertStringContainsString(htmlspecialchars($_POST['users']), $html);
    }

    /**
     * Tests for getHtmlForDataManipulationStatements() method.
     */
    public function testGetHtmlForDataManipulationStatements(): void
    {
        $_POST['version'] = '10';
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
        $drop_image_or_text = 'text';
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;
        $filter_users = ['*'];

        $html = $this->tracking->getHtmlForDataManipulationStatements(
            $data,
            $filter_users,
            $filter_ts_from,
            $filter_ts_to,
            $url_params,
            $ddlog_count,
            $drop_image_or_text
        );

        self::assertStringContainsString(__('Date'), $html);

        self::assertStringContainsString(__('Username'), $html);

        self::assertStringContainsString(__('Data manipulation statement'), $html);

        self::assertStringContainsString($data['dmlog'][0]['date'], $html);

        self::assertStringContainsString($data['dmlog'][0]['username'], $html);
    }

    /**
     * Tests for getHtmlForDataDefinitionStatements() method.
     */
    public function testGetHtmlForDataDefinitionStatements(): void
    {
        $_POST['version'] = '10';

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
        $filter_users = ['*'];
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;
        $url_params = [];
        $drop_image_or_text = 'text';

        [$html, $count] = $this->tracking->getHtmlForDataDefinitionStatements(
            $data,
            $filter_users,
            $filter_ts_from,
            $filter_ts_to,
            $url_params,
            $drop_image_or_text
        );

        self::assertStringContainsString(__('Date'), $html);

        self::assertStringContainsString(__('Username'), $html);

        self::assertStringContainsString(__('Data definition statement'), $html);

        self::assertStringContainsString(__('Action'), $html);

        //PMA_getHtmlForDataDefinitionStatement
        self::assertStringContainsString(htmlspecialchars($data['ddlog'][0]['username']), $html);

        self::assertSame(2, $count);
    }

    /**
     * Tests for getHtmlForIndexes() method.
     */
    public function testGetHtmlForIndexes(): void
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

        self::assertStringContainsString(__('Indexes'), $html);
        self::assertStringContainsString(__('Keyname'), $html);
        self::assertStringContainsString(__('Type'), $html);
        self::assertStringContainsString(__('Unique'), $html);
        self::assertStringContainsString(__('Packed'), $html);
        self::assertStringContainsString(__('Column'), $html);
        self::assertStringContainsString(__('Cardinality'), $html);
        // items
        self::assertStringContainsString(htmlspecialchars($indexs[0]['Key_name']), $html);
        self::assertStringContainsString(htmlspecialchars($indexs[0]['Index_type']), $html);
        self::assertStringContainsString(htmlspecialchars($indexs[0]['Column_name']), $html);
        self::assertStringContainsString(htmlspecialchars($indexs[0]['Cardinality']), $html);
        self::assertStringContainsString(htmlspecialchars($indexs[0]['Collation']), $html);
    }

    /**
     * Tests for getTrackingSet() method.
     */
    public function testGetTrackingSet(): void
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
        self::assertSame('RENAME TABLE,CREATE TABLE,DROP TABLE,DROP INDEX,INSERT,DELETE,TRUNCATE', $tracking_set);

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
        self::assertSame('ALTER TABLE,CREATE INDEX,UPDATE', $tracking_set);
    }

    /**
     * Tests for getEntries() method.
     */
    public function testGetEntries(): void
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
            'dmlog' => [
                [
                    'statement' => 'statement1',
                    'date' => 'date2',
                    'username' => 'username3',
                ],
            ],
        ];
        $filter_users = ['*'];
        $filter_ts_to = 9999999999;
        $filter_ts_from = 0;

        $entries = $this->tracking->getEntries($data, $filter_ts_from, $filter_ts_to, $filter_users);
        self::assertSame('username3', $entries[0]['username']);
        self::assertSame('statement1', $entries[0]['statement']);
    }
}
