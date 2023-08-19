<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Util;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Options::class)]
class OptionsTest extends AbstractTestCase
{
    private Options $export;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadContainerBuilder();

        parent::setLanguage();

        parent::setGlobalConfig();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        parent::loadDbiIntoContainerBuilder();

        $GLOBALS['server'] = 0;

        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'PMA';

        $this->export = new Options(
            new Relation($dbi),
            new TemplateModel($dbi),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Config::$instance = null;
    }

    public function testGetOptions(): void
    {
        $GLOBALS['cfg']['Export']['method'] = 'XML';
        $GLOBALS['cfg']['SaveDir'] = '/tmp';
        $GLOBALS['cfg']['ZipDump'] = false;
        $GLOBALS['cfg']['GZipDump'] = false;

        $exportType = 'server';
        $db = 'PMA';
        $table = 'PMA_test';
        $numTablesStr = '10';
        $unlimNumRowsStr = 'unlim_num_rows_str';
        //$single_table = "single_table";
        DatabaseInterface::getInstance()->getCache()->cacheTableContent([$db, $table, 'ENGINE'], 'MERGE');

        $columnsInfo = [
            'test_column1' => ['COLUMN_NAME' => 'test_column1'],
            'test_column2' => ['COLUMN_NAME' => 'test_column2'],
        ];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->willReturn($columnsInfo);
        $dbi->expects($this->any())->method('getCompatibilities')
            ->willReturn([]);

        DatabaseInterface::$instance = $dbi;

        $exportList = Plugins::getExport($exportType, true);
        $dropdown = Plugins::getChoice($exportList, 'sql');

        $config = Config::getInstance();
        $config->selectedServer['host'] = 'localhost';
        $config->selectedServer['user'] = 'pma_user';
        $_POST['filename_template'] = 'user value for test';

        //Call the test function
        $actual = $this->export->getOptions($exportType, $db, $table, '', $numTablesStr, $unlimNumRowsStr, $exportList);

        $expected = [
            'export_type' => $exportType,
            'db' => $db,
            'table' => $table,
            'templates' => ['is_enabled' => '', 'templates' => [], 'selected' => null],
            'sql_query' => '',
            'hidden_inputs' => [
                'db' => $db,
                'table' => $table,
                'export_type' => $exportType,
                'export_method' => $GLOBALS['cfg']['Export']['method'],
                'template_id' => '',
            ],
            'export_method' => $GLOBALS['cfg']['Export']['method'],
            'plugins_choice' => $dropdown,
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $GLOBALS['cfg']['ExecTimeLimit'],
            'rows' => [],
            'has_save_dir' => true,
            'save_dir' => Util::userDir($GLOBALS['cfg']['SaveDir']),
            'export_is_checked' => $GLOBALS['cfg']['Export']['quick_export_onserver'],
            'export_overwrite_is_checked' => $GLOBALS['cfg']['Export']['quick_export_onserver_overwrite'],
            'has_aliases' => false,
            'aliases' => [],
            'is_checked_lock_tables' => $GLOBALS['cfg']['Export']['lock_tables'],
            'is_checked_asfile' => $GLOBALS['cfg']['Export']['asfile'],
            'is_checked_as_separate_files' => $GLOBALS['cfg']['Export']['as_separate_files'],
            'is_checked_export' => $GLOBALS['cfg']['Export']['onserver'],
            'is_checked_export_overwrite' => $GLOBALS['cfg']['Export']['onserver_overwrite'],
            'is_checked_remember_file_template' => $GLOBALS['cfg']['Export']['remember_file_template'],
            'repopulate' => false,
            'lock_tables' => false,
            'is_encoding_supported' => true,
            'encodings' => Encoding::listEncodings(),
            'export_charset' => $GLOBALS['cfg']['Export']['charset'],
            'export_asfile' => $GLOBALS['cfg']['Export']['asfile'],
            'has_zip' => $GLOBALS['cfg']['ZipDump'],
            'has_gzip' => $GLOBALS['cfg']['GZipDump'],
            'selected_compression' => 'none',
            'filename_template' => 'user value for test',
        ];

        $this->assertEquals($expected, $actual);
    }
}
