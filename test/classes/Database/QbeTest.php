<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;

class QbeTest extends AbstractTestCase
{
    /** @var Qbe */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'pma_test';
        $this->object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        //mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $create_table = 'CREATE TABLE `table1` ('
            . '`id` int(11) NOT NULL,'
            . '`value` int(11) NOT NULL,'
            . 'PRIMARY KEY (`id`,`value`),'
            . 'KEY `value` (`value`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=latin1';

        $dbi->expects($this->any())
            ->method('fetchValue')
            ->with('SHOW CREATE TABLE `pma_test`.`table1`', 0, 1)
            ->will($this->returnValue($create_table));

        $dbi->expects($this->any())
            ->method('getTableIndexes')
            ->will($this->returnValue([]));

        $GLOBALS['dbi'] = $dbi;
        $this->object->dbi = $dbi;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for getSortSelectCell
     */
    public function testGetSortSelectCell(): void
    {
        $this->assertStringContainsString(
            'style="width:12ex" name="criteriaSort[1]"',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSortSelectCell',
                [1]
            )
        );
        $this->assertStringNotContainsString(
            'selected="selected"',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSortSelectCell',
                [1]
            )
        );
        $this->assertStringContainsString(
            'value="ASC" selected="selected">',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSortSelectCell',
                [
                    1,
                    'ASC',
                ]
            )
        );
    }

    /**
     * Test for getSortRow
     */
    public function testGetSortRow(): void
    {
        $this->assertStringContainsString(
            'name="criteriaSort[0]"',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSortRow',
                []
            )
        );
        $this->assertStringContainsString(
            'name="criteriaSort[1]"',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSortRow',
                []
            )
        );
        $this->assertStringContainsString(
            'name="criteriaSort[2]"',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSortRow',
                []
            )
        );
    }

    /**
     * Test for getShowRow
     */
    public function testGetShowRow(): void
    {
        $this->assertEquals(
            '<td class="text-center"><input type'
            . '="checkbox" name="criteriaShow[0]"></td><td class="text-center">'
            . '<input type="checkbox" name="criteriaShow[1]"></td><td '
            . 'class="text-center"><input type="checkbox" name="criteriaShow[2]">'
            . '</td>',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getShowRow',
                []
            )
        );
    }

    /**
     * Test for getCriteriaInputboxRow
     */
    public function testGetCriteriaInputboxRow(): void
    {
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
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getCriteriaInputboxRow',
                []
            )
        );
    }

    /**
     * Test for getAndOrColCell
     */
    public function testGetAndOrColCell(): void
    {
        $this->assertEquals(
            '<td class="text-center"><strong>Or:</strong><input type="radio" '
            . 'name="criteriaAndOrColumn[1]" value="or">&nbsp;&nbsp;<strong>And:'
            . '</strong><input type="radio" name="criteriaAndOrColumn[1]" value='
            . '"and"><br>Ins<input type="checkbox" name="criteriaColumnInsert'
            . '[1]">&nbsp;&nbsp;Del<input type="checkbox" '
            . 'name="criteriaColumnDelete[1]"></td>',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getAndOrColCell',
                [1]
            )
        );
    }

    /**
     * Test for getModifyColumnsRow
     */
    public function testGetModifyColumnsRow(): void
    {
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
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getModifyColumnsRow',
                []
            )
        );
    }

    /**
     * Test for getInputboxRow
     */
    public function testGetInputboxRow(): void
    {
        $this->assertEquals(
            '<td class="text-center"><input type="text" name="Or2[0]" value="" class='
            . '"textfield" style="width: 12ex" size="20"></td><td class="text-center">'
            . '<input type="text" name="Or2[1]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td><td class="text-center"><input '
            . 'type="text" name="Or2[2]" value="" class="textfield" style="width: '
            . '12ex" size="20"></td>',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getInputboxRow',
                [2]
            )
        );
    }

    /**
     * Test for getInsDelAndOrCriteriaRows
     */
    public function testGetInsDelAndOrCriteriaRows(): void
    {
        $actual = $this->callFunction(
            $this->object,
            Qbe::class,
            'getInsDelAndOrCriteriaRows',
            [
                2,
                3,
            ]
        );

        $this->assertStringContainsString('<tr class="noclick">', $actual);
        $this->assertStringContainsString(
            '<td class="text-center"><input type="text" '
            . 'name="Or0[0]" value="" class="textfield" style="width: 12ex" '
            . 'size="20"></td><td class="text-center"><input type="text" name="Or0[1]" '
            . 'value="" class="textfield" style="width: 12ex" size="20"></td><td '
            . 'class="text-center"><input type="text" name="Or0[2]" value="" class='
            . '"textfield" style="width: 12ex" size="20"></td></tr>',
            $actual
        );
    }

    /**
     * Test for getSelectClause
     */
    public function testGetSelectClause(): void
    {
        $this->assertEquals(
            '',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSelectClause',
                []
            )
        );
    }

    /**
     * Test for getWhereClause
     */
    public function testGetWhereClause(): void
    {
        $this->assertEquals(
            '',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getWhereClause',
                []
            )
        );
    }

    /**
     * Test for getOrderByClause
     */
    public function testGetOrderByClause(): void
    {
        $this->assertEquals(
            '',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getOrderByClause',
                []
            )
        );
    }

    /**
     * Test for getIndexes
     */
    public function testGetIndexes(): void
    {
        $this->assertEquals(
            [
                'unique' => [],
                'index' => [],
            ],
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getIndexes',
                [
                    [
                        '`table1`',
                        'table2',
                    ],
                    [
                        'column1',
                        'column2',
                        'column3',
                    ],
                    ['column2'],
                ]
            )
        );
    }

    /**
     * Test for getLeftJoinColumnCandidates
     */
    public function testGetLeftJoinColumnCandidates(): void
    {
        $this->assertEquals(
            [0 => 'column2'],
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getLeftJoinColumnCandidates',
                [
                    [
                        '`table1`',
                        'table2',
                    ],
                    [
                        'column1',
                        'column2',
                        'column3',
                    ],
                    ['column2'],
                ]
            )
        );
    }

    /**
     * Test for getMasterTable
     */
    public function testGetMasterTable(): void
    {
        $this->assertEquals(
            0,
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getMasterTable',
                [
                    [
                        'table1',
                        'table2',
                    ],
                    [
                        'column1',
                        'column2',
                        'column3',
                    ],
                    ['column2'],
                    ['qbe_test'],
                ]
            )
        );
    }

    /**
     * Test for getWhereClauseTablesAndColumns
     */
    public function testGetWhereClauseTablesAndColumns(): void
    {
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            [
                'where_clause_tables' => [],
                'where_clause_columns' => [],
            ],
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getWhereClauseTablesAndColumns',
                []
            )
        );
    }

    /**
     * Test for getFromClause
     */
    public function testGetFromClause(): void
    {
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            '`table1`',
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getFromClause',
                [['`table1`.`id`']]
            )
        );
    }

    /**
     * Test for getSQLQuery
     */
    public function testGetSQLQuery(): void
    {
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            'FROM `table1`' . "\n",
            $this->callFunction(
                $this->object,
                Qbe::class,
                'getSQLQuery',
                [['`table1`.`id`']]
            )
        );
    }
}
