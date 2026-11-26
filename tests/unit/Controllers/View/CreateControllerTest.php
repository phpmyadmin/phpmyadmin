<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\View;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\View\CreateController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateController::class)]
final class CreateControllerTest extends AbstractTestCase
{
    public function testErrorWithEmptyDbParam(): void
    {
        $config = new Config();
        $config->set('PmaAbsoluteUri', 'https://example.com/');
        Config::$instance = $config;
        $dbi = $this->createDatabaseInterface(config: $config);
        $responseRenderer = new ResponseRenderer($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => '', 'table' => '', 'sql_query' => 'SHOW PROCESSLIST;']);

        $response = (new CreateController($responseRenderer, $dbi, new DbTableExists($dbi)))($request);

        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame(
            '/index.php?route=/&reload=1&message=No+database+selected.&lang=en',
            $response->getHeaderLine('Location'),
        );
        self::assertSame('', (string) $response->getBody());
    }

    public function testErrorWithEmptyDbParamAjax(): void
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface(config: $config);
        $responseRenderer = new ResponseRenderer();
        $responseRenderer->setAjax(true);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withQueryParams(['db' => '', 'table' => '', 'sql_query' => 'SHOW PROCESSLIST;']);

        $response = (new CreateController($responseRenderer, $dbi, new DbTableExists($dbi)))($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(
            '{"success":false,"error":"<div class=\"alert alert-danger\" role=\"alert\">\n'
            . '  <img src=\"themes\/dot.gif\" title=\"\" alt=\"\" class=\"icon ic_s_error\"> No database selected.\n'
            . '<\/div>\n"}',
            (string) $response->getBody(),
        );
        self::assertFalse($responseRenderer->hasSuccessState());
    }
}
