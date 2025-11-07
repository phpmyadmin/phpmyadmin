<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Tracking;

use DateTimeImmutable;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tracking\LogType;
use PhpMyAdmin\Tracking\TrackedData;
use PhpMyAdmin\Tracking\TrackedDataType;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

use function __;
use function _pgettext;
use function date;
use function htmlspecialchars;
use function ini_get;
use function ini_restore;
use function ini_set;
use function sprintf;

#[CoversClass(Tracking::class)]
#[CoversClass(TrackedData::class)]
final class TrackingTest extends AbstractTestCase
{
    private Tracking $tracking;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        Current::$database = 'PMA_db';
        Current::$table = 'PMA_table';
        Current::$lang = 'en';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->selectedServer['tracking_default_statements'] = 'DELETE';

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::TRACKING => 'tracking',
            RelationParameters::TRACKING_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $template = new Template();
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $this->tracking = new Tracking(
            new SqlQueryForm($template, $dbi, $bookmarkRepository),
            $template,
            $relation,
            $dbi,
            self::createStub(TrackingChecker::class),
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

        self::assertSame('username1', $ret[0]['username']);
        self::assertSame('statement1', $ret[0]['statement']);
    }

    public function testGetHtmlForMain(): void
    {
        $html = $this->tracking->getHtmlForMainPage('PMA_db', 'PMA_table', []);

        self::assertStringContainsString('PMA_db.PMA_table', $html);
        self::assertStringContainsString('<td>date_created</td>', $html);
        self::assertStringContainsString(__('Delete version'), $html);
        self::assertStringContainsString('<div class="card mt-3">', $html);
        self::assertStringContainsString('<div class="card-header">', $html);
        self::assertStringContainsString('<div class="card-body">', $html);
        self::assertStringContainsString('<div class="card-footer">', $html);
        self::assertStringContainsString(Url::getHiddenInputs(Current::$database), $html);
        self::assertStringContainsString(
            sprintf(
                __('Create version %1$s of %2$s'),
                2,
                htmlspecialchars(Current::$database . '.' . Current::$table),
            ),
            $html,
        );
        self::assertStringContainsString(
            '<input type="checkbox" name="delete" value="true"'
                . ' checked>' . "\n" . '            DELETE<br>',
            $html,
        );
        self::assertStringContainsString(__('Create version'), $html);
        self::assertStringContainsString('Deactivate now', $html);
    }

    /**
     * Tests for getTableLastVersionNumber() method.
     */
    public function testGetTableLastVersionNumber(): void
    {
        $sqlResult = $this->tracking->getListOfVersionsOfTable('PMA_db', 'PMA_table');
        self::assertNotFalse($sqlResult);

        $lastVersion = $this->tracking->getTableLastVersionNumber($sqlResult);
        self::assertSame(1, $lastVersion);
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

        self::assertStringContainsString(
            __('Column'),
            $html,
        );
        self::assertStringContainsString(
            __('Type'),
            $html,
        );
        self::assertStringContainsString(
            __('Collation'),
            $html,
        );
        self::assertStringContainsString(
            __('Default'),
            $html,
        );
        self::assertStringContainsString(
            __('Comment'),
            $html,
        );

        //column1
        $item1 = $columns[0];
        self::assertStringContainsString(
            htmlspecialchars($item1['Field']),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($item1['Type']),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($item1['Collation']),
            $html,
        );
        self::assertStringContainsString('<em>NULL</em>', $html);
        self::assertStringContainsString(
            htmlspecialchars($item1['Comment']),
            $html,
        );

        //column2
        $item1 = $columns[1];
        self::assertStringContainsString(
            htmlspecialchars($item1['Field']),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($item1['Type']),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($item1['Collation']),
            $html,
        );
        self::assertStringContainsString(
            _pgettext('None for default', 'None'),
            $html,
        );
        self::assertStringContainsString(
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

        self::assertNotFalse($ret);
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
            LogType::SchemaAndData,
            $filterUsers,
            '10',
            new DateTimeImmutable('2022-11-03 22:15:24'),
            new DateTimeImmutable('2022-11-04 22:15:24'),
            'users',
        );

        self::assertStringContainsString(
            __('Tracking report'),
            $html,
        );

        self::assertStringContainsString(
            __('Tracking statements'),
            $html,
        );

        self::assertStringContainsString($data->tracking, $html);

        $version = Url::getHiddenInputs($urlParams + ['report' => 'true', 'version' => '10']);

        self::assertStringContainsString($version, $html);

        self::assertStringContainsString($version, $html);

        self::assertStringContainsString(
            __('Structure only'),
            $html,
        );

        self::assertStringContainsString(
            __('Data only'),
            $html,
        );

        self::assertStringContainsString(
            __('Structure and data'),
            $html,
        );

        self::assertStringContainsString('2022-11-03 22:15:24', $html);
        self::assertStringContainsString('2022-11-04 22:15:24', $html);
        self::assertStringContainsString('users', $html);
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

        self::assertStringContainsString(
            __('Date'),
            $html,
        );

        self::assertStringContainsString(
            __('Username'),
            $html,
        );

        self::assertStringContainsString(
            __('Data manipulation statement'),
            $html,
        );

        self::assertStringContainsString($data->dmlog[0]['date'], $html);

        self::assertStringContainsString($data->dmlog[0]['username'], $html);
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

        self::assertStringContainsString(
            __('Date'),
            $html,
        );

        self::assertStringContainsString(
            __('Username'),
            $html,
        );

        self::assertStringContainsString(
            __('Data definition statement'),
            $html,
        );

        self::assertStringContainsString(
            __('Action'),
            $html,
        );

        //PMA_getHtmlForDataDefinitionStatement
        self::assertStringContainsString(
            htmlspecialchars($data->ddlog[0]['username']),
            $html,
        );

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

        self::assertStringContainsString(
            __('Indexes'),
            $html,
        );
        self::assertStringContainsString(
            __('Keyname'),
            $html,
        );
        self::assertStringContainsString(
            __('Type'),
            $html,
        );
        self::assertStringContainsString(
            __('Unique'),
            $html,
        );
        self::assertStringContainsString(
            __('Packed'),
            $html,
        );
        self::assertStringContainsString(
            __('Column'),
            $html,
        );
        self::assertStringContainsString(
            __('Cardinality'),
            $html,
        );
        // items
        self::assertStringContainsString(
            htmlspecialchars($indexs[0]['Key_name']),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($indexs[0]['Index_type']),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($indexs[0]['Column_name']),
            $html,
        );
        self::assertStringContainsString(
            htmlspecialchars($indexs[0]['Cardinality']),
            $html,
        );
        self::assertStringContainsString(
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
        self::assertSame('RENAME TABLE,CREATE TABLE,DROP TABLE,DROP INDEX,INSERT,DELETE,TRUNCATE', $trackingSet);

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
        self::assertSame('ALTER TABLE,CREATE INDEX,UPDATE', $trackingSet);
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
            LogType::Schema,
            new DateTimeImmutable('2010-01-01 12:34:56'),
            new DateTimeImmutable('2020-01-01 12:34:56'),
        );
        self::assertSame('username3', $entries[0]['username']);
        self::assertSame('statement1', $entries[0]['statement']);
    }

    public function testGetDownloadInfoForExport(): void
    {
        $tracking = new Tracking(
            self::createStub(SqlQueryForm::class),
            self::createStub(Template::class),
            self::createStub(Relation::class),
            self::createStub(DatabaseInterface::class),
            self::createStub(TrackingChecker::class),
        );
        ini_set('url_rewriter.tags', 'a=href,area=href,frame=src,form=,fieldset=');
        $entries = [['statement' => 'first statement'], ['statement' => 'second statement']];
        $expectedDump = '# Tracking report for table `test&gt; table`' . "\n"
            . '# ' . date('Y-m-d H:i:sP') . "\n"
            . 'first statementsecond statement';
        $actual = $tracking->getDownloadInfoForExport('test>  table', $entries);
        self::assertSame('log_test&gt; table.sql', $actual['filename']);
        self::assertSame($expectedDump, $actual['dump']);
        self::assertSame('', ini_get('url_rewriter.tags'));
        ini_restore('url_rewriter.tags');
    }

    /**
     * Test for deleteTracking()
     */
    public function testDeleteTracking(): void
    {
        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sqlQuery = "/*NOTRACK*/\n"
            . 'DELETE FROM `pmadb`.`tracking`'
            . " WHERE `db_name` = 'testdb'"
            . " AND `table_name` = 'testtable'";

        $dbi->expects(self::exactly(1))
            ->method('queryAsControlUser')
            ->with($sqlQuery)
            ->willReturn($resultStub);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $tracking = new Tracking(
            self::createStub(SqlQueryForm::class),
            self::createStub(Template::class),
            new Relation(DatabaseInterface::getInstance()),
            $dbi,
            self::createStub(TrackingChecker::class),
        );
        self::assertTrue($tracking->deleteTracking('testdb', 'testtable'));
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

        $resultStub1 = self::createMock(DummyResult::class);
        $resultStub2 = self::createMock(DummyResult::class);

        $dbi->method('queryAsControlUser')
            ->willReturnMap([[$sqlQuery1, $resultStub1], [$sqlQuery2, $resultStub2]]);

        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $tracking = new Tracking(
            self::createStub(SqlQueryForm::class),
            self::createStub(Template::class),
            new Relation(DatabaseInterface::getInstance()),
            $dbi,
            self::createStub(TrackingChecker::class),
        );

        self::assertTrue(
            $tracking->changeTrackingData(
                'pma_db',
                'pma_table',
                '1.0',
                TrackedDataType::DML,
                $newData,
            ),
        );
    }

    /**
     * Test for getTrackedData()
     *
     * @param array<string, string> $fetchArrayReturn Value to be returned by mocked fetchArray
     * @param TrackedData           $expected         Expected value
     */
    #[DataProvider('getTrackedDataProvider')]
    public function testGetTrackedData(array $fetchArrayReturn, TrackedData $expected): void
    {
        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('queryAsControlUser')
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn($fetchArrayReturn);

        $tracking = new Tracking(
            self::createStub(SqlQueryForm::class),
            self::createStub(Template::class),
            new Relation(DatabaseInterface::getInstance()),
            $dbi,
            self::createStub(TrackingChecker::class),
        );

        $result = $tracking->getTrackedData("pma'db", "pma'table", '1.0');

        self::assertEquals($expected, $result);
    }

    /**
     * Data provider for testGetTrackedData
     *
     * @return iterable<array-key, array{array<string, string>, TrackedData}>
     */
    public static function getTrackedDataProvider(): iterable
    {
        $fetchArrayReturn = [
            'schema_sql' => "# log 20-03-2013 23:33:58 user1\nstat1" .
            "# log 20-03-2013 23:39:58 user2\n",
            'data_sql' => '# log ',
            'schema_snapshot' => 'dataschema',
            'tracking' => 'SELECT, DELETE',
        ];

        $data = new TrackedData(
            '20-03-2013 23:33:58',
            '20-03-2013 23:39:58',
            [
                ['date' => '20-03-2013 23:33:58', 'username' => 'user1', 'statement' => "\nstat1"],
                ['date' => '20-03-2013 23:39:58', 'username' => 'user2', 'statement' => ''],
            ],
            [],
            'SELECT, DELETE',
            'dataschema',
        );

        yield [$fetchArrayReturn, $data];

        $fetchArrayReturn = [
            'schema_sql' => "# log 20-03-2012 23:33:58 user1\n" .
            "# log 20-03-2012 23:39:58 user2\n",
            'data_sql' => "# log 20-03-2013 23:33:58 user3\n" .
            "# log 20-03-2013 23:39:58 user4\n",
            'schema_snapshot' => 'dataschema',
            'tracking' => 'SELECT, DELETE',
        ];

        $data = new TrackedData(
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

        yield [$fetchArrayReturn, $data];
    }
}
