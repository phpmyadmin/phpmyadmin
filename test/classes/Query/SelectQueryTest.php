<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Query;

use PhpMyAdmin\Query\Builder;
use PHPUnit\Framework\TestCase;

/**
 * This class is for testing PhpMyAdmin\Query\SelectQuery class
 */
class SelectQueryTest extends TestCase
{
    public function testSelect(): void
    {
        $query = Builder::select(['a', 'b'])->database('db')->table('mytable')->toSql();
        $this->assertSame('SELECT `a`,`b` FROM `db`.`mytable`', $query);
    }

    public function testSelectNoDb(): void
    {
        $query = Builder::select(['a', 'b'])->table('mytable')->toSql();
        $this->assertSame('SELECT `a`,`b` FROM `mytable`', $query);
    }

    public function testSelectNoDbNoTable(): void
    {
        $query = Builder::select(['a', 'b'])->toSql();
        $this->assertSame('SELECT `a`,`b` FROM dual', $query);
    }
}
