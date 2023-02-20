<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli_stmt;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Dbal\MysqliStatement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Dbal\MysqliStatement
 * @covers \PhpMyAdmin\Dbal\MysqliResult
 */
class MysqliStatementTest extends TestCase
{
    public function testGetResult(): void
    {
        $mysqliStmt = $this->createMock(mysqli_stmt::class);
        $mysqliStmt->expects($this->once())->method('get_result')->willReturn(false);
        $statement = new MysqliStatement($mysqliStmt);
        $result = $statement->getResult();
        $this->assertInstanceOf(MysqliResult::class, $result);
    }
}
