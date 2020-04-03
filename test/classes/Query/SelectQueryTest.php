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
        $query = Builder::select(['a', 'b'])->database('db')->table('mytable');
        $this->assertSame('SELECT `a`,`b` FROM `db`.`mytable`', $query->toSql());
        $this->assertSame([], $query->getPlaceHolderValues());
    }

    public function testSelectNoDb(): void
    {
        $query = Builder::select(['a', 'b'])->table('mytable');
        $this->assertSame('SELECT `a`,`b` FROM `mytable`', $query->toSql());
        $this->assertSame([], $query->getPlaceHolderValues());
    }

    public function testSelectNoDbNoTable(): void
    {
        $query = Builder::select(['a', 'b']);
        $this->assertSame('SELECT `a`,`b` FROM dual', $query->toSql());
        $this->assertSame([], $query->getPlaceHolderValues());
    }

    public function testSelectNoDbNoTableWhere(): void
    {
        $query = Builder::select(['a', 'b'])->where('a', '=', 1);
        $this->assertSame('SELECT `a`,`b` FROM dual WHERE a = ?', $query->toSql());
        $this->assertSame([1], $query->getPlaceHolderValues());
    }

    public function testSelectNoDbNoTableWhereSimple(): void
    {
        $query = Builder::select(['a', 'b'])->whereSimple(1);
        $this->assertSame('SELECT `a`,`b` FROM dual WHERE ?', $query->toSql());
        $this->assertSame([1], $query->getPlaceHolderValues());
    }

    public function testSelectNoDbNoTableCountWhereSimple(): void
    {
        $query = Builder::select(['a', 'b'])->count()->whereSimple(1);
        $this->assertSame('SELECT COUNT(*),`a`,`b` FROM dual WHERE ?', $query->toSql());
        $this->assertSame([1], $query->getPlaceHolderValues());
    }

    public function testSelectNoDbNoTableCountAsWhereSimple(): void
    {
        $query = Builder::select(['a', 'b'])->count('*', 'mon compteur')->whereSimple(1);
        $this->assertSame('SELECT COUNT(*) AS `mon compteur`,`a`,`b` FROM dual WHERE ?', $query->toSql());
        $this->assertSame([1], $query->getPlaceHolderValues());
    }

    public function testSelectNoDbNoTableCountAsWhereSimpleOrderByAs(): void
    {
        $query = Builder::select(['a', 'b'])->count('*', 'mon compteur')->whereSimple(1)->orderBy('mon compteur');
        $this->assertSame('SELECT COUNT(*) AS `mon compteur`,`a`,`b` FROM dual WHERE ? ORDER BY `mon compteur`', $query->toSql());
        $this->assertSame([1], $query->getPlaceHolderValues());
    }

    public function testSelectNoDbNoTableCountAsColumnWhereSimpleOrderByAs(): void
    {
        $query = Builder::select(['a', 'b'])->count('d', 'mon compteur')->whereSimple(1)->orderBy('mon compteur');
        $this->assertSame('SELECT COUNT(`d`) AS `mon compteur`,`a`,`b` FROM dual WHERE ? ORDER BY `mon compteur`', $query->toSql());
        $this->assertSame([1], $query->getPlaceHolderValues());
    }
}
