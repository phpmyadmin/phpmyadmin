<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;

use function array_merge;

use const MYSQLI_TYPE_STRING;

/**
 * @covers \PhpMyAdmin\Table\ColumnsDefinition
 */
class ColumnsDefinitionTest extends AbstractTestCase
{
    public function testDisplayForm(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        // phpcs:disable Generic.Files.LineLength.TooLong
        $columnMeta = ['Field' => 'actor_id', 'Type' => 'smallint(5) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => 'auto_increment', 'Privileges' => 'select,insert,update,references', 'Comment' => ''];
        $dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `sakila`.`actor`',
            [
                ['actor_id', 'smallint(5) unsigned', null, 'NO', 'PRI', null, 'auto_increment', 'select,insert,update,references', ''],
                ['first_name', 'varchar(45)', 'utf8mb4_general_ci', 'NO', '', null, '', 'select,insert,update,references', ''],
                ['last_name', 'varchar(45)', 'utf8mb4_general_ci', 'NO', 'MUL', null, '', 'select,insert,update,references', ''],
                ['last_update', 'timestamp', null, 'NO', '', 'current_timestamp()', 'on update current_timestamp()', 'select,insert,update,references', ''],
            ],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment']
        );
        $dummyDbi->addResult(
            'SHOW INDEXES FROM `sakila`.`actor`',
            [
                ['actor', '0', 'PRIMARY', '1', 'actor_id', 'A', '2', null, null, '', 'BTREE', '', '', 'NO'],
                ['actor', '1', 'idx_actor_last_name', '1', 'last_name', 'A', '2', null, null, '', 'BTREE', '', '', 'NO'],
            ],
            ['Table', 'Non_unique', 'Key_name', 'Seq_in_index', 'Column_name', 'Collation', 'Cardinality', 'Sub_part', 'Packed', 'Null', 'Index_type', 'Comment', 'Index_comment', 'Ignored']
        );
        // phpcs:enable
        $dummyDbi->addResult(
            'SELECT * FROM `sakila`.`actor` LIMIT 1',
            [['1', 'PENELOPE', 'GUINESS', '2006-02-15 04:34:33']],
            ['actor_id', 'first_name', 'last_name', 'last_update']
        );
        $createTable = <<<'SQL'
CREATE TABLE `actor` (
  `actor_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(45) NOT NULL,
  `last_name` varchar(45) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`actor_id`),
  KEY `idx_actor_last_name` (`last_name`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4
SQL;
        $dummyDbi->addResult(
            'SHOW CREATE TABLE `sakila`.`actor`',
            [['actor', $createTable]],
            ['actor_id', 'first_name', 'last_name', 'last_update']
        );

        $relation = new Relation($dbi);
        $columnsDefinition = new ColumnsDefinition($dbi, $relation, new Transformations());

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'sakila';
        $GLOBALS['table'] = 'actor';
        $GLOBALS['col_priv'] = true;
        $GLOBALS['is_reload_priv'] = true;
        $GLOBALS['mime_map'] = null;
        $_SESSION['relation'] = [];

        $actual = $columnsDefinition->displayForm('/table/structure/save', 1, null, ['actor_id'], [$columnMeta]);

        $contentCell = [
            'column_number' => 0,
            'column_meta' => array_merge($columnMeta, ['DefaultType' => 'NONE', 'DefaultValue' => '']),
            'type_upper' => 'SMALLINT',
            'default_value' => '',
            'length_values_input_size' => 8,
            'length' => '5',
            'extracted_columnspec' => [
                'type' => 'smallint',
                'spec_in_brackets' => '5',
                'enum_set_values' => [],
                'print_type' => 'smallint(5)',
                'binary' => false,
                'unsigned' => true,
                'zerofill' => false,
                'attribute' => 'UNSIGNED',
                'can_contain_collation' => false,
                'displayed_type' => 'smallint(5)',
            ],
            'submit_attribute' => null,
            'comments_map' => [],
            'fields_meta' => [$columnMeta],
            'is_backup' => true,
            'move_columns' => [
                new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) ['name' => 'actor_id']),
                new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) ['name' => 'first_name']),
                new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) ['name' => 'last_name']),
                new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) ['name' => 'last_update']),
            ],
            'available_mime' => [],
            'mime_map' => [],
        ];
        $expected = [
            'is_backup' => true,
            'fields_meta' => [$columnMeta],
            'relation_parameters' => $relation->getRelationParameters(),
            'action' => '/table/structure/save',
            'form_params' => [
                'db' => 'sakila',
                'table' => 'actor',
                'orig_num_fields' => 1,
                'orig_field_where' => null,
                'orig_after_field' => null,
                'selected[0]' => 'actor_id',
                'field_orig[0]' => 'actor_id',
                'field_type_orig[0]' => 'SMALLINT',
                'field_length_orig[0]' => '5',
                'field_default_value_orig[0]' => '',
                'field_default_type_orig[0]' => 'NONE',
                'field_collation_orig[0]' => '',
                'field_attribute_orig[0]' => 'UNSIGNED',
                'field_null_orig[0]' => 'NO',
                'field_extra_orig[0]' => 'auto_increment',
                'field_comments_orig[0]' => '',
                'field_virtuality_orig[0]' => '',
                'field_expression_orig[0]' => '',
            ],
            'content_cells' => [$contentCell],
            'partition_details' => [
                'partition_by' => null,
                'partition_expr' => null,
                'subpartition_by' => null,
                'subpartition_expr' => null,
                'partition_count' => 0,
                'subpartition_count' => 0,
                'can_have_subpartitions' => false,
                'value_enabled' => false,
            ],
            'primary_indexes' => null,
            'unique_indexes' => null,
            'indexes' => null,
            'fulltext_indexes' => null,
            'spatial_indexes' => null,
            'table' => null,
            'comment' => null,
            'tbl_collation' => null,
            'charsets' => [
                [
                    'name' => 'armscii8',
                    'description' => 'armscii8_general_ci',
                    'collations' => [['name' => 'armscii8_general_ci', 'description' => 'Armenian, case-insensitive']],
                ],
                [
                    'name' => 'latin1',
                    'description' => 'cp1252 West European',
                    'collations' => [['name' => 'latin1_swedish_ci', 'description' => 'Swedish, case-insensitive']],
                ],
                [
                    'name' => 'utf8',
                    'description' => 'UTF-8 Unicode',
                    'collations' => [
                        ['name' => 'utf8_bin', 'description' => 'Unicode, binary'],
                        ['name' => 'utf8_general_ci', 'description' => 'Unicode, case-insensitive'],
                    ],
                ],
                [
                    'name' => 'utf8mb4',
                    'description' => 'utf8mb4_0900_ai_ci',
                    'collations' => [
                        ['name' => 'utf8mb4_general_ci', 'description' => 'Unicode (UCA 4.0.0), case-insensitive'],
                    ],
                ],
            ],
            'tbl_storage_engine' => null,
            'storage_engines' => ['dummy' => ['name' => 'dummy', 'comment' => 'dummy comment', 'is_default' => false]],
            'connection' => null,
            'change_column' => null,
            'is_virtual_columns_supported' => true,
            'is_integers_length_restricted' => false,
            'browse_mime' => true,
            'supports_stored_keyword' => false,
            'server_version' => $dbi->getVersion(),
            'max_rows' => 25,
            'char_editing' => 'input',
            'attribute_types' => ['', 'BINARY', 'UNSIGNED', 'UNSIGNED ZEROFILL', 'on update CURRENT_TIMESTAMP'],
            'privs_available' => true,
            'max_length' => 1024,
            'have_partitioning' => true,
            'disable_is' => false,
        ];

        $this->assertEquals($expected, $actual);
    }
}
