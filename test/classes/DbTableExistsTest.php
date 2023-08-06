<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DbTableExists::class)]
final class DbTableExistsTest extends AbstractTestCase
{
    public function testHasDatabase(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbTableExists = new DbTableExists($dbi);
        $this->assertTrue($dbTableExists->hasDatabase(DatabaseName::from('test_db')));
        // cached result
        $this->assertTrue($dbTableExists->hasDatabase(DatabaseName::from('test_db')));
        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testHasDatabaseWithNoDatabase(): void
    {
        $db = DatabaseName::from('test_db');
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('selectDb')->with($db)->willReturn(false);
        $dbTableExists = new DbTableExists($dbi);
        $this->assertFalse($dbTableExists->hasDatabase($db));
    }

    public function testHasTable(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']], ['Tables_in_test_db (test_table)']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbi->getCache()->clearTableCache();
        $dbTableExists = new DbTableExists($dbi);
        $this->assertTrue($dbTableExists->hasTable(DatabaseName::from('test_db'), TableName::from('test_table')));
        $dbi->getCache()->clearTableCache();
        // cached result
        $this->assertTrue($dbTableExists->hasTable(DatabaseName::from('test_db'), TableName::from('test_table')));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testHasTableWithTempTable(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SHOW TABLES LIKE \'test_table\';', [], ['Tables_in_test_db (test_table)']);
        $dbiDummy->addResult('SELECT 1 FROM `test_table` LIMIT 1;', [['1']], ['1']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbi->getCache()->clearTableCache();
        $dbTableExists = new DbTableExists($dbi);
        $this->assertTrue($dbTableExists->hasTable(DatabaseName::from('test_db'), TableName::from('test_table')));
        $dbi->getCache()->clearTableCache();
        // cached result
        $this->assertTrue($dbTableExists->hasTable(DatabaseName::from('test_db'), TableName::from('test_table')));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testHasTableWithDbiCache(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbi->getCache()->cacheTableContent(['test_db', 'test_table'], ['test_table']);
        $dbTableExists = new DbTableExists($dbi);
        $this->assertTrue($dbTableExists->hasTable(DatabaseName::from('test_db'), TableName::from('test_table')));
        $dbi->getCache()->clearTableCache();
        // cached result
        $this->assertTrue($dbTableExists->hasTable(DatabaseName::from('test_db'), TableName::from('test_table')));
    }

    public function testHasTableWithNoTable(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SHOW TABLES LIKE \'test_table\';', false);
        $dbiDummy->addResult('SELECT 1 FROM `test_table` LIMIT 1;', false);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbi->getCache()->clearTableCache();
        $dbTableExists = new DbTableExists($dbi);
        $this->assertFalse($dbTableExists->hasTable(DatabaseName::from('test_db'), TableName::from('test_table')));
        $dbiDummy->assertAllQueriesConsumed();
    }
}
