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

    public function testGetColumnCreationQuery(): void
    {
        $_POST['db'] = '2fa';
        $_POST['field_where'] = 'after';
        $_POST['after_field'] = 'd';
        $_POST['table'] = 'aes';
        $_POST['orig_num_fields'] = '1';
        $_POST['orig_field_where'] = 'after';
        $_POST['orig_after_field'] = 'd';
        $_POST['primary_indexes'] = '[]';
        $_POST['unique_indexes'] = '[]';
        $_POST['indexes'] = '[]';
        $_POST['fulltext_indexes'] = '[]';
        $_POST['spatial_indexes'] = '[]';
        $_POST['field_name'] = ['dd'];
        $_POST['field_type'] = ['INT'];
        $_POST['field_length'] = [''];
        $_POST['field_default_type'] = ['NONE'];
        $_POST['field_default_value'] = [''];
        $_POST['field_collation'] = [''];
        $_POST['field_attribute'] = [''];
        $_POST['field_key'] = ['none_0'];
        $_POST['field_comments'] = [''];
        $_POST['field_virtuality'] = [''];
        $_POST['field_expression'] = [''];
        $_POST['field_move_to'] = [''];
        $_POST['field_mimetype'] = [''];
        $_POST['field_transformation'] = [''];
        $_POST['field_transformation_options'] = [''];
        $_POST['field_input_transformation'] = [''];
        $_POST['field_input_transformation_options'] = [''];
        $_POST['do_save_data'] = '1';
        $_POST['preview_sql'] = '1';
        $_POST['ajax_request'] = '1';
        $sqlQuery = $this->createAddField->getColumnCreationQuery('my_table');
        $this->assertEquals(
            'ALTER TABLE `my_table`  ADD `dd` INT NOT NULL  AFTER `d`;',
            $sqlQuery
        );
        $_POST['online_transaction'] = 'ONLINE_TRANSACTION_ENABLED';
        $sqlQuery = $this->createAddField->getColumnCreationQuery('my_table');
        $this->assertEquals(
            'ALTER TABLE `my_table`  ADD `dd` INT NOT NULL  AFTER `d`, ALGORITHM=INPLACE, LOCK=NONE;',
            $sqlQuery
        );
    }
}
