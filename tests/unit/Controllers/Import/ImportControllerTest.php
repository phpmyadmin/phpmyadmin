<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Import;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Import\ImportController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Message;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject; // Make sure this is imported

#[CoversClass(ImportController::class)]
class ImportControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    // Declare a mock property for Sql
    private Sql&MockObject $sqlMock; // Correct declaration

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $this->sqlMock = $this->createMock(Sql::class);
    }

    // Add a new test method specifically for the empty HTML output scenario
    public function testQueryExecutionFailedWithTrulyEmptyHtmlOutput(): void
    {
        $this->setLanguage();

        $config = Config::getInstance();
        $config->selectedServer['user'] = 'user';
        $config->settings['MaxCharactersInDisplayedSQL'] = 10000;

        Current::$sqlQuery = 'SELECT * FROM table1;';
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $this->dummyDbi->addSelectDb('test_db');

        // Configure the mock Sql object to return an EMPTY STRING for executeQueryAndGetQueryResponse
        $this->sqlMock->expects($this->once())
                      ->method('executeQueryAndGetQueryResponse')
                      ->willReturn('');

        // Configure the mock Sql object for hasNoRightsToDropDatabase
        $this->sqlMock->method('hasNoRightsToDropDatabase')->willReturn(false);

        ImportSettings::$goSql = true;

        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParamAsString')->willReturn('');
        $request->method('getParsedBodyParamAsStringOrNull')->willReturn(null);
        $request->method('hasBodyParam')->willReturn(false);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['format', null, 'sql'],
        ]);

        $responseRenderer = new ResponseRenderer();
        $relation = new Relation($this->dbi);
        $bookmarkRepository = new BookmarkRepository($this->dbi, $relation);

        $importController = new ImportController(
            $responseRenderer,
            new Import(),
            $this->sqlMock,
            $this->dbi,
            $bookmarkRepository,
            $config,
        );

        $importController($request);

        self::assertTrue(Import::$hasError, 'Import::$hasError should be true when query returns empty string.');
        self::assertInstanceOf(Message::class, Current::$message);
        self::assertStringContainsString(
            'Query execution failed with empty response',
            Current::$message->getMessage(),
            'The error message should indicate an empty response.'
        );

        self::assertFalse($responseRenderer->hasSuccessState(), 'Expected the request to fail.');
        self::assertStringContainsString(
            'Query execution failed with empty response',
            $responseRenderer->getJSONResult()['message']
        );
        // Assert that all expected DbiDummy calls were consumed
        $this->dummyDbi->assertAllSelectsConsumed();
        $this->dummyDbi->assertAllQueriesConsumed();
    }

    public function testIndexParametrized(): void
    {
        $this->setLanguage();

        $config = Config::getInstance();
        $config->selectedServer['user'] = 'user';

        // Some params were not added as they are not required for this test
        Sql::$showAsPhp = null;
        Current::$database = 'pma_test';
        Current::$table = 'table1';
        Current::$sqlQuery = 'SELECT A.*' . "\n"
            . 'FROM table1 A' . "\n"
            . 'WHERE A.nomEtablissement = :nomEta AND foo = :1 AND `:a` IS NULL';

        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, Current::$database],
            ['table', null, Current::$table],
            ['parameters', null, [':nomEta' => 'Saint-Louis - Châteaulin', ':1' => '4']],
            ['sql_query', null, Current::$sqlQuery],
        ]);
        $request->method('hasBodyParam')->willReturnMap([
            ['parameterized', true],
            ['rollback_query', false],
            ['allow_interrupt', false],
            ['skip', false],
            ['show_as_php', false],
        ]);

        $this->dummyDbi->addResult(
            'SELECT A.* FROM table1 A WHERE A.nomEtablissement = \'Saint-Louis - Châteaulin\''
            . ' AND foo = 4 AND `:a` IS NULL LIMIT 0, 25',
            [],
            ['nomEtablissement', 'foo'],
        );

        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `pma_test`.`table1`',
            [],
        );

        $this->dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'pma_test\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'table1\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [],
        );

        $responseRenderer = new ResponseRenderer();
        $relation = new Relation($this->dbi);
        $bookmarkRepository = new BookmarkRepository($this->dbi, $relation);
        $template = new Template();
        $sql = new Sql(
            $this->dbi,
            $relation,
            self::createStub(RelationCleanup::class),
            self::createStub(Transformations::class),
            $template,
            $bookmarkRepository,
            $config,
        );

        $importController = new ImportController(
            $responseRenderer,
            new Import(),
            $sql,
            $this->dbi,
            $bookmarkRepository,
            $config,
        );

        $this->dummyDbi->addSelectDb('pma_test');
        $this->dummyDbi->addSelectDb('pma_test');
        $importController($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        self::assertTrue($responseRenderer->hasSuccessState(), 'expected the request not to fail');

        $output = $responseRenderer->getHTMLResult();

        self::assertStringContainsString('MySQL returned an empty result set (i.e. zero rows).', $output);

        self::assertStringContainsString(
            'SELECT A.*' . "\n" . 'FROM table1 A' . "\n"
                . 'WHERE A.nomEtablissement = \'Saint-Louis - Châteaulin\' AND foo = 4 AND `:a` IS NULL',
            $output,
        );

        $this->dummyDbi->assertAllQueriesConsumed();
    }
}
