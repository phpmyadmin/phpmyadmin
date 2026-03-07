<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\ImportController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Util;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ImportController::class)]
final class ImportControllerTest extends AbstractTestCase
{
    public function testController(): void
    {
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Current::$database = 'test_db';

        Util::$uploadMaxFilesize = '2M';
        Util::$postMaxSize = '8M';

        $config = new Config();
        $config->set('GZipDump', false);
        $config->set('BZipDump', false);
        $config->set('ZipDump', false);

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['route' => '/database/import', 'db' => 'test_db']);

        $response = ($this->getImportController($dbiDummy, $config))($request);

        Util::$uploadMaxFilesize = null;
        Util::$postMaxSize = null;

        $dbiDummy->assertAllSelectsConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Import-testController.html',
            (string) $response->getBody(),
        );
    }

    private function getImportController(DbiDummy $dbiDummy, Config $config): ImportController
    {
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $relation = new Relation($dbi, $config);
        $userPreferences = new UserPreferences($dbi, $relation, new Template($config), $config, new Clock());
        $responseRenderer = new ResponseRenderer();

        return new ImportController(
            $responseRenderer,
            $dbi,
            new PageSettings($userPreferences, $responseRenderer),
            new DbTableExists($dbi),
            $config,
        );
    }
}
