<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\Structure\MoveColumnsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

use function preg_replace;

#[CoversClass(MoveColumnsController::class)]
class MoveColumnsControllerTest extends AbstractTestCase
{
    /**
     * @param array<int,string> $columnNames
     * @psalm-param list<string> $columnNames
     */
    #[DataProvider('providerForTestGenerateAlterTableSql')]
    public function testGenerateAlterTableSql(string $createStatement, array $columnNames, string|null $expected): void
    {
        $class = new ReflectionClass(MoveColumnsController::class);
        $method = $class->getMethod('generateAlterTableSql');

        Current::$database = 'test-db';
        Current::$table = 'test';
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $controller = new MoveColumnsController(
            new ResponseStub(),
            new Template(),
            $dbi,
        );
        /** @var string|null $alterStatement */
        $alterStatement = $method->invoke($controller, $createStatement, $columnNames);

        $expected = $expected === null ? null : preg_replace('/\r?\n/', "\n", $expected);
        $alterStatement = $alterStatement === null ? null : preg_replace('/\r?\n/', "\n", $alterStatement);
        self::assertSame($expected, $alterStatement);
    }

    /**
     * Data provider for testGenerateAlterTableSql
     *
     * @return array<array<string[]|string|null>>
     * @psalm-return list<array{string,list<string>,string}>
     */
    public static function providerForTestGenerateAlterTableSql(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            // MariaDB / column CHECK constraint
            [
                <<<'SQL'
                    CREATE TABLE `test` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(45) DEFAULT NULL,
                      `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json`)),
                      PRIMARY KEY (`id`)
                    )
                    SQL,
                ['id', 'data', 'name'],
                <<<'SQL'
                    ALTER TABLE `test`
                      CHANGE `data` `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json`)) AFTER `id`
                    SQL,
            ],
            // MariaDB / text column with uuid() default
            [
                <<<'SQL'
                    CREATE TABLE `test` (
                      `Id` int(11) NOT NULL,
                      `First` text NOT NULL DEFAULT uuid(),
                      `Second` text NOT NULL DEFAULT uuid()
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                    SQL,
                ['Id', 'Second', 'First'],
                <<<'SQL'
                    ALTER TABLE `test`
                      CHANGE `Second` `Second` text NOT NULL DEFAULT uuid() AFTER `Id`
                    SQL,
            ],
            // MySQL 8.0.13 text column with uuid() default
            [
                <<<'SQL'
                    CREATE TABLE `test` (
                      `Id` int(11) NOT NULL,
                      `First` text COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
                      `Second` text COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid())
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                    SQL,
                ['Id', 'Second', 'First'],
                <<<'SQL'
                    ALTER TABLE `test`
                      CHANGE `Second` `Second` text COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()) AFTER `Id`
                    SQL,
            ],
            // enum with default
            [
                <<<'SQL'
                    CREATE TABLE `test` (
                      `id` int(11) NOT NULL,
                      `enum` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci
                    SQL,
                ['enum', 'id'],
                <<<'SQL'
                    ALTER TABLE `test`
                      CHANGE `enum` `enum` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no' FIRST
                    SQL,
            ],
        ];
        // phpcs:enable
    }
}
