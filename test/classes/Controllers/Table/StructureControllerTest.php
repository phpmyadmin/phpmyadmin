<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Index;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

/**
 * @covers \PhpMyAdmin\Controllers\Table\StructureController
 */
class StructureControllerTest extends AbstractTestCase
{
    public function testStructureController(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server'] = $GLOBALS['config']->defaultServer;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['cfg']['ShowStats'] = false;
        $GLOBALS['cfg']['ShowPropertyComments'] = false;
        $_SESSION['relation'] = [];

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult(
            'SHOW COLLATION',
            [
                ['utf8mb4_general_ci', 'utf8mb4', '45', 'Yes', 'Yes', '1'],
                ['armscii8_general_ci', 'armscii8', '32', 'Yes', 'Yes', '1'],
                ['utf8_general_ci', 'utf8', '33', 'Yes', 'Yes', '1'],
                ['utf8_bin', 'utf8', '83', '', 'Yes', '1'],
                ['latin1_swedish_ci', 'latin1', '8', 'Yes', 'Yes', '1'],
            ],
            ['Collation', 'Charset', 'Id', 'Default', 'Compiled', 'Sortlen']
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->dummyDbi->addResult(
            'SELECT * FROM `information_schema`.`PARTITIONS` WHERE `TABLE_SCHEMA` = \'test_db\' AND `TABLE_NAME` = \'test_table\'',
            [
                ['def', 'test_db', 'test_table', null, null, null, null, null, null, null, null, null, '3', '5461', '16384', null, '0', '0', '2022-02-21 13:34:11', null, null, null, '', '', null],
            ],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'PARTITION_NAME', 'SUBPARTITION_NAME', 'PARTITION_ORDINAL_POSITION', 'SUBPARTITION_ORDINAL_POSITION', 'PARTITION_METHOD', 'SUBPARTITION_METHOD', 'PARTITION_EXPRESSION', 'SUBPARTITION_EXPRESSION', 'PARTITION_DESCRIPTION', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'CHECKSUM', 'PARTITION_COMMENT', 'NODEGROUP', 'TABLESPACE_NAME']
        );
        $this->dummyDbi->addResult(
            'SELECT DISTINCT `PARTITION_NAME` FROM `information_schema`.`PARTITIONS` WHERE `TABLE_SCHEMA` = \'test_db\' AND `TABLE_NAME` = \'test_table\'',
            [[null]],
            ['PARTITION_NAME']
        );
        // phpcs:enable

        $pageSettings = new PageSettings('TableStructure');
        $fields = $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table'], true);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getRoute')->willReturn('/table/structure');

        $response = new ResponseRenderer();
        $relation = new Relation($this->dbi);
        $template = new Template();
        (new StructureController(
            $response,
            $template,
            $relation,
            new Transformations(),
            new CreateAddField($this->dbi),
            new RelationCleanup($this->dbi, $relation),
            $this->dbi,
            new FlashMessages()
        ))($request);

        $expected = $pageSettings->getHTML();
        $expected .= $template->render('table/structure/display_structure', [
            'collations' => [
                'utf8mb4_general_ci' => [
                    'name' => 'utf8mb4_general_ci',
                    'description' => 'Unicode (UCA 4.0.0), case-insensitive',
                ],
            ],
            'is_foreign_key_supported' => true,
            'indexes' => Index::getFromTable($GLOBALS['table'], $GLOBALS['db']),
            'indexes_duplicates' => Index::findDuplicates($GLOBALS['table'], $GLOBALS['db']),
            'relation_parameters' => $relation->getRelationParameters(),
            'hide_structure_actions' => true,
            'db' => 'test_db',
            'table' => 'test_table',
            'db_is_system_schema' => false,
            'tbl_is_view' => false,
            'mime_map' => [],
            'tbl_storage_engine' => 'INNODB',
            'primary' => Index::getPrimary($GLOBALS['table'], $GLOBALS['db']),
            'columns_with_unique_index' => [],
            'columns_list' => ['id', 'name', 'datetimefield'],
            'table_stats' => null,
            'fields' => $fields,
            'extracted_columnspecs' => [
                1 => Util::extractColumnSpec((string) $fields['id']['Type']),
                2 => Util::extractColumnSpec((string) $fields['name']['Type']),
                3 => Util::extractColumnSpec((string) $fields['datetimefield']['Type']),
            ],
            'columns_with_index' => [],
            'central_list' => [],
            'comments_map' => [],
            'browse_mime' => true,
            'show_column_comments' => true,
            'show_stats' => false,
            'mysql_int_version' => $this->dbi->getVersion(),
            'is_mariadb' => $this->dbi->isMariaDB(),
            'text_dir' => 'ltr',
            'is_active' => false,
            'have_partitioning' => true,
            'partitions' => [],
            'partition_names' => [0 => null],
            'default_sliders_state' => 'closed',
            'attributes' => [1 => ' ', 2 => ' ', 3 => ' '],
            'displayed_fields' => [
                1 => [
                    'text' => 'id',
                    'icon' => '<img src="themes/dot.gif" title="Primary" alt="Primary" class="icon ic_b_primary">',
                ],
                2 => ['text' => 'name', 'icon' => ''],
                3 => ['text' => 'datetimefield', 'icon' => ''],
            ],
            'row_comments' => [1 => '', 2 => '', 3 => ''],
            'route' => '/table/structure',
        ]);

        $this->assertSame($expected, $response->getHTMLResult());
    }
}
