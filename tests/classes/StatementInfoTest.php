<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\StatementInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StatementInfo::class)]
class StatementInfoTest extends TestCase
{
    public function testFromArray(): void
    {
        $parser = new Parser('SELECT * FROM `sakila`.`actor`');
        $statement = $parser->statements[0];
        $info = [
            'distinct' => false,
            'drop_database' => false,
            'group' => false,
            'having' => false,
            'is_affected' => false,
            'is_analyse' => false,
            'is_count' => false,
            'is_delete' => false,
            'is_explain' => false,
            'is_export' => false,
            'is_func' => false,
            'is_group' => false,
            'is_insert' => false,
            'is_maint' => false,
            'is_procedure' => false,
            'is_replace' => false,
            'is_select' => true,
            'is_show' => false,
            'is_subquery' => false,
            'join' => false,
            'limit' => false,
            'offset' => false,
            'order' => false,
            'querytype' => 'SELECT',
            'reload' => false,
            'select_from' => true,
            'union' => false,
            'parser' => $parser,
            'statement' => $statement,
            'select_tables' => [['actor', null]],
            'select_expr' => ['*'],
        ];

        $statementInfo = StatementInfo::fromArray($info);

        self::assertFalse($statementInfo->distinct);
        self::assertFalse($statementInfo->dropDatabase);
        self::assertFalse($statementInfo->group);
        self::assertFalse($statementInfo->having);
        self::assertFalse($statementInfo->isAffected);
        self::assertFalse($statementInfo->isAnalyse);
        self::assertFalse($statementInfo->isCount);
        self::assertFalse($statementInfo->isDelete);
        self::assertFalse($statementInfo->isExplain);
        self::assertFalse($statementInfo->isExport);
        self::assertFalse($statementInfo->isFunction);
        self::assertFalse($statementInfo->isGroup);
        self::assertFalse($statementInfo->isInsert);
        self::assertFalse($statementInfo->isMaint);
        self::assertFalse($statementInfo->isProcedure);
        self::assertFalse($statementInfo->isReplace);
        self::assertTrue($statementInfo->isSelect);
        self::assertFalse($statementInfo->isShow);
        self::assertFalse($statementInfo->isSubquery);
        self::assertFalse($statementInfo->join);
        self::assertFalse($statementInfo->limit);
        self::assertFalse($statementInfo->offset);
        self::assertFalse($statementInfo->order);
        self::assertSame('SELECT', $statementInfo->queryType);
        self::assertFalse($statementInfo->reload);
        self::assertTrue($statementInfo->selectFrom);
        self::assertFalse($statementInfo->union);
        self::assertNotNull($statementInfo->parser);
        self::assertNotNull($statementInfo->statement);
        self::assertNotEmpty($statementInfo->selectTables);
        self::assertNotEmpty($statementInfo->selectExpression);
        self::assertSame($info['parser'], $statementInfo->parser);
        self::assertSame($info['statement'], $statementInfo->statement);
        self::assertSame($info['select_tables'], $statementInfo->selectTables);
        self::assertSame($info['select_expr'], $statementInfo->selectExpression);
    }

    public function testFromArrayWithEmptyStatement(): void
    {
        $info = [
            'distinct' => false,
            'drop_database' => false,
            'group' => false,
            'having' => false,
            'is_affected' => false,
            'is_analyse' => false,
            'is_count' => false,
            'is_delete' => false,
            'is_explain' => false,
            'is_export' => false,
            'is_func' => false,
            'is_group' => false,
            'is_insert' => false,
            'is_maint' => false,
            'is_procedure' => false,
            'is_replace' => false,
            'is_select' => false,
            'is_show' => false,
            'is_subquery' => false,
            'join' => false,
            'limit' => false,
            'offset' => false,
            'order' => false,
            'querytype' => false,
            'reload' => false,
            'select_from' => false,
            'union' => false,
        ];

        $statementInfo = StatementInfo::fromArray($info);

        self::assertFalse($statementInfo->distinct);
        self::assertFalse($statementInfo->dropDatabase);
        self::assertFalse($statementInfo->group);
        self::assertFalse($statementInfo->having);
        self::assertFalse($statementInfo->isAffected);
        self::assertFalse($statementInfo->isAnalyse);
        self::assertFalse($statementInfo->isCount);
        self::assertFalse($statementInfo->isDelete);
        self::assertFalse($statementInfo->isExplain);
        self::assertFalse($statementInfo->isExport);
        self::assertFalse($statementInfo->isFunction);
        self::assertFalse($statementInfo->isGroup);
        self::assertFalse($statementInfo->isInsert);
        self::assertFalse($statementInfo->isMaint);
        self::assertFalse($statementInfo->isProcedure);
        self::assertFalse($statementInfo->isReplace);
        self::assertFalse($statementInfo->isSelect);
        self::assertFalse($statementInfo->isShow);
        self::assertFalse($statementInfo->isSubquery);
        self::assertFalse($statementInfo->join);
        self::assertFalse($statementInfo->limit);
        self::assertFalse($statementInfo->offset);
        self::assertFalse($statementInfo->order);
        self::assertFalse($statementInfo->queryType);
        self::assertFalse($statementInfo->reload);
        self::assertFalse($statementInfo->selectFrom);
        self::assertFalse($statementInfo->union);
        self::assertNull($statementInfo->parser);
        self::assertNull($statementInfo->statement);
        self::assertEmpty($statementInfo->selectTables);
        self::assertEmpty($statementInfo->selectExpression);
    }
}
