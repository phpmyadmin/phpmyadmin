<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\StatementInfo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\StatementInfo
 */
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

        $this->assertFalse($statementInfo->distinct);
        $this->assertFalse($statementInfo->dropDatabase);
        $this->assertFalse($statementInfo->group);
        $this->assertFalse($statementInfo->having);
        $this->assertFalse($statementInfo->isAffected);
        $this->assertFalse($statementInfo->isAnalyse);
        $this->assertFalse($statementInfo->isCount);
        $this->assertFalse($statementInfo->isDelete);
        $this->assertFalse($statementInfo->isExplain);
        $this->assertFalse($statementInfo->isExport);
        $this->assertFalse($statementInfo->isFunction);
        $this->assertFalse($statementInfo->isGroup);
        $this->assertFalse($statementInfo->isInsert);
        $this->assertFalse($statementInfo->isMaint);
        $this->assertFalse($statementInfo->isProcedure);
        $this->assertFalse($statementInfo->isReplace);
        $this->assertTrue($statementInfo->isSelect);
        $this->assertFalse($statementInfo->isShow);
        $this->assertFalse($statementInfo->isSubquery);
        $this->assertFalse($statementInfo->join);
        $this->assertFalse($statementInfo->limit);
        $this->assertFalse($statementInfo->offset);
        $this->assertFalse($statementInfo->order);
        $this->assertSame('SELECT', $statementInfo->queryType);
        $this->assertFalse($statementInfo->reload);
        $this->assertTrue($statementInfo->selectFrom);
        $this->assertFalse($statementInfo->union);
        $this->assertNotNull($statementInfo->parser);
        $this->assertNotNull($statementInfo->statement);
        $this->assertNotEmpty($statementInfo->selectTables);
        $this->assertNotEmpty($statementInfo->selectExpression);
        $this->assertSame($info['parser'], $statementInfo->parser);
        $this->assertSame($info['statement'], $statementInfo->statement);
        $this->assertSame($info['select_tables'], $statementInfo->selectTables);
        $this->assertSame($info['select_expr'], $statementInfo->selectExpression);
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

        $this->assertFalse($statementInfo->distinct);
        $this->assertFalse($statementInfo->dropDatabase);
        $this->assertFalse($statementInfo->group);
        $this->assertFalse($statementInfo->having);
        $this->assertFalse($statementInfo->isAffected);
        $this->assertFalse($statementInfo->isAnalyse);
        $this->assertFalse($statementInfo->isCount);
        $this->assertFalse($statementInfo->isDelete);
        $this->assertFalse($statementInfo->isExplain);
        $this->assertFalse($statementInfo->isExport);
        $this->assertFalse($statementInfo->isFunction);
        $this->assertFalse($statementInfo->isGroup);
        $this->assertFalse($statementInfo->isInsert);
        $this->assertFalse($statementInfo->isMaint);
        $this->assertFalse($statementInfo->isProcedure);
        $this->assertFalse($statementInfo->isReplace);
        $this->assertFalse($statementInfo->isSelect);
        $this->assertFalse($statementInfo->isShow);
        $this->assertFalse($statementInfo->isSubquery);
        $this->assertFalse($statementInfo->join);
        $this->assertFalse($statementInfo->limit);
        $this->assertFalse($statementInfo->offset);
        $this->assertFalse($statementInfo->order);
        $this->assertFalse($statementInfo->queryType);
        $this->assertFalse($statementInfo->reload);
        $this->assertFalse($statementInfo->selectFrom);
        $this->assertFalse($statementInfo->union);
        $this->assertNull($statementInfo->parser);
        $this->assertNull($statementInfo->statement);
        $this->assertEmpty($statementInfo->selectTables);
        $this->assertEmpty($statementInfo->selectExpression);
    }
}
