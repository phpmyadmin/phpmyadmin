<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function json_encode;

#[CoversClass(LintController::class)]
final class LintControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testWithoutParams(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('isAjax')->willReturn(true);
        $request->method('getParsedBodyParam')->willReturnMap([['sql_query', '', ''], ['options', null, null]]);

        $response = $this->getLintController()($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['application/json; charset=UTF-8'], $response->getHeader('Content-Type'));
        $output = (string) $response->getBody();
        self::assertJson($output);
        self::assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testWithoutSqlErrors(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('isAjax')->willReturn(true);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['sql_query', '', 'SELECT * FROM `actor` WHERE `actor_id` = 1;'],
            ['options', null, null],
        ]);

        $response = $this->getLintController()($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['application/json; charset=UTF-8'], $response->getHeader('Content-Type'));
        $output = (string) $response->getBody();
        self::assertJson($output);
        self::assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testWithSqlErrors(): void
    {
        $expectedJson = json_encode([
            [
                'message' => 'An alias was previously found. (near <code>`actor_id`</code>)',
                'fromLine' => 0,
                'fromColumn' => 29,
                'toLine' => 0,
                'toColumn' => 39,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>`actor_id`</code>)',
                'fromLine' => 0,
                'fromColumn' => 29,
                'toLine' => 0,
                'toColumn' => 39,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>=</code>)',
                'fromLine' => 0,
                'fromColumn' => 40,
                'toLine' => 0,
                'toColumn' => 41,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>1</code>)',
                'fromLine' => 0,
                'fromColumn' => 42,
                'toLine' => 0,
                'toColumn' => 43,
                'severity' => 'error',
            ],
        ]);
        self::assertNotFalse($expectedJson);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'ajax_request' => '1',
                'sql_query' => 'SELECT * FROM `actor` WHEREE `actor_id` = 1',
                'options' => null,
            ]);
        $response = $this->getLintController()($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['application/json; charset=UTF-8'], $response->getHeader('Content-Type'));
        $output = (string) $response->getBody();
        self::assertJson($output);
        self::assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    private function getLintController(): LintController
    {
        return new LintController(ResponseFactory::create());
    }
}
