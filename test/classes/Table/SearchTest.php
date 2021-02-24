<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Tests\AbstractTestCase;

class SearchTest extends AbstractTestCase
{
    /** @var Search */
    private $search;

    protected function setUp(): void
    {
        parent::setUp();
        global $dbi;

        $this->search = new Search($dbi);
    }

    public function testBuildSqlQuery(): void
    {
        $_POST['distinct'] = true;
        $_POST['zoom_submit'] = true;
        $_POST['table'] = 'PMA';
        $_POST['orderByColumn'] = 'name';
        $_POST['order'] = 'asc';
        $_POST['customWhereClause'] = "name='pma'";

        $this->assertEquals(
            'SELECT DISTINCT *  FROM `PMA` WHERE name=\'pma\' ORDER BY `name` asc',
            $this->search->buildSqlQuery()
        );

        unset($_POST['customWhereClause']);

        $this->assertEquals(
            'SELECT DISTINCT *  FROM `PMA` ORDER BY `name` asc',
            $this->search->buildSqlQuery()
        );

        $_POST['criteriaValues'] = [
            'value1',
            'value2',
            'value3',
            'value4',
            'value5',
            'value6',
            'value7,value8',
        ];
        $_POST['criteriaColumnNames'] = [
            'name',
            'id',
            'index',
            'index2',
            'index3',
            'index4',
            'index5',
        ];
        $_POST['criteriaColumnTypes'] = [
            'varchar',
            'int',
            'enum',
            'type1',
            'type2',
            'type3',
            'type4',
        ];
        $_POST['criteriaColumnCollations'] = [
            'char1',
            'char2',
            'char3',
            'char4',
            'char5',
            'char6',
            'char7',
        ];
        $_POST['criteriaColumnOperators'] = [
            '!=',
            '>',
            'IS NULL',
            'LIKE %...%',
            'REGEXP ^...$',
            'IN (...)',
            'BETWEEN',
        ];

        $expected = 'SELECT DISTINCT *  FROM `PMA` WHERE `name` != \'value1\''
            . ' AND `id` > value2 AND `index` IS NULL AND `index2` LIKE \'%value4%\''
            . ' AND `index3` REGEXP ^value5$ AND `index4` IN (value6) AND `index5`'
            . ' BETWEEN value7 AND value8 ORDER BY `name` asc';
        $this->assertEquals(
            $expected,
            $this->search->buildSqlQuery()
        );
    }

    public function testBuildSqlQueryWithWhereClause(): void
    {
        $_POST['zoom_submit'] = true;
        $_POST['table'] = 'PMA';

        $this->assertEquals(
            'SELECT *  FROM `PMA`',
            $this->search->buildSqlQuery()
        );

        $_POST['customWhereClause'] = '`table` = \'WhereClause\'';

        $this->assertEquals(
            'SELECT *  FROM `PMA` WHERE `table` = \'WhereClause\'',
            $this->search->buildSqlQuery()
        );

        unset($_POST['customWhereClause']);
        $_POST['criteriaColumnNames'] = [
            'b',
            'a',
            'c',
            'd',
        ];
        $_POST['criteriaColumnOperators'] = [
            '<=',
            '=',
            'IS NULL',
            'IS NOT NULL',
        ];
        $_POST['criteriaValues'] = [
            '10',
            '2',
            '',
            '',
        ];
        $_POST['criteriaColumnTypes'] = [
            'int(11)',
            'int(11)',
            'int(11)',
            'int(11)',
        ];

        $this->assertEquals(
            'SELECT *  FROM `PMA` WHERE `b` <= 10 AND `a` = 2 AND `c` IS NULL AND `d` IS NOT NULL',
            $this->search->buildSqlQuery()
        );
    }

    public function testBuildSqlQueryWithWhereClauseGeom(): void
    {
        $_POST['zoom_submit'] = true;
        $_POST['table'] = 'PMA';

        $this->assertEquals(
            'SELECT *  FROM `PMA`',
            $this->search->buildSqlQuery()
        );

        $_POST['customWhereClause'] = '`table` = \'WhereClause\'';

        $this->assertEquals(
            'SELECT *  FROM `PMA` WHERE `table` = \'WhereClause\'',
            $this->search->buildSqlQuery()
        );

        unset($_POST['customWhereClause']);
        $_POST['criteriaColumnNames'] = ['b'];
        $_POST['criteriaColumnOperators'] = ['='];
        $_POST['geom_func'] = ['Dimension'];
        $_POST['criteriaValues'] = ['1'];
        $_POST['criteriaColumnTypes'] = ['geometry'];

        $this->assertEquals(
            'SELECT *  FROM `PMA` WHERE Dimension(`b`) = \'1\'',
            $this->search->buildSqlQuery()
        );
    }

    public function testBuildSqlQueryWithWhereClauseEnum(): void
    {
        $_POST['zoom_submit'] = true;
        $_POST['table'] = 'PMA';

        $this->assertEquals(
            'SELECT *  FROM `PMA`',
            $this->search->buildSqlQuery()
        );

        $_POST['customWhereClause'] = '`table` = \'WhereClause\'';

        $this->assertEquals(
            'SELECT *  FROM `PMA` WHERE `table` = \'WhereClause\'',
            $this->search->buildSqlQuery()
        );

        unset($_POST['customWhereClause']);
        $_POST['criteriaColumnNames'] = ['rating'];
        $_POST['criteriaColumnOperators'] = ['='];

        $_POST['criteriaValues'] = ['PG-13'];
        $_POST['criteriaColumnTypes'] = ['enum(\'G\', \'PG\', \'PG-13\', \'R\', \'NC-17\')'];

        $this->assertEquals(
            'SELECT *  FROM `PMA` WHERE `rating` = \'PG-13\'',
            $this->search->buildSqlQuery()
        );
    }
}
