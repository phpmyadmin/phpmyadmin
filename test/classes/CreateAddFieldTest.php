<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\CreateAddField;

use function json_encode;

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
            [
                ' PARTITION BY LIST (EXPR2()) PARTITIONS 2 ( PARTITION p0,  PARTITION p1)',
                [
                    'partition_by' => 'LIST',
                    'partition_count' => '2',
                    'subpartition_by' => 'HASH',
                    'partition_expr' => 'EXPR2()',
                    'partitions' => [
                        [
                            'name' => 'p0',
                            'value_type' => '',
                            'value' => '',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        ],
                        [
                            'name' => 'p1',
                            'value_type' => '',
                            'value' => '',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        ],
                    ],
                ],
            ],
            [
                ' PARTITION BY LIST (EXPR2()) PARTITIONS 2 SUBPARTITION BY HASH (EXPR1()) '
                . 'SUBPARTITIONS 2 ( PARTITION p0,  PARTITION p1,  PARTITION p2 VALUES <value type> (<value>))',
                [
                    'partition_by' => 'LIST',
                    'partition_count' => '2',
                    'subpartition_by' => 'HASH',
                    'subpartition_expr' => 'EXPR1()',
                    'subpartition_count' => '2',
                    'partition_expr' => 'EXPR2()',
                    'partitions' => [
                        [
                            'name' => 'p0',
                            'value_type' => '',
                            'value' => '',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        ],
                        [
                            'name' => 'p1',
                            'value_type' => '',
                            'value' => '',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        ],
                        [
                            'name' => 'p2',
                            'value_type' => '<value type>',
                            'value' => '<value>',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        ],
                    ],
                ],
            ],
            [
                ' PARTITION BY LIST (EXPR2()) PARTITIONS 2 SUBPARTITION BY HASH (EXPR1()) '
                . 'SUBPARTITIONS 2 ( PARTITION p0 ENGINE = MRG_MyISAM COMMENT = \'Partition zero\' '
                . 'MAX_ROWS = 2048 MIN_ROWS = 25,  PARTITION p1 VALUES LESS THAN MAXVALUE, '
                . ' PARTITION p2 ( SUBPARTITION p2_s1 DATA DIRECTORY = \'datadir\' INDEX_DIRECTORY = \'indexdir\' '
                . 'TABLESPACE = space1 NODEGROUP = ngroup1))',
                [
                    'partition_by' => 'LIST',
                    'subpartition_by' => 'HASH',
                    'subpartition_expr' => 'EXPR1()',
                    'subpartition_count' => '2',
                    'partition_expr' => 'EXPR2()',
                    'partition_count' => '2',
                    'partitions' => [
                        [
                            'name' => 'p0',
                            'value_type' => '',
                            'value' => '',
                            'engine' => 'MRG_MyISAM',
                            'comment' => 'Partition zero',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '2048',
                            'min_rows' => '25',
                            'tablespace' => '',
                            'node_group' => '',
                        ],
                        [
                            'name' => 'p1',
                            'value_type' => 'LESS THAN MAXVALUE',
                            'value' => '',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                        ],
                        [
                            'name' => 'p2',
                            'value_type' => '',
                            'value' => '',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                            'subpartitions' => [
                                [
                                    'name' => 'p2_s1',
                                    'value_type' => '',
                                    'value' => '1',
                                    'engine' => '',
                                    'comment' => '',
                                    'data_directory' => 'datadir',
                                    'index_directory' => 'indexdir',
                                    'max_rows' => '',
                                    'min_rows' => '',
                                    'tablespace' => 'space1',
                                    'node_group' => 'ngroup1',
                                ],
                            ],
                        ],
                    ],
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

    /**
     * Data provider for testGetColumnCreationQuery
     *
     * @return array[]
     */
    public function providerGetColumnCreationQueryRequest(): array
    {
        return [
            [
                'ALTER TABLE `my_table`  ADD `dd` INT NOT NULL  AFTER `d`;',
                [
                    'db' => '2fa',
                    'field_where' => 'after',
                    'after_field' => 'd',
                    'table' => 'aes',
                    'orig_num_fields' => '1',
                    'orig_field_where' => 'after',
                    'orig_after_field' => 'd',
                    'primary_indexes' => '[]',
                    'unique_indexes' => '[]',
                    'indexes' => '[]',
                    'fulltext_indexes' => '[]',
                    'spatial_indexes' => '[]',
                    'field_name' => ['dd'],
                    'field_type' => ['INT'],
                    'field_length' => [''],
                    'field_default_type' => ['NONE'],
                    'field_default_value' => [''],
                    'field_collation' => [''],
                    'field_attribute' => [''],
                    'field_key' => ['none_0'],
                    'field_comments' => [''],
                    'field_virtuality' => [''],
                    'field_expression' => [''],
                    'field_move_to' => [''],
                    'field_mimetype' => [''],
                    'field_transformation' => [''],
                    'field_transformation_options' => [''],
                    'field_input_transformation' => [''],
                    'field_input_transformation_options' => [''],
                    'do_save_data' => '1',
                    'preview_sql' => '1',
                    'ajax_request' => '1',
                ],
            ],
            [
                'ALTER TABLE `my_table`  ADD `dd` INT NOT NULL  AFTER `d`, ALGORITHM=INPLACE, LOCK=NONE;',
                [
                    'db' => '2fa',
                    'field_where' => 'after',
                    'after_field' => 'd',
                    'table' => 'aes',
                    'orig_num_fields' => '1',
                    'orig_field_where' => 'after',
                    'orig_after_field' => 'd',
                    'primary_indexes' => '[]',
                    'unique_indexes' => '[]',
                    'indexes' => '[]',
                    'fulltext_indexes' => '[]',
                    'spatial_indexes' => '[]',
                    'field_name' => ['dd'],
                    'field_type' => ['INT'],
                    'field_length' => [''],
                    'field_default_type' => ['NONE'],
                    'field_default_value' => [''],
                    'field_collation' => [''],
                    'field_attribute' => [''],
                    'field_key' => ['none_0'],
                    'field_comments' => [''],
                    'field_virtuality' => [''],
                    'field_expression' => [''],
                    'field_move_to' => [''],
                    'field_mimetype' => [''],
                    'field_transformation' => [''],
                    'field_transformation_options' => [''],
                    'field_input_transformation' => [''],
                    'field_input_transformation_options' => [''],
                    'do_save_data' => '1',
                    'preview_sql' => '1',
                    'ajax_request' => '1',
                    'online_transaction' => 'ONLINE_TRANSACTION_ENABLED',
                ],
            ],
            [
                'ALTER TABLE `my_table`  ADD `dd` INT NOT NULL  AFTER `d`, '
                . ' ADD   UNIQUE  `un1` (`dd`(12), `dd`),  ADD   UNIQUE  `un3` (`dd`(12)) USING '
                . 'BTREE COMMENT \'Unique 3\',  ADD   UNIQUE  `un3.1` (`dd`(12)) WITH '
                . 'PARSER Parser 1 COMMENT \'Unique 3.1\',  ADD   UNIQUE  `un2` (`dd`(12)) '
                . 'KEY_BLOCK_SIZE = 32 USING BTREE COMMENT \'Unique 2\', ALGORITHM=INPLACE, LOCK=NONE;',
                [
                    'db' => '2fa',
                    'field_where' => 'after',
                    'after_field' => 'd',
                    'table' => 'aes',
                    'orig_num_fields' => '1',
                    'orig_field_where' => 'after',
                    'orig_after_field' => 'd',
                    'primary_indexes' => json_encode([]),
                    'unique_indexes' => json_encode([
                        [
                            'Key_name' => 'un1',
                            'Index_comment' => '',
                            'Index_choice' => 'UNIQUE',
                            'Key_block_size' => '',
                            'Parser' => '',
                            'Index_type' => '',
                            'columns' => [
                                [
                                    'col_index' => '0',
                                    'size' => '12',
                                ],
                                [
                                    'col_index' => '0',
                                    'size' => '',
                                ],
                            ],
                        ],
                        [
                            'Key_name' => 'un3',
                            'Index_comment' => 'Unique 3',
                            'Index_choice' => 'UNIQUE',
                            'Key_block_size' => '',
                            'Parser' => 'Parser 1',
                            'Index_type' => 'BTREE',
                            'columns' => [
                                [
                                    'col_index' => '0',
                                    'size' => '12',
                                ],
                            ],
                        ],
                        [
                            'Key_name' => 'un3.1',
                            'Index_comment' => 'Unique 3.1',
                            'Index_choice' => 'FULLTEXT',
                            'Key_block_size' => '',
                            'Parser' => 'Parser 1',
                            'Index_type' => 'BTREE',
                            'columns' => [
                                [
                                    'col_index' => '0',
                                    'size' => '12',
                                ],
                            ],
                        ],
                        [
                            'Key_name' => 'un2',
                            'Index_comment' => 'Unique 2',
                            'Index_choice' => 'UNIQUE',
                            'Key_block_size' => '32',
                            'Parser' => '',
                            'Index_type' => 'BTREE',
                            'columns' => [
                                [
                                    'col_index' => '0',
                                    'size' => '12',
                                ],
                            ],
                        ],
                    ]),
                    'indexes' => json_encode([]),
                    'fulltext_indexes' => json_encode([]),
                    'spatial_indexes' => json_encode([]),
                    'field_name' => ['dd'],
                    'field_type' => ['INT'],
                    'field_length' => [''],
                    'field_default_type' => ['NONE'],
                    'field_default_value' => [''],
                    'field_collation' => [''],
                    'field_attribute' => [''],
                    'field_key' => ['none_0'],
                    'field_comments' => [''],
                    'field_virtuality' => [''],
                    'field_expression' => [''],
                    'field_move_to' => [''],
                    'field_mimetype' => [''],
                    'field_transformation' => [''],
                    'field_transformation_options' => [''],
                    'field_input_transformation' => [''],
                    'field_input_transformation_options' => [''],
                    'do_save_data' => '1',
                    'preview_sql' => '1',
                    'ajax_request' => '1',
                    'online_transaction' => 'ONLINE_TRANSACTION_ENABLED',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerGetColumnCreationQueryRequest
     */
    public function testGetColumnCreationQuery(string $expected, array $request): void
    {
        $_POST = $request;
        $sqlQuery = $this->createAddField->getColumnCreationQuery('my_table');
        $this->assertEquals(
            $expected,
            $sqlQuery
        );
    }
}
