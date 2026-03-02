<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Database\SqlAutoCompleteController;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SqlAutoCompleteController::class)]
final class SqlAutoCompleteControllerTest extends AbstractTestCase
{
    public function testAutoCompleteDisabled(): void
    {
        $config = new Config();
        $config->set('EnableAutocompleteForTablesAndColumns', false);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withQueryParams(['route' => '/database/sql/autocomplete'])
            ->withParsedBody(['db' => 'test_db']);

        $response = ($this->getSqlAutoCompleteController(config: $config))($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('{"tables":[],"success":true}', (string) $response->getBody());
    }

    public function testAutoCompleteEnabled(): void
    {
        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_KEY FROM information_schema.columns WHERE table_schema = 'test_db'",
            [['test_table', 'id', 'int(11)', 'PRI'], ['test_table', 'name', 'varchar(20)', 'UNI'], ['test_table', 'datetimefield', 'datetime', '']],
            ['TABLE_NAME', 'COLUMN_NAME', 'COLUMN_TYPE', 'COLUMN_KEY'],
        );
        // phpcs:enable

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withQueryParams(['route' => '/database/sql/autocomplete'])
            ->withParsedBody(['db' => 'test_db']);

        $response = ($this->getSqlAutoCompleteController($dbiDummy))($request);

        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        // phpcs:disable Generic.Files.LineLength.TooLong
        self::assertSame(
            '{"tables":{"test_table":[{"field":"id","columnHint":"int(11) | Primary"},{"field":"name","columnHint":"varchar(20) | Unique"},{"field":"datetimefield","columnHint":"datetime"}]},"success":true}',
            (string) $response->getBody(),
        );
        // phpcs:enable
    }

    private function getSqlAutoCompleteController(
        DbiDummy|null $dbiDummy = null,
        Config|null $config = null,
    ): SqlAutoCompleteController {
        $config ??= new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $responseRenderer = new ResponseRenderer();
        $responseRenderer->setAjax(true);

        return new SqlAutoCompleteController($responseRenderer, $dbi, $config);
    }
}
