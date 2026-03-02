<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\Database\SqlFormatController;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SqlFormatController::class)]
final class SqlFormatControllerTest extends AbstractTestCase
{
    public function testSqlFormatMultipleLines(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql' => "\nselect\n*\nfrom\ntbl\nwhere\n1\n", 'formatSingleLine' => 'false']);

        $response = ($this->getSqlFormatController())($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(
            '{"sql":"SELECT\n    *\nFROM\n    tbl\nWHERE\n    1","success":true}',
            (string) $response->getBody(),
        );
    }

    public function testSqlFormatSingleLine(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['sql' => "\nselect\n*\nfrom\ntbl\nwhere\n1\n", 'formatSingleLine' => 'true']);

        $response = ($this->getSqlFormatController())($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(
            '{"sql":"SELECT * FROM tbl WHERE 1","success":true}',
            (string) $response->getBody(),
        );
    }

    private function getSqlFormatController(): SqlFormatController
    {
        $responseRenderer = new ResponseRenderer();
        $responseRenderer->setAjax(true);

        return new SqlFormatController($responseRenderer);
    }
}
