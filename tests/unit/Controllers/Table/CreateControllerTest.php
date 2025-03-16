<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\CreateController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;

use function array_merge;

#[CoversClass(CreateController::class)]
class CreateControllerTest extends AbstractTestCase
{
    public function testCreateController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'new_test_table';
        $config = Config::getInstance();
        $config->selectedServer = $config->getSettings()->Servers[1]->asArray();
        $_POST = ['db' => 'test_db', 'table' => 'new_test_table'];

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'test_db\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'new_test_table\''
                . ' ORDER BY `ORDINAL_POSITION`',
            false,
        );
        $dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'test_db\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'new_test_table\''
                . ' ORDER BY `ORDINAL_POSITION`',
            false,
        );
        $dummyDbi->addResult('SHOW CREATE TABLE `test_db`.`new_test_table`', false);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $contentCell = [
            'column_number' => 0,
            'column_meta' => ['Type' => ''],
            'type_upper' => '',
            'default_value' => '',
            'length_values_input_size' => 8,
            'length' => '',
            'extracted_columnspec' => [],
            'submit_attribute' => null,
            'comments_map' => [],
            'fields_meta' => null,
            'is_backup' => false,
            'move_columns' => [],
            'available_mime' => [
                'mimetype' => [
                    'Image/JPEG' => 'Image/JPEG',
                    'Text/Plain' => 'Text/Plain',
                    'Application/Octetstream' => 'Application/Octetstream',
                    'Image/PNG' => 'Image/PNG',
                    'Text/Octetstream' => 'Text/Octetstream',
                ],
                'input_transformation' => [
                    'Image/JPEG: Upload',
                    'Text/Plain: FileUpload',
                    'Text/Plain: Iptobinary',
                    'Text/Plain: Iptolong',
                    'Text/Plain: JsonEditor',
                    'Text/Plain: RegexValidation',
                    'Text/Plain: SqlEditor',
                    'Text/Plain: XmlEditor',
                    'Text/Plain: Link',
                    'Text/Plain: Longtoipv4',
                    'Text/Plain: PreApPend',
                    'Text/Plain: Substring',
                ],
                'input_transformation_file' => [
                    'Input/Image_JPEG_Upload.php',
                    'Input/Text_Plain_FileUpload.php',
                    'Input/Text_Plain_Iptobinary.php',
                    'Input/Text_Plain_Iptolong.php',
                    'Input/Text_Plain_JsonEditor.php',
                    'Input/Text_Plain_RegexValidation.php',
                    'Input/Text_Plain_SqlEditor.php',
                    'Input/Text_Plain_XmlEditor.php',
                    'Text_Plain_Link.php',
                    'Text_Plain_Longtoipv4.php',
                    'Text_Plain_PreApPend.php',
                    'Text_Plain_Substring.php',
                ],
                'transformation' => [
                    'Application/Octetstream: Download',
                    'Application/Octetstream: Hex',
                    'Image/JPEG: Inline',
                    'Image/JPEG: Link',
                    'Image/PNG: Inline',
                    'Text/Octetstream: Sql',
                    'Text/Plain: Binarytoip',
                    'Text/Plain: Bool2Text',
                    'Text/Plain: Dateformat',
                    'Text/Plain: External',
                    'Text/Plain: Formatted',
                    'Text/Plain: Imagelink',
                    'Text/Plain: Json',
                    'Text/Plain: Sql',
                    'Text/Plain: Xml',
                    'Text/Plain: Link',
                    'Text/Plain: Longtoipv4',
                    'Text/Plain: PreApPend',
                    'Text/Plain: Substring',
                ],
                'transformation_file' => [
                    'Output/Application_Octetstream_Download.php',
                    'Output/Application_Octetstream_Hex.php',
                    'Output/Image_JPEG_Inline.php',
                    'Output/Image_JPEG_Link.php',
                    'Output/Image_PNG_Inline.php',
                    'Output/Text_Octetstream_Sql.php',
                    'Output/Text_Plain_Binarytoip.php',
                    'Output/Text_Plain_Bool2Text.php',
                    'Output/Text_Plain_Dateformat.php',
                    'Output/Text_Plain_External.php',
                    'Output/Text_Plain_Formatted.php',
                    'Output/Text_Plain_Imagelink.php',
                    'Output/Text_Plain_Json.php',
                    'Output/Text_Plain_Sql.php',
                    'Output/Text_Plain_Xml.php',
                    'Text_Plain_Link.php',
                    'Text_Plain_Longtoipv4.php',
                    'Text_Plain_PreApPend.php',
                    'Text_Plain_Substring.php',
                ],
                'input_transformation_file_quoted' => [
                    'Input/Image_JPEG_Upload\\.php',
                    'Input/Text_Plain_FileUpload\\.php',
                    'Input/Text_Plain_Iptobinary\\.php',
                    'Input/Text_Plain_Iptolong\\.php',
                    'Input/Text_Plain_JsonEditor\\.php',
                    'Input/Text_Plain_RegexValidation\\.php',
                    'Input/Text_Plain_SqlEditor\\.php',
                    'Input/Text_Plain_XmlEditor\\.php',
                    'Text_Plain_Link\\.php',
                    'Text_Plain_Longtoipv4\\.php',
                    'Text_Plain_PreApPend\\.php',
                    'Text_Plain_Substring\\.php',
                ],
                'transformation_file_quoted' => [
                    'Output/Application_Octetstream_Download\\.php',
                    'Output/Application_Octetstream_Hex\\.php',
                    'Output/Image_JPEG_Inline\\.php',
                    'Output/Image_JPEG_Link\\.php',
                    'Output/Image_PNG_Inline\\.php',
                    'Output/Text_Octetstream_Sql\\.php',
                    'Output/Text_Plain_Binarytoip\\.php',
                    'Output/Text_Plain_Bool2Text\\.php',
                    'Output/Text_Plain_Dateformat\\.php',
                    'Output/Text_Plain_External\\.php',
                    'Output/Text_Plain_Formatted\\.php',
                    'Output/Text_Plain_Imagelink\\.php',
                    'Output/Text_Plain_Json\\.php',
                    'Output/Text_Plain_Sql\\.php',
                    'Output/Text_Plain_Xml\\.php',
                    'Text_Plain_Link\\.php',
                    'Text_Plain_Longtoipv4\\.php',
                    'Text_Plain_PreApPend\\.php',
                    'Text_Plain_Substring\\.php',
                ],
            ],
            'mime_map' => [],
        ];

        $relation = new Relation($dbi);
        $response = new ResponseRenderer();
        $template = new Template();
        $expected = $template->render('columns_definitions/column_definitions_form', [
            'is_backup' => false,
            'fields_meta' => null,
            'relation_parameters' => $relation->getRelationParameters(),
            'action' => '/table/create',
            'form_params' => [
                'db' => 'test_db',
                'reload' => 1,
                'orig_num_fields' => 2,
                'orig_field_where' => null,
                'orig_after_field' => null,
            ],
            'content_cells' => [$contentCell, array_merge($contentCell, ['column_number' => 1])],
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
            'table' => 'new_test_table',
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
            'supports_stored_keyword' => true,
            'server_version' => $dbi->getVersion(),
            'max_rows' => 25,
            'char_editing' => 'input',
            'attribute_types' => ['', 'BINARY', 'UNSIGNED', 'UNSIGNED ZEROFILL', 'on update CURRENT_TIMESTAMP'],
            'privs_available' => false,
            'max_length' => 1024,
            'have_partitioning' => true,
            'dbi' => $dbi,
            'disable_is' => false,
        ]);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['num_fields' => '2']);

        $transformations = new Transformations();
        (new CreateController(
            $response,
            $transformations,
            $this->createConfig(),
            $dbi,
            new ColumnsDefinition($dbi, $relation, $transformations),
            new UserPrivilegesFactory($dbi),
        ))($request);

        self::assertSame($expected, $response->getHTMLResult());
    }
}
