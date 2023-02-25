<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Database\Qbe */
class QbeTest extends AbstractTestCase
{
    public function testGetSortSelectCell(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertStringContainsString(
            'style="width:12ex" name="criteriaSort[1]"',
            $this->callFunction($object, Qbe::class, 'getSortSelectCell', [1]),
        );
        $this->assertStringNotContainsString(
            'selected="selected"',
            $this->callFunction($object, Qbe::class, 'getSortSelectCell', [1]),
        );
        $this->assertStringContainsString(
            'value="ASC" selected="selected">',
            $this->callFunction($object, Qbe::class, 'getSortSelectCell', [1, 'ASC']),
        );
    }

    public function testGetSortRow(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertStringContainsString(
            'name="criteriaSort[0]"',
            $this->callFunction($object, Qbe::class, 'getSortRow', []),
        );
        $this->assertStringContainsString(
            'name="criteriaSort[1]"',
            $this->callFunction($object, Qbe::class, 'getSortRow', []),
        );
        $this->assertStringContainsString(
            'name="criteriaSort[2]"',
            $this->callFunction($object, Qbe::class, 'getSortRow', []),
        );
    }

    public function testGetShowRow(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            '<td class="text-center"><input type'
            . '="checkbox" name="criteriaShow[0]"></td><td class="text-center">'
            . '<input type="checkbox" name="criteriaShow[1]"></td><td '
            . 'class="text-center"><input type="checkbox" name="criteriaShow[2]">'
            . '</td>',
            $this->callFunction($object, Qbe::class, 'getShowRow', []),
        );
    }

    public function testGetCriteriaInputboxRow(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            '<td class="text-center">'
            . '<input type="hidden" name="prev_criteria[0]" value="">'
            . '<input type="text" name="criteria[0]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td><td class="text-center">'
            . '<input type="hidden" name="prev_criteria[1]" value="">'
            . '<input type="text" name="criteria[1]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td><td class="text-center">'
            . '<input type="hidden" name="prev_criteria[2]" value="">'
            . '<input type="text" name="criteria[2]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td>',
            $this->callFunction($object, Qbe::class, 'getCriteriaInputboxRow', []),
        );
    }

    public function testGetAndOrColCell(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            '<td class="text-center"><strong>Or:</strong><input type="radio" '
            . 'name="criteriaAndOrColumn[1]" value="or">&nbsp;&nbsp;<strong>And:'
            . '</strong><input type="radio" name="criteriaAndOrColumn[1]" value='
            . '"and"><br>Ins<input type="checkbox" name="criteriaColumnInsert'
            . '[1]">&nbsp;&nbsp;Del<input type="checkbox" '
            . 'name="criteriaColumnDelete[1]"></td>',
            $this->callFunction($object, Qbe::class, 'getAndOrColCell', [1]),
        );
    }

    public function testGetModifyColumnsRow(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            '<td class="text-center"><strong>'
            . 'Or:</strong><input type="radio" name="criteriaAndOrColumn[0]" value'
            . '="or">&nbsp;&nbsp;<strong>And:</strong><input type="radio" name='
            . '"criteriaAndOrColumn[0]" value="and" checked="checked"><br>Ins'
            . '<input type="checkbox" name="criteriaColumnInsert[0]">&nbsp;&nbsp;'
            . 'Del<input type="checkbox" name="criteriaColumnDelete[0]"></td><td '
            . 'class="text-center"><strong>Or:</strong><input type="radio" name="'
            . 'criteriaAndOrColumn[1]" value="or">&nbsp;&nbsp;<strong>And:'
            . '</strong><input type="radio" name="criteriaAndOrColumn[1]" value='
            . '"and" checked="checked"><br>Ins<input type="checkbox" name='
            . '"criteriaColumnInsert[1]">&nbsp;&nbsp;Del<input type="checkbox" '
            . 'name="criteriaColumnDelete[1]"></td><td class="text-center"><br>Ins'
            . '<input type="checkbox" name="criteriaColumnInsert[2]">&nbsp;&nbsp;'
            . 'Del<input type="checkbox" name="criteriaColumnDelete[2]"></td>',
            $this->callFunction($object, Qbe::class, 'getModifyColumnsRow', []),
        );
    }

    public function testGetInputboxRow(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            '<td class="text-center"><input type="text" name="Or2[0]" value="" class='
            . '"textfield" style="width: 12ex" size="20"></td><td class="text-center">'
            . '<input type="text" name="Or2[1]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td><td class="text-center"><input '
            . 'type="text" name="Or2[2]" value="" class="textfield" style="width: '
            . '12ex" size="20"></td>',
            $this->callFunction($object, Qbe::class, 'getInputboxRow', [2]),
        );
    }

    public function testGetInsDelAndOrCriteriaRows(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $actual = $this->callFunction($object, Qbe::class, 'getInsDelAndOrCriteriaRows', [2, 3]);

        $this->assertStringContainsString('<tr class="noclick">', $actual);
        $this->assertStringContainsString(
            '<td class="text-center"><input type="text" '
            . 'name="Or0[0]" value="" class="textfield" style="width: 12ex" '
            . 'size="20"></td><td class="text-center"><input type="text" name="Or0[1]" '
            . 'value="" class="textfield" style="width: 12ex" size="20"></td><td '
            . 'class="text-center"><input type="text" name="Or0[2]" value="" class='
            . '"textfield" style="width: 12ex" size="20"></td></tr>',
            $actual,
        );
    }

    public function testGetSelectClause(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals('', $this->callFunction($object, Qbe::class, 'getSelectClause', []));
    }

    public function testGetWhereClause(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals('', $this->callFunction($object, Qbe::class, 'getWhereClause', []));
    }

    public function testGetOrderByClause(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals('', $this->callFunction($object, Qbe::class, 'getOrderByClause', []));
    }

    public function testGetIndexes(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SHOW INDEXES FROM `pma_test`.```table1```', []);
        $GLOBALS['dbi'] = $this->createDatabaseInterface($dbiDummy);
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            ['unique' => [], 'index' => []],
            $this->callFunction(
                $object,
                Qbe::class,
                'getIndexes',
                [
                    ['`table1`', 'table2'],
                    ['column1', 'column2', 'column3'],
                    ['column2'],
                ],
            ),
        );
    }

    public function testGetLeftJoinColumnCandidates(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('pma_test');
        $dbiDummy->addResult('SHOW INDEXES FROM `pma_test`.```table1```', []);
        $GLOBALS['dbi'] = $this->createDatabaseInterface($dbiDummy);
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            [0 => 'column2'],
            $this->callFunction(
                $object,
                Qbe::class,
                'getLeftJoinColumnCandidates',
                [
                    ['`table1`', 'table2'],
                    ['column1', 'column2', 'column3'],
                    ['column2'],
                ],
            ),
        );
    }

    public function testGetMasterTable(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $this->assertEquals(
            0,
            $this->callFunction(
                $object,
                Qbe::class,
                'getMasterTable',
                [
                    ['table1', 'table2'],
                    ['column1', 'column2', 'column3'],
                    ['column2'],
                    ['qbe_test'],
                ],
            ),
        );
    }

    public function testGetWhereClauseTablesAndColumns(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            ['where_clause_tables' => [], 'where_clause_columns' => []],
            $this->callFunction($object, Qbe::class, 'getWhereClauseTablesAndColumns', []),
        );
    }

    public function testGetFromClause(): void
    {
        $GLOBALS['db'] = 'pma_test';
        $dbiDummy = $this->createDbiDummy();
        $createTableStatement = 'CREATE TABLE `table1` (`id` int(11) NOT NULL,`value` int(11) NOT NULL,'
            . 'PRIMARY KEY (`id`,`value`),KEY `value` (`value`)) ENGINE=InnoDB DEFAULT CHARSET=latin1';
        $dbiDummy->addResult('SHOW CREATE TABLE `pma_test`.`table1`', [[$createTableStatement]]);
        $dbiDummy->addSelectDb('pma_test');
        $dbiDummy->addResult('SHOW CREATE TABLE `pma_test`.`table1`', [[$createTableStatement]]);
        $GLOBALS['dbi'] = $this->createDatabaseInterface($dbiDummy);
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals('`table1`', $this->callFunction($object, Qbe::class, 'getFromClause', [['`table1`.`id`']]));
    }

    public function testGetSQLQuery(): void
    {
        $GLOBALS['db'] = 'pma_test';
        $dbiDummy = $this->createDbiDummy();
        $createTableStatement = 'CREATE TABLE `table1` (`id` int(11) NOT NULL,`value` int(11) NOT NULL,'
            . 'PRIMARY KEY (`id`,`value`),KEY `value` (`value`)) ENGINE=InnoDB DEFAULT CHARSET=latin1';
        $dbiDummy->addResult('SHOW CREATE TABLE `pma_test`.`table1`', [[$createTableStatement]]);
        $dbiDummy->addSelectDb('pma_test');
        $dbiDummy->addResult('SHOW CREATE TABLE `pma_test`.`table1`', [[$createTableStatement]]);
        $GLOBALS['dbi'] = $this->createDatabaseInterface($dbiDummy);
        $object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            'FROM `table1`' . "\n",
            $this->callFunction($object, Qbe::class, 'getSQLQuery', [['`table1`.`id`']]),
        );
    }
}
