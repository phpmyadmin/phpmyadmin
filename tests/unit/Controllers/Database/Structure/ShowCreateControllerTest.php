<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Database\Structure\ShowCreateController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ShowCreateController::class)]
final class ShowCreateControllerTest extends AbstractTestCase
{
    public function testEscapesNamesAndQueriesOnce(): void
    {
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;
        Config::$instance = $config;
        Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'test_db' AND TABLE_NAME = 'a&b c'",
            [],
        );
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `test_db`.`a&b c`',
            [['a&b  c', "CREATE TABLE `a&b  c` (\n  `id` int\n)"]],
        );
        $dbiDummy->addResult(
            "SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'test_db' AND TABLE_NAME = 'v<1>'",
            [['1']],
        );
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `test_db`.`v<1>`',
            [['v<1>', 'CREATE VIEW `v<1>` AS SELECT 1 < 2']],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $response = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['selected_tbl' => ['a&b  c', 'v<1>']]);

        (new ShowCreateController($response, new Template($config), $dbi))($request);

        $message = $response->getJSONResult()['message'];
        self::assertIsString($message);
        self::assertStringContainsString('<td><strong>a&amp;b  c</strong></td>', $message);
        self::assertStringContainsString(
            '<code class="sql" dir="ltr">CREATE TABLE `a&amp;b  c` (' . "\n" . '  `id` int' . "\n" . ')</code>',
            $message,
        );
        self::assertStringContainsString('<td><strong>v&lt;1&gt;</strong></td>', $message);
        self::assertStringContainsString(
            '<code class="sql" dir="ltr">CREATE VIEW `v&lt;1&gt;` AS SELECT 1 &lt; 2</code>',
            $message,
        );

        $dbiDummy->assertAllQueriesConsumed();
    }
}
