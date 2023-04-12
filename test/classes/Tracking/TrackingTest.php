<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Tracking;

use DateTimeImmutable;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tracking\LogTypeEnum;
use PhpMyAdmin\Tracking\TrackedData;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use ReflectionClass;

use function __;
use function _pgettext;
use function date;
use function htmlspecialchars;
use function ini_get;
use function ini_restore;
use function ini_set;
use function sprintf;

/** @covers \PhpMyAdmin\Tracking\Tracking */
class TrackingTest extends AbstractTestCase
{
    private Tracking $tracking;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setTheme();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'PMA_db';
        $GLOBALS['table'] = 'PMA_table';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['cfg']['Server']['tracking_default_statements'] = 'DELETE';

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'tracking' => 'tracking',
            'trackingwork' => true,
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $template = new Template();
        $this->tracking = new Tracking(
            new SqlQueryForm($template, $GLOBALS['dbi']),
            $template,
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );
    }

    /**
     * Tests for filter() method.
     */
    public function testFilter(): void
    {
        $data = [
            ['date' => '2012-01-01 12:34:56', 'username' => 'username1', 'statement' => 'statement1'],
            ['date' => '2013-01-01 12:34:56', 'username' => 'username2', 'statement' => 'statement2'],
        ];
        $filterUsers = ['username1'];

        $ret = $this->tracking->filter(
            $data,
            $filterUsers,
            new DateTimeImmutable('2010-01-01 12:34:56'),
            new DateTimeImmutable('2020-01-01 12:34:56'),
        );

        $this->assertEquals('username1', $ret[0]['username']);
        $this->assertEquals('statement1', $ret[0]['statement']);
    }

    public function testGetHtmlForMain(): void
    {
        $html = $this->tracking->getHtmlForMainPage('PMA_db', 'PMA_table', [], 'ltr');

        $this->assertStringContainsString('PMA_db.PMA_table', $html);
        $this->assertStringContainsString('<td>date_created</td>', $html);
        $this->assertStringContainsString(__('Delete version'), $html);
        $this->assertStringContainsString('<div class="card mt-3">', $html);
        $this->assertStringContainsString('<div class="card-header">', $html);
        $this->assertStringContainsString('<div class="card-body">', $html);
        $this->assertStringContainsString('<div class="card-footer">', $html);
        $this->assertStringContainsString(Url::getHiddenInputs($GLOBALS['db']), $html);
        $this->assertStringContainsString(
            sprintf(
                __('Create version %1$s of %2$s'),
                2,
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table']),
            ),
            $html,
        );
        $this->assertStringContainsString(
            '<input type="checkbox" name="delete" value="true"'
                . ' checked="checked">' . "\n" . '            DELETE<br>',
            $html,
        );
        $this->assertStringContainsString(__('Create version'), $html);
        $this->assertStringContainsString('Deactivate now', $html);
    }

    /**
     * Tests for getTableLastVersionNumber() method.
     */
    public function testGetTableLastVersionNumber(): void
    {
        $sqlResult = $this->tracking->getListOfVersionsOfTable('PMA_db', 'PMA_table');
        $this->assertNotFalse($sqlResult);

        $lastVersion = $this->tracking->getTableLastVersionNumber($sqlResult);
        $this->assertSame(1, $lastVersion);
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

        $this->assertStringContainsString(
            __('Column'),
            $html,
        );
        $this->assertStringContainsString(
            __('Type'),
            $html,
        );
        $this->assertStringContainsString(
            __('Collation'),
            $html,
        );
        $this->assertStringContainsString(
            __('Default'),
            $html,
        );
        $this->assertStringContainsString(
            __('Comment'),
            $html,
        );

        //column1
        $item1 = $columns[0];
        $this->assertStringContainsString(
            htmlspecialchars($item1['Field']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Type']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Collation']),
            $html,
        );
        $this->assertStringContainsString('<em>NULL</em>', $html);
        $this->assertStringContainsString(
            htmlspecialchars($item1['Comment']),
            $html,
        );

        //column2
        $item1 = $columns[1];
        $this->assertStringContainsString(
            htmlspecialchars($item1['Field']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Type']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Collation']),
            $html,
        );
        $this->assertStringContainsString(
            _pgettext('None for default', 'None'),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($item1['Comment']),
            $html,
        );
    }

    /**
     * Tests for getListOfVersionsOfTable() method.
     */
    public function testGetListOfVersionsOfTable(): void
    {
        $ret = $this->tracking->getListOfVersionsOfTable('PMA_db', 'PMA_table');

        $this->assertNotFalse($ret);
    }

    /**
     * Tests for getHtmlForTrackingReport() method.
     */
    public function testGetHtmlForTrackingReportr(): void
    {
        $data = new TrackedData(
            '',
            '',
            [['statement' => 'statement', 'date' => '2022-11-02 22:15:24', 'username' => 'username']],
            [['statement' => 'statement', 'date' => '2022-11-02 22:15:24', 'username' => 'username']],
            'tracking',
            '',
        );
        $urlParams = [];
        $filterUsers = [];

        $html = $this->tracking->getHtmlForTrackingReport(
            $data,
            $urlParams,
            'schema_and_data',
            $filterUsers,
            '10',
            new DateTimeImmutable('2022-11-03 22:15:24'),
            new DateTimeImmutable('2022-11-04 22:15:24'),
            'users',
        );

        $this->assertStringContainsString(
            __('Tracking report'),
            $html,
        );

        $this->assertStringContainsString(
            __('Tracking statements'),
            $html,
        );

        $this->assertStringContainsString($data->tracking, $html);

        $version = Url::getHiddenInputs($urlParams + ['report' => 'true', 'version' => '10']);

        $this->assertStringContainsString($version, $html);

        $this->assertStringContainsString($version, $html);

        $this->assertStringContainsString(
            __('Structure only'),
            $html,
        );

        $this->assertStringContainsString(
            __('Data only'),
            $html,
        );

        $this->assertStringContainsString(
            __('Structure and data'),
            $html,
        );

        $this->assertStringContainsString('2022-11-03 22:15:24', $html);
        $this->assertStringContainsString('2022-11-04 22:15:24', $html);
        $this->assertStringContainsString('users', $html);
    }

    /**
     * Tests for getHtmlForDataManipulationStatements() method.
     */
    public function testGetHtmlForDataManipulationStatements(): void
    {
        $data = new TrackedData(
            '',
            '',
            [],
            [['statement' => 'statement', 'date' => '2013-01-01 12:34:56', 'username' => 'username']],
            'tracking',
            '',
        );
        $urlParams = [];
        $ddlogCount = 10;
        $dropImageOrText = 'text';
        $filterUsers = ['*'];

        $html = $this->tracking->getHtmlForDataManipulationStatements(
            $data,
            $filterUsers,
            $urlParams,
            $ddlogCount,
            $dropImageOrText,
            '10',
            new DateTimeImmutable('2010-01-01 12:34:56'),
            new DateTimeImmutable('2020-01-01 12:34:56'),
        );

        $this->assertStringContainsString(
            __('Date'),
            $html,
        );

        $this->assertStringContainsString(
            __('Username'),
            $html,
        );

        $this->assertStringContainsString(
            __('Data manipulation statement'),
            $html,
        );

        $this->assertStringContainsString($data->dmlog[0]['date'], $html);

        $this->assertStringContainsString($data->dmlog[0]['username'], $html);
    }

    /**
     * Tests for getHtmlForDataDefinitionStatements() method.
     */
    public function testGetHtmlForDataDefinitionStatements(): void
    {
        $data = new TrackedData(
            '',
            '',
            [['statement' => 'statement', 'date' => '2013-01-01 12:34:56', 'username' => 'username']],
            [],
            'tracking',
            '',
        );
        $filterUsers = ['*'];
        $urlParams = [];
        $dropImageOrText = 'text';

        [$html, $count] = $this->tracking->getHtmlForDataDefinitionStatements(
            $data,
            $filterUsers,
            $urlParams,
            $dropImageOrText,
            '10',
            new DateTimeImmutable('2010-01-01 12:34:56'),
            new DateTimeImmutable('2020-01-01 12:34:56'),
        );

        $this->assertStringContainsString(
            __('Date'),
            $html,
        );

        $this->assertStringContainsString(
            __('Username'),
            $html,
        );

        $this->assertStringContainsString(
            __('Data definition statement'),
            $html,
        );

        $this->assertStringContainsString(
            __('Action'),
            $html,
        );

        //PMA_getHtmlForDataDefinitionStatement
        $this->assertStringContainsString(
            htmlspecialchars($data->ddlog[0]['username']),
            $html,
        );

        $this->assertEquals(2, $count);
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

        $this->assertStringContainsString(
            __('Indexes'),
            $html,
        );
        $this->assertStringContainsString(
            __('Keyname'),
            $html,
        );
        $this->assertStringContainsString(
            __('Type'),
            $html,
        );
        $this->assertStringContainsString(
            __('Unique'),
            $html,
        );
        $this->assertStringContainsString(
            __('Packed'),
            $html,
        );
        $this->assertStringContainsString(
            __('Column'),
            $html,
        );
        $this->assertStringContainsString(
            __('Cardinality'),
            $html,
        );
        // items
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Key_name']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Index_type']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Column_name']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Cardinality']),
            $html,
        );
        $this->assertStringContainsString(
            htmlspecialchars($indexs[0]['Collation']),
            $html,
        );
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

        $trackingSet = $this->tracking->getTrackingSet();
        $this->assertEquals('RENAME TABLE,CREATE TABLE,DROP TABLE,DROP INDEX,INSERT,DELETE,TRUNCATE', $trackingSet);

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

        $trackingSet = $this->tracking->getTrackingSet();
        $this->assertEquals('ALTER TABLE,CREATE INDEX,UPDATE', $trackingSet);
    }

    /**
     * Tests for getEntries() method.
     */
    public function testGetEntries(): void
    {
        $data = new TrackedData(
            '',
            '',
            [['statement' => 'statement1', 'date' => '2012-01-01 12:34:56', 'username' => 'username3']],
            [['statement' => 'statement1', 'date' => '2013-01-01 12:34:56', 'username' => 'username3']],
            'tracking',
            '',
        );
        $filterUsers = ['*'];

        $entries = $this->tracking->getEntries(
            $data,
            $filterUsers,
            'schema',
            new DateTimeImmutable('2010-01-01 12:34:56'),
            new DateTimeImmutable('2020-01-01 12:34:56'),
        );
        $this->assertEquals('username3', $entries[0]['username']);
        $this->assertEquals('statement1', $entries[0]['statement']);
    }

    public function testGetDownloadInfoForExport(): void
    {
        $tracking = new Tracking(
            $this->createStub(SqlQueryForm::class),
            $this->createStub(Template::class),
            $this->createStub(Relation::class),
            $this->createStub(DatabaseInterface::class),
            $this->createStub(TrackingChecker::class),
        );
        ini_set('url_rewriter.tags', 'a=href,area=href,frame=src,form=,fieldset=');
        $entries = [['statement' => 'first statement'], ['statement' => 'second statement']];
        $expectedDump = '# Tracking report for table `test&gt; table`' . "\n"
            . '# ' . date('Y-m-d H:i:sP') . "\n"
            . 'first statementsecond statement';
        $actual = $tracking->getDownloadInfoForExport('test>  table', $entries);
        $this->assertSame('log_test&gt; table.sql', $actual['filename']);
        $this->assertSame($expectedDump, $actual['dump']);
        $this->assertSame('', ini_get('url_rewriter.tags'));
        ini_restore('url_rewriter.tags');
    }

    /**
     * Test for deleteTracking()
     */
    public function testDeleteTracking(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlQuery = "/*NOTRACK*/\n"
            . 'DELETE FROM `pmadb`.`tracking`'
            . " WHERE `db_name` = 'testdb'"
            . " AND `table_name` = 'testtable'";

        $dbi->expects($this->exactly(1))
            ->method('queryAsControlUser')
            ->with($sqlQuery)
            ->will($this->returnValue($resultStub));
        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $tracking = new Tracking(
            $this->createStub(SqlQueryForm::class),
            $this->createStub(Template::class),
            new Relation($GLOBALS['dbi']),
            $dbi,
            $this->createStub(TrackingChecker::class),
        );
        $this->assertTrue($tracking->deleteTracking('testdb', 'testtable'));
    }

    /**
     * Test for changeTrackingData()
     */
    public function testChangeTrackingData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlQuery1 = 'UPDATE `pmadb`.`tracking`' .
        " SET `schema_sql` = '# new_data_processed'" .
        " WHERE `db_name` = 'pma_db'" .
        " AND `table_name` = 'pma_table'" .
        " AND `version` = '1.0'";

        $date = Util::date('Y-m-d H:i:s');

        $newData = [
            ['date' => $date, 'username' => 'user1', 'statement' => 'test_statement1'],
            ['date' => $date, 'username' => 'user2', 'statement' => 'test_statement2'],
        ];

        $sqlQuery2 = 'UPDATE `pmadb`.`tracking`' .
        " SET `data_sql` = '# log " . $date . " user1test_statement1\n" .
        '# log ' . $date . " user2test_statement2\n'" .
        " WHERE `db_name` = 'pma_db'" .
        " AND `table_name` = 'pma_table'" .
        " AND `version` = '1.0'";

        $resultStub1 = $this->createMock(DummyResult::class);
        $resultStub2 = $this->createMock(DummyResult::class);

        $dbi->method('queryAsControlUser')
            ->will(
                $this->returnValueMap(
                    [[$sqlQuery1, $resultStub1], [$sqlQuery2, $resultStub2]],
                ),
            );

        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $tracking = new Tracking(
            $this->createStub(SqlQueryForm::class),
            $this->createStub(Template::class),
            new Relation($GLOBALS['dbi']),
            $dbi,
            $this->createStub(TrackingChecker::class),
        );

        $this->assertTrue(
            $tracking->changeTrackingData(
                'pma_db',
                'pma_table',
                '1.0',
                LogTypeEnum::DML,
                $newData,
            ),
        );
    }

    /**
     * Test for getTrackedData()
     *
     * @param mixed[]     $fetchArrayReturn Value to be returned by mocked fetchArray
     * @param TrackedData $expected         Expected value
     *
     * @dataProvider getTrackedDataProvider
     */
    public function testGetTrackedData(array $fetchArrayReturn, TrackedData $expected): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('queryAsControlUser')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue($fetchArrayReturn));

        $tracking = new Tracking(
            $this->createStub(SqlQueryForm::class),
            $this->createStub(Template::class),
            new Relation($GLOBALS['dbi']),
            $dbi,
            $this->createStub(TrackingChecker::class),
        );

        $result = $tracking->getTrackedData("pma'db", "pma'table", '1.0');

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for testGetTrackedData
     *
     * @return mixed[] Test data
     */
    public static function getTrackedDataProvider(): array
    {
        $fetchArrayReturn = [
            [
                'schema_sql' => "# log 20-03-2013 23:33:58 user1\nstat1" .
                "# log 20-03-2013 23:39:58 user2\n",
                'data_sql' => '# log ',
                'schema_snapshot' => 'dataschema',
                'tracking' => 'SELECT, DELETE',
            ],
        ];

        $data = [
            new TrackedData(
                '20-03-2013 23:33:58',
                '20-03-2013 23:39:58',
                [
                    ['date' => '20-03-2013 23:33:58', 'username' => 'user1', 'statement' => "\nstat1"],
                    ['date' => '20-03-2013 23:39:58', 'username' => 'user2', 'statement' => ''],
                ],
                [],
                'SELECT, DELETE',
                'dataschema',
            ),
        ];

        $fetchArrayReturn[1] = [
            'schema_sql' => "# log 20-03-2012 23:33:58 user1\n" .
            "# log 20-03-2012 23:39:58 user2\n",
            'data_sql' => "# log 20-03-2013 23:33:58 user3\n" .
            "# log 20-03-2013 23:39:58 user4\n",
            'schema_snapshot' => 'dataschema',
            'tracking' => 'SELECT, DELETE',
        ];

        $data[1] = new TrackedData(
            '20-03-2012 23:33:58',
            '20-03-2013 23:39:58',
            [
                ['date' => '20-03-2012 23:33:58', 'username' => 'user1', 'statement' => ''],
                ['date' => '20-03-2012 23:39:58', 'username' => 'user2', 'statement' => ''],
            ],
            [
                ['date' => '20-03-2013 23:33:58', 'username' => 'user3', 'statement' => ''],
                ['date' => '20-03-2013 23:39:58', 'username' => 'user4', 'statement' => ''],
            ],
            'SELECT, DELETE',
            'dataschema',
        );

        return [[$fetchArrayReturn[0], $data[0]], [$fetchArrayReturn[1], $data[1]]];
    }
}
