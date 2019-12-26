<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for operations
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Operations;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Theme;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * tests for operations
 *
 * @package PhpMyAdmin-test
 */
class OperationsTest extends TestCase
{
    /**
     * @var Operations
     */
    private $operations;

    /**
     * Set up global environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';
        $GLOBALS['cfg'] = [
            'ServerDefault' => 1,
            'ActionLinksMode' => 'icons',
            'LinkLengthLimit' => 1000,
        ];
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['server'] = 1;

        $GLOBALS['db_priv'] = true;
        $GLOBALS['table_priv'] = true;
        $GLOBALS['col_priv'] = true;
        $GLOBALS['proc_priv'] = true;
        $GLOBALS['flush_priv'] = true;
        $GLOBALS['is_reload_priv'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $relation = new Relation($GLOBALS['dbi']);
        $this->operations = new Operations($GLOBALS['dbi'], $relation);
    }

    /**
     * Test for getHtmlForDatabaseComment
     *
     * @return void
     */
    public function testGetHtmlForDatabaseComment()
    {

        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*Database comment.*name="comment"([\n]|.)*/m',
            $this->operations->getHtmlForDatabaseComment("pma")
        );
    }

    /**
     * Test for getHtmlForRenameDatabase
     *
     * @return void
     */
    public function testGetHtmlForRenameDatabase()
    {

        $db_collation = 'db1';
        $html = $this->operations->getHtmlForRenameDatabase("pma", $db_collation);
        $this->assertStringContainsString('db_operations.php', $html);
        $this->assertRegExp(
            '/.*db_rename.*Rename database to.*/',
            $html
        );
    }

    /**
     * Test for getHtmlForDropDatabaseLink
     *
     * @return void
     */
    public function testGetHtmlForDropDatabaseLink()
    {

        $this->assertRegExp(
            '/.*DROP.DATABASE.*db_operations.php.*Drop the database.*/',
            $this->operations->getHtmlForDropDatabaseLink("pma")
        );
    }

    /**
     * Test for getHtmlForCopyDatabase
     *
     * @return void
     */
    public function testGetHtmlForCopyDatabase()
    {
        $db_collation = 'db1';
        $html = $this->operations->getHtmlForCopyDatabase("pma", $db_collation);
        $this->assertRegExp('/.*db_operations.php.*/', $html);
        $this->assertRegExp('/.*db_copy.*/', $html);
        $this->assertRegExp('/.*Copy database to.*/', $html);
    }

    /**
     * Test for getHtmlForChangeDatabaseCharset
     *
     * @return void
     */
    public function testGetHtmlForChangeDatabaseCharset()
    {

        $db_collation = 'db1';
        $result = $this->operations->getHtmlForChangeDatabaseCharset("pma", $db_collation);
        $this->assertRegExp(
            '/.*select_db_collation.*Collation.*/m',
            $result
        );
        $this->assertRegExp(
            '/.*db_operations.php.*/',
            $result
        );
    }

    /**
     * Test for getHtmlForOrderTheTable
     *
     * @return void
     */
    public function testGetHtmlForOrderTheTable()
    {

        $this->assertRegExp(
            '/.*tbl_operations.php(.|[\n])*Alter table order by([\n]|.)*order_order.*/m',
            $this->operations->getHtmlForOrderTheTable(
                [
                    ['Field' => "column1"],
                    ['Field' => "column2"],
                ]
            )
        );
    }

    /**
     * Test for getHtmlForTableRow
     *
     * @return void
     */
    public function testGetHtmlForTableRow()
    {
        $method = new ReflectionMethod(Operations::class, 'getHtmlForTableRow');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->operations, ['name', 'lable', 'value']);

        $this->assertEquals(
            '<tr><td class="vmiddle"><label for="name">lable</label></td><td><input type="checkbox" name="name" id="name" value="1"></td></tr>',
            $result
        );
    }

    /**
     * Test for getMaintainActionlink
     *
     * @return void
     */
    public function testGetMaintainActionlink()
    {
        $method = new ReflectionMethod(Operations::class, 'getMaintainActionlink');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->operations, [
            'post',
            [
                'name' => 'foo',
                'value' => 'bar',
            ],
            [],
            'doclink',
        ]);

        $this->assertRegExp(
            '/.*href="sql.php.*post.*/',
            $result
        );
    }

    /**
     * Test for getHtmlForDeleteDataOrTable
     *
     * @return void
     */
    public function testGetHtmlForDeleteDataOrTable()
    {

        $this->assertRegExp(
            '/.*Delete data or table.*Empty the table.*Delete the table.*/m',
            $this->operations->getHtmlForDeleteDataOrTable(
                ["truncate" => 'foo'],
                ["drop" => 'bar']
            )
        );
    }

    /**
     * Test for getDeleteDataOrTablelink
     *
     * @return void
     */
    public function testGetDeleteDataOrTablelink()
    {

        $this->assertRegExp(
            '/.*TRUNCATE.TABLE.foo.*id_truncate.*Truncate table.*/m',
            $this->operations->getDeleteDataOrTablelink(
                ["sql" => 'TRUNCATE TABLE foo'],
                "TRUNCATE_TABLE",
                "Truncate table",
                "id_truncate"
            )
        );
    }

    /**
     * Test for getHtmlForPartitionMaintenance
     *
     * @return void
     */
    public function testGetHtmlForPartitionMaintenance()
    {
        $html = $this->operations->getHtmlForPartitionMaintenance(
            [
                "partition1",
                "partion2",
            ],
            [
                "param1" => 'foo',
                "param2" => 'bar',
            ]
        );
        $this->assertRegExp('/.*action="tbl_operations.php".*/', $html);
        $this->assertRegExp('/.*ANALYZE.*/', $html);
        $this->assertRegExp('/.*REBUILD.*/', $html);
    }

    /**
     * Test for getHtmlForReferentialIntegrityCheck
     *
     * @return void
     */
    public function testGetHtmlForReferentialIntegrityCheck()
    {
        $GLOBALS['cfg']['blowfish_secret'] = '';
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');

        $this->assertRegExp(
            '/.*Check referential integrity.*href="sql.php(.|[\n])*/m',
            $this->operations->getHtmlForReferentialIntegrityCheck(
                [
                    [
                        'foreign_db'    => 'db1',
                        'foreign_table' => "foreign1",
                        'foreign_field' => "foreign2",
                    ],
                ],
                [
                    "param1" => 'a',
                    "param2" => 'b',
                ]
            )
        );
    }
}
