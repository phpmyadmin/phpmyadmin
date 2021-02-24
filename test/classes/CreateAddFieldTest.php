<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\CreateAddField;

/**
 * This class is for testing PhpMyAdmin\CreateAddField methods
 */
class CreateAddFieldTest extends AbstractTestCase
{
    /** @var CreateAddField */
    private $createAddField;

    /**
     * Set up for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAddField = new CreateAddField($GLOBALS['dbi']);
    }

    /**
     * Test for getPartitionsDefinition
     *
     * @param string $expected Expected result
     * @param array  $request  $_REQUEST array
     *
     * @dataProvider providerGetPartitionsDefinition
     */
    public function testGetPartitionsDefinition(string $expected, array $request): void
    {
        $_POST = $request;
        $actual = $this->createAddField->getPartitionsDefinition();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for testGetPartitionsDefinition
     *
     * @return array
     */
    public function providerGetPartitionsDefinition(): array
    {
        return [
            [
                '',
                [],
            ],
            [
                ' PARTITION BY HASH (EXPR()) PARTITIONS 2',
                [
                    'partition_by' => 'HASH',
                    'partition_expr' => 'EXPR()',
                    'partition_count' => '2',
                ],
            ],
        ];
    }

    /**
     * Test for getTableCreationQuery
     *
     * @param string $expected Expected result
     * @param string $db       Database name
     * @param string $table    Table name
     * @param array  $request  $_REQUEST array
     *
     * @dataProvider providerGetTableCreationQuery
     */
    public function testGetTableCreationQuery(string $expected, string $db, string $table, array $request): void
    {
        $_POST = $request;
        $actual = $this->createAddField->getTableCreationQuery($db, $table);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for testGetTableCreationQuery
     *
     * @return array
     */
    public function providerGetTableCreationQuery(): array
    {
        return [
            [
                'CREATE TABLE `db`.`table` ();',
                'db',
                'table',
                [
                    'field_name' => [],
                    'primary_indexes' => '{}',
                    'indexes' => '{}',
                    'unique_indexes' => '{}',
                    'fulltext_indexes' => '{}',
                    'spatial_indexes' => '{}',
                ],
            ],
            [
                'CREATE TABLE `db`.`table` () ENGINE = Inno\\\'DB CHARSET=armscii8 COMMENT = \'my \\\'table\';',
                'db',
                'table',
                [
                    'field_name' => [],
                    'primary_indexes' => '{}',
                    'indexes' => '{}',
                    'unique_indexes' => '{}',
                    'fulltext_indexes' => '{}',
                    'spatial_indexes' => '{}',
                    'tbl_storage_engine' => 'Inno\'DB',
                    'tbl_collation' => 'armscii8',
                    'connection' => 'aaaa',
                    'comment' => 'my \'table',
                ],
            ],
        ];
    }

    /**
     * Test for getNumberOfFieldsFromRequest
     *
     * @param int   $expected Expected result
     * @param array $request  $_REQUEST array
     *
     * @dataProvider providerGetNumberOfFieldsFromRequest
     */
    public function testGetNumberOfFieldsFromRequest(int $expected, array $request): void
    {
        $_POST = $request;
        $actual = $this->createAddField->getNumberOfFieldsFromRequest();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for testGetNumberOfFieldsFromRequest
     *
     * @return array
     */
    public function providerGetNumberOfFieldsFromRequest(): array
    {
        return [
            [
                4,
                [],
            ],
        ];
    }
}
