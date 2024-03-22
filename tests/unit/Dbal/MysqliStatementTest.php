<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use mysqli_stmt;
use PhpMyAdmin\Dbal\MysqliResult;
use PhpMyAdmin\Dbal\MysqliStatement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MysqliStatement::class)]
#[CoversClass(MysqliResult::class)]
class MysqliStatementTest extends TestCase
{
    public function testGetResult(): void
    {
        $mysqliStmt = self::createMock(mysqli_stmt::class);
        $mysqliStmt->expects(self::once())->method('get_result')->willReturn(false);
        $statement = new MysqliStatement($mysqliStmt);
        $result = $statement->getResult();
        self::assertInstanceOf(MysqliResult::class, $result);
    }
}
