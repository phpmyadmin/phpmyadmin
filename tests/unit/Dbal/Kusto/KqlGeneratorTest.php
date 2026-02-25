<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal\Kusto;

use PhpMyAdmin\Dbal\Kusto\KqlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KqlGenerator::class)]
class KqlGeneratorTest extends TestCase
{
    public function testShowDatabases(): void
    {
        self::assertSame('.show databases', KqlGenerator::showDatabases());
    }

    public function testShowTables(): void
    {
        self::assertSame('.show tables', KqlGenerator::showTables());
        self::assertSame('.show database mydb tables', KqlGenerator::showTables('mydb'));
    }

    public function testShowTableSchema(): void
    {
        self::assertSame(
            '.show table MyTable schema as json',
            KqlGenerator::showTableSchema('MyTable'),
        );
    }

    public function testGetColumns(): void
    {
        self::assertSame(
            '.show table MyTable schema as cslschema',
            KqlGenerator::getColumns('MyTable'),
        );
    }

    public function testCountRows(): void
    {
        self::assertSame('MyTable | count', KqlGenerator::countRows('MyTable'));
    }

    public function testSelectAll(): void
    {
        self::assertSame('MyTable | take 100', KqlGenerator::selectAll('MyTable', 100));
    }

    public function testSelectAllWithOffset(): void
    {
        $result = KqlGenerator::selectAll('MyTable', 50, 10);
        self::assertStringContainsString('serialize', $result);
        self::assertStringContainsString('where _rowNumber > 10', $result);
        self::assertStringContainsString('take 50', $result);
    }

    public function testDropTable(): void
    {
        self::assertSame('.drop table MyTable ifexists', KqlGenerator::dropTable('MyTable'));
    }

    public function testCreateTable(): void
    {
        $result = KqlGenerator::createTable('MyTable', [
            'Id' => 'long',
            'Name' => 'string',
            'Created' => 'datetime',
        ]);

        self::assertSame(
            '.create table MyTable (Id:long, Name:string, Created:datetime)',
            $result,
        );
    }

    public function testQuoteIdentifier(): void
    {
        // Simple identifiers don't need quoting
        self::assertSame('MyTable', KqlGenerator::quoteIdentifier('MyTable'));
        self::assertSame('my_table_1', KqlGenerator::quoteIdentifier('my_table_1'));

        // Identifiers with special chars get quoted
        self::assertSame("[' my table']", KqlGenerator::quoteIdentifier(' my table'));
        self::assertSame("['my-table']", KqlGenerator::quoteIdentifier('my-table'));
    }

    public function testShowFunctions(): void
    {
        self::assertSame('.show functions', KqlGenerator::showFunctions());
    }

    public function testShowVersion(): void
    {
        self::assertSame('.show version', KqlGenerator::showVersion());
    }

    public function testShowQueries(): void
    {
        self::assertSame('.show queries', KqlGenerator::showQueries());
    }

    public function testMysqlTypeToKusto(): void
    {
        self::assertSame('int', KqlGenerator::mysqlTypeToKusto('int'));
        self::assertSame('int', KqlGenerator::mysqlTypeToKusto('INTEGER'));
        self::assertSame('long', KqlGenerator::mysqlTypeToKusto('bigint'));
        self::assertSame('real', KqlGenerator::mysqlTypeToKusto('float'));
        self::assertSame('real', KqlGenerator::mysqlTypeToKusto('double'));
        self::assertSame('bool', KqlGenerator::mysqlTypeToKusto('boolean'));
        self::assertSame('datetime', KqlGenerator::mysqlTypeToKusto('datetime'));
        self::assertSame('datetime', KqlGenerator::mysqlTypeToKusto('timestamp'));
        self::assertSame('string', KqlGenerator::mysqlTypeToKusto('varchar(255)'));
        self::assertSame('string', KqlGenerator::mysqlTypeToKusto('text'));
        self::assertSame('dynamic', KqlGenerator::mysqlTypeToKusto('json'));
        self::assertSame('dynamic', KqlGenerator::mysqlTypeToKusto('blob'));
    }

    public function testRenameTable(): void
    {
        self::assertSame(
            '.rename table OldName to NewName',
            KqlGenerator::renameTable('OldName', 'NewName'),
        );
    }

    public function testAddColumn(): void
    {
        self::assertSame(
            '.alter-merge table MyTable (NewCol:string)',
            KqlGenerator::addColumn('MyTable', 'NewCol', 'string'),
        );
    }

    public function testDropColumn(): void
    {
        self::assertSame(
            '.alter table MyTable drop column OldCol',
            KqlGenerator::dropColumn('MyTable', 'OldCol'),
        );
    }

    public function testSearchTable(): void
    {
        $result = KqlGenerator::searchTable('MyTable', 'hello');
        self::assertStringContainsString("has 'hello'", $result);
        self::assertStringContainsString('take 1000', $result);
    }
}
