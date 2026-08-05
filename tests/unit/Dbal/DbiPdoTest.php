<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PDO;
use PDOStatement;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DbiPdo;
use PhpMyAdmin\Dbal\PdoConnection;
use PhpMyAdmin\Dbal\PdoResult;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(DbiPdo::class)]
#[CoversClass(PdoConnection::class)]
class DbiPdoTest extends AbstractTestCase
{
    protected DbiPdo $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new DbiPdo();
    }

    private function createPdoMock(): MockObject&PDO
    {
        return self::createMock(PDO::class);
    }

    public function testSelectDb(): void
    {
        $pdo = $this->createPdoMock();
        $pdo->expects(self::once())
            ->method('exec')
            ->with(self::equalTo('USE `test`'))
            ->willReturn(0);

        self::assertTrue($this->object->selectDb('test', new Connection(new PdoConnection($pdo))));
    }

    public function testRealQuery(): void
    {
        $query = 'SELECT 1';
        $statement = self::createMock(PDOStatement::class);
        $statement->method('columnCount')->willReturn(0);

        $pdo = $this->createPdoMock();
        $pdo->expects(self::once())
            ->method('query')
            ->with(self::equalTo($query))
            ->willReturn($statement);

        $pdoConnection = new PdoConnection($pdo);

        self::assertInstanceOf(
            PdoResult::class,
            $this->object->realQuery($query, new Connection($pdoConnection)),
        );
        self::assertSame($statement, $pdoConnection->lastStatement);
    }

    public function testRealQueryFailure(): void
    {
        $pdo = $this->createPdoMock();
        $pdo->expects(self::once())
            ->method('query')
            ->willReturn(false);

        $pdoConnection = new PdoConnection($pdo);

        self::assertFalse($this->object->realQuery('SELECT 1', new Connection($pdoConnection)));
        self::assertNull($pdoConnection->lastStatement);
    }

    public function testRealMultiQuery(): void
    {
        $query = 'SELECT 1; SELECT 2';
        $statement = self::createMock(PDOStatement::class);

        $pdo = $this->createPdoMock();
        $pdo->expects(self::once())
            ->method('query')
            ->with(self::equalTo($query))
            ->willReturn($statement);

        self::assertTrue($this->object->realMultiQuery(new Connection(new PdoConnection($pdo)), $query));
    }

    public function testNextResult(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->expects(self::once())
            ->method('nextRowset')
            ->willReturn(true);

        $pdoConnection = new PdoConnection($this->createPdoMock());
        $pdoConnection->lastStatement = $statement;

        self::assertTrue($this->object->nextResult(new Connection($pdoConnection)));
    }

    public function testNextResultWithoutStatement(): void
    {
        $pdoConnection = new PdoConnection($this->createPdoMock());

        self::assertFalse($this->object->nextResult(new Connection($pdoConnection)));
    }

    public function testStoreResult(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->method('columnCount')->willReturn(1);
        $statement->method('getColumnMeta')->willReturn(['name' => 'id', 'native_type' => 'LONG', 'flags' => []]);
        $statement->method('fetchAll')->willReturn([['1']]);

        $pdoConnection = new PdoConnection($this->createPdoMock());
        $pdoConnection->lastStatement = $statement;

        self::assertInstanceOf(PdoResult::class, $this->object->storeResult(new Connection($pdoConnection)));
    }

    public function testStoreResultWithoutResultSet(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->method('columnCount')->willReturn(0);

        $pdoConnection = new PdoConnection($this->createPdoMock());
        $pdoConnection->lastStatement = $statement;

        self::assertFalse($this->object->storeResult(new Connection($pdoConnection)));
    }

    public function testEscapeString(): void
    {
        $pdo = $this->createPdoMock();
        $pdo->expects(self::once())
            ->method('quote')
            ->with(self::equalTo("te'st"))
            ->willReturn("'te\\'st'");

        self::assertSame("te\\'st", $this->object->escapeString(new Connection(new PdoConnection($pdo)), "te'st"));
    }

    public function testAffectedRows(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(30);

        $pdoConnection = new PdoConnection($this->createPdoMock());
        $pdoConnection->lastStatement = $statement;

        self::assertSame(30, $this->object->affectedRows(new Connection($pdoConnection)));
    }

    public function testAffectedRowsWithoutStatement(): void
    {
        $pdoConnection = new PdoConnection($this->createPdoMock());

        self::assertSame(-1, $this->object->affectedRows(new Connection($pdoConnection)));
    }

    public function testGetWarningCount(): void
    {
        $statement = self::createMock(PDOStatement::class);
        $statement->method('fetchColumn')->willReturn('30');

        $pdo = $this->createPdoMock();
        $pdo->expects(self::once())
            ->method('query')
            ->with(self::equalTo('SHOW COUNT(*) WARNINGS'))
            ->willReturn($statement);

        self::assertSame(30, $this->object->getWarningCount(new Connection(new PdoConnection($pdo))));
    }

    public function testGetError(): void
    {
        $pdo = $this->createPdoMock();
        $pdo->method('errorInfo')->willReturn(['42S02', 1146, "Table 'test.nonexistent' doesn't exist"]);

        $error = $this->object->getError(new Connection(new PdoConnection($pdo)));

        self::assertSame('#1146 - Table &#039;test.nonexistent&#039; doesn&#039;t exist', $error);
    }

    public function testGetErrorWithoutError(): void
    {
        $pdo = $this->createPdoMock();
        $pdo->method('errorInfo')->willReturn(['00000', null, null]);

        self::assertSame('', $this->object->getError(new Connection(new PdoConnection($pdo))));
    }
}
