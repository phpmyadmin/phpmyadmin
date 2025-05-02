<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Table\FindReplaceController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FindReplaceController::class)]
final class FindReplaceControllerTest extends AbstractTestCase
{
    public function testReplace(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dbiDummy->addResult('SELECT @@character_set_connection', [['utf8mb4']]);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $dbiDummy->addResult('UPDATE `test_table` SET `id` = REPLACE(`id`, \'Field\', \'Column\') WHERE `id` LIKE \'%Field%\' COLLATE utf8mb4_bin', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $responseRenderer = new ResponseRenderer();
        $controller = new FindReplaceController(
            $responseRenderer,
            new Template(),
            $dbi,
            new DbTableExists($dbi),
            new Config(),
        );

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withParsedBody([
                'db' => 'test_db',
                'table' => 'test_table',
                'replace' => '1',
                'replaceWith' => 'Column',
                'columnIndex' => '0',
                'findString' => 'Field',
            ]);

        $controller($request);

        self::assertStringContainsString(
            '<pre>' . "\n"
            . 'UPDATE `test_table` SET `id` = REPLACE(`id`, \'Field\', \'Column\')'
            . ' WHERE `id` LIKE \'%Field%\' COLLATE utf8mb4_bin'
            . "\n" . '</pre>',
            $responseRenderer->getHTMLResult(),
        );
        self::assertSame([], $responseRenderer->getJSONResult());
    }

    public function testReplaceWithRegex(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dbiDummy->addResult('SELECT @@character_set_connection', [['utf8mb4']]);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $dbiDummy->addResult('SELECT `id`, 1, COUNT(*) FROM `test_db`.`test_table` WHERE `id` RLIKE \'Field\' COLLATE utf8mb4_bin GROUP BY `id` ORDER BY `id` ASC', []);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $dbiDummy->addResult('UPDATE `test_table` SET `id` = `id` WHERE `id` RLIKE \'Field\' COLLATE utf8mb4_bin', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $responseRenderer = new ResponseRenderer();
        $controller = new FindReplaceController(
            $responseRenderer,
            new Template(),
            $dbi,
            new DbTableExists($dbi),
            new Config(),
        );

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withParsedBody([
                'db' => 'test_db',
                'table' => 'test_table',
                'replace' => '1',
                'useRegex' => 'On',
                'replaceWith' => 'Column',
                'columnIndex' => '0',
                'findString' => 'Field',
            ]);

        $controller($request);

        self::assertStringContainsString(
            '<pre>' . "\n"
            . 'UPDATE `test_table` SET `id` = `id` WHERE `id` RLIKE \'Field\' COLLATE utf8mb4_bin'
            . "\n" . '</pre>',
            $responseRenderer->getHTMLResult(),
        );
        self::assertSame([], $responseRenderer->getJSONResult());
    }
}
