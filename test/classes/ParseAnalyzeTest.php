<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\StatementInfo;

/**
 * @covers \PhpMyAdmin\ParseAnalyze
 */
class ParseAnalyzeTest extends AbstractTestCase
{
    public function testSqlQuery(): void
    {
        $GLOBALS['lang'] = 'en';
        ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['unparsed_sql'] = '';

        $actual = ParseAnalyze::sqlQuery('SELECT * FROM `sakila`.`actor`', 'sakila_test');

        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertSame('SELECT * FROM `sakila`.`actor`', $GLOBALS['unparsed_sql']);
        $this->assertCount(3, $actual);
        $this->assertInstanceOf(StatementInfo::class, $actual[0]);
        $this->assertSame('sakila', $actual[1]);
        $this->assertSame('actor', $actual[2]);
        $this->assertTrue($actual[0]->reload);
        $this->assertNotEmpty($actual[0]->selectTables);
        $this->assertSame([['actor', 'sakila']], $actual[0]->selectTables);
        $this->assertNotEmpty($actual[0]->selectExpression);
        $this->assertSame(['*'], $actual[0]->selectExpression);
    }

    public function testSqlQuery2(): void
    {
        $GLOBALS['lang'] = 'en';
        ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['unparsed_sql'] = '';

        $actual = ParseAnalyze::sqlQuery('SELECT `first_name`, `title` FROM `actor`, `film`', 'sakila');

        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertSame('SELECT `first_name`, `title` FROM `actor`, `film`', $GLOBALS['unparsed_sql']);
        $this->assertCount(3, $actual);
        $this->assertInstanceOf(StatementInfo::class, $actual[0]);
        $this->assertSame('sakila', $actual[1]);
        $this->assertSame('', $actual[2]);
        $this->assertFalse($actual[0]->reload);
        $this->assertNotEmpty($actual[0]->selectTables);
        $this->assertSame([['actor', null], ['film', null]], $actual[0]->selectTables);
        $this->assertNotEmpty($actual[0]->selectExpression);
        $this->assertSame(['`first_name`', '`title`'], $actual[0]->selectExpression);
    }
}
