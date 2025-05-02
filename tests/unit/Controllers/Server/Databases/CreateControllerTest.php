<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Databases;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Databases\CreateController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;
use function sprintf;

#[CoversClass(CreateController::class)]
final class CreateControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testCreateDatabase(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        Current::$database = 'pma_test';
        Current::$table = '';

        $response = new ResponseRenderer();

        $controller = new CreateController($response, $this->dbi, $config);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'new_db' => 'test_db_error',
                'db_collation' => null,
            ]);

        $controller($request);
        $actual = $response->getJSONResult();

        self::assertArrayHasKey('message', $actual);
        self::assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual['message']);

        $response = new ResponseRenderer();

        $controller = new CreateController($response, $this->dbi, $config);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
        ->withParsedBody([
            'ajax_request' => 'true',
            'new_db' => 'test_db',
            'db_collation' => 'utf8_general_ci',
        ]);

        $controller($request);
        $actual = $response->getJSONResult();

        self::assertArrayHasKey('message', $actual);
        self::assertStringContainsString('<div class="alert alert-success" role="alert">', $actual['message']);
        self::assertStringContainsString(
            sprintf(__('Database %1$s has been created.'), 'test_db'),
            $actual['message'],
        );
    }
}
