<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ParseAnalyze::class)]
class ParseAnalyzeTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testSqlQuery(): void
    {
        Current::$lang = 'en';
        ResponseRenderer::getInstance()->setAjax(false);

        $actual = ParseAnalyze::sqlQuery('SELECT * FROM `sakila`.`actor`', 'sakila_test');

        self::assertSame('sakila', $actual[1]);
        self::assertSame('actor', $actual[2]);
        self::assertTrue($actual[3]);
        self::assertNotEmpty($actual[0]->selectTables);
        self::assertSame([['actor', 'sakila']], $actual[0]->selectTables);
        self::assertNotEmpty($actual[0]->selectExpressions);
        self::assertSame(['*'], $actual[0]->selectExpressions);
    }

    public function testSqlQuery2(): void
    {
        Current::$lang = 'en';
        ResponseRenderer::getInstance()->setAjax(false);

        $actual = ParseAnalyze::sqlQuery('SELECT `first_name`, `title` FROM `actor`, `film`', 'sakila');

        self::assertSame('sakila', $actual[1]);
        self::assertSame('', $actual[2]);
        self::assertFalse($actual[0]->flags->reload);
        self::assertNotEmpty($actual[0]->selectTables);
        self::assertSame([['actor', null], ['film', null]], $actual[0]->selectTables);
        self::assertNotEmpty($actual[0]->selectExpressions);
        self::assertSame(['`first_name`', '`title`'], $actual[0]->selectExpressions);
    }
}
