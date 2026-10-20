<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\ExportController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExportController::class)]
final class ExportControllerTest extends AbstractTestCase
{
    public function testController(): void
    {
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Current::$database = 'test_db';
        Export::$singleTable = false;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['route' => '/database/export', 'db' => 'test_db']);

        $response = ($this->getExportController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Export-testController.html',
            (string) $response->getBody(),
        );
    }

    private function getExportController(DbiDummy $dbiDummy): ExportController
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $responseRenderer = new ResponseRenderer();
        $relation = new Relation($dbi, $config);
        $userPreferences = new UserPreferences($dbi, $relation, new Template($config), $config, new Clock());
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            $userPreferences,
            new LanguageManager($config),
            new ThemeManager(),
        );

        $pluginFactory = $this->createPluginFactory($dbi, $config);

        return new ExportController(
            $responseRenderer,
            new Export($dbi, new OutputHandler(), $pluginFactory),
            new Options($relation, new TemplateModel($dbi), $userPreferencesHandler),
            new PageSettings($userPreferences, $responseRenderer),
            new DbTableExists($dbi),
            $pluginFactory,
        );
    }
}
