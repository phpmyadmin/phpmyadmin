<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Database\DesignerController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(DesignerController::class)]
final class DesignerControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::TABLE_COORDS => 'table_coords',
            RelationParameters::PDF_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    public function testEditPageDialog(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT `page_nr`, `page_descr` FROM `pmadb`.`pdf_pages` WHERE db_name = 'test_db' ORDER BY `page_descr`",
            [['1', 'Page one'], ['2', 'Page two']],
            ['page_nr', 'page_descr'],
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'edit']);

        $response = ($this->getDesignerController($dbiDummy))($request);

        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Designer-testEditPageDialog.html',
            (string) $response->getBody(),
        );
    }

    public function testDeletePageDialog(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT `page_nr`, `page_descr` FROM `pmadb`.`pdf_pages` WHERE db_name = 'test_db' ORDER BY `page_descr`",
            [['1', 'Page one'], ['2', 'Page two']],
            ['page_nr', 'page_descr'],
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'delete']);

        $response = ($this->getDesignerController($dbiDummy))($request);

        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Designer-testDeletePageDialog.html',
            (string) $response->getBody(),
        );
    }

    public function testSaveAsPageDialog(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT `page_nr`, `page_descr` FROM `pmadb`.`pdf_pages` WHERE db_name = 'test_db' ORDER BY `page_descr`",
            [['1', 'Page one'], ['2', 'Page two']],
            ['page_nr', 'page_descr'],
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'save_as']);

        $response = ($this->getDesignerController($dbiDummy))($request);

        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Designer-testSaveAsPageDialog.html',
            (string) $response->getBody(),
        );
    }

    public function testExportDialog(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'export']);

        $response = ($this->getDesignerController())($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Designer-testExportDialog.html',
            (string) $response->getBody(),
        );
    }

    public function testAddTableDialog(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'add_table']);

        $response = ($this->getDesignerController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringMatchesFormatFile(
            __DIR__ . '/Fixtures/Designer-testAddTableDialog.html',
            (string) $response->getBody(),
        );
    }

    public function testMainPage(): void
    {
        Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult(
            "SELECT `page_nr` FROM `pmadb`.`pdf_pages` WHERE `db_name` = 'test_db' AND `page_descr` = 'test_db'",
            [['2']],
            ['page_nr'],
        );
        $dbiDummy->addResult(
            'SELECT `page_descr` FROM `pmadb`.`pdf_pages` WHERE `page_nr` = 2',
            [['test_db']],
            ['page_descr'],
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            "SELECT CONCAT_WS('.', `db_name`, `table_name`) AS `name`, `db_name` as `dbName`, `table_name` as `tableName`, `x` AS `X`, `y` AS `Y`, 1 AS `V`, 1 AS `H` FROM `pmadb`.`table_coords` WHERE pdf_page_number = 2",
            [['test_db.test_table', 'test_db', 'test_table', '341', '109', '1', '1']],
            ['name', 'dbName', 'tableName', 'X', 'Y', 'V', 'H'],
        );
        // phpcs:enable
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`', [['test_table']], ['Tables_in_test_db']);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $response = ($this->getDesignerController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Designer-testMainPage.html',
            (string) $response->getBody(),
        );
    }

    private function getDesignerController(DbiDummy|null $dbiDummy = null): DesignerController
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $relation = new Relation($dbi, $config);
        $template = new Template($config);

        return new DesignerController(
            new ResponseRenderer(),
            $template,
            new Designer($dbi, $relation, $template, $config),
            new Common($dbi, $relation, $config),
            new DbTableExists($dbi),
        );
    }
}
