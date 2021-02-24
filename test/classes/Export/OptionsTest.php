<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Util;

class OptionsTest extends AbstractTestCase
{
    /** @var Options */
    private $export;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['server'] = 0;

        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'PMA';

        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = '';

        $pmaconfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pmaconfig->expects($this->any())
            ->method('getUserValue')
            ->will($this->returnValue('user value for test'));

        $GLOBALS['PMA_Config'] = $pmaconfig;

        $this->export = new Options(
            new Relation($GLOBALS['dbi']),
            new TemplateModel($GLOBALS['dbi'])
        );
    }

    public function testGetOptions(): void
    {
        global $cfg;

        $cfg['Export']['method'] = 'XML';
        $cfg['SaveDir'] = '/tmp';
        $cfg['ZipDump'] = false;
        $cfg['GZipDump'] = false;

        $export_type = 'server';
        $db = 'PMA';
        $table = 'PMA_test';
        $num_tables_str = '10';
        $unlim_num_rows_str = 'unlim_num_rows_str';
        //$single_table = "single_table";
        $GLOBALS['dbi']->getCache()->cacheTableContent([$db, $table, 'ENGINE'], 'MERGE');

        $columns_info = [
            'test_column1' => ['COLUMN_NAME' => 'test_column1'],
            'test_column2' => ['COLUMN_NAME' => 'test_column2'],
        ];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->will($this->returnValue($columns_info));
        $dbi->expects($this->any())->method('getCompatibilities')
            ->will($this->returnValue([]));

        $GLOBALS['dbi'] = $dbi;

        $exportList = Plugins::getExport($export_type, true);
        $dropdown = Plugins::getChoice('Export', 'what', $exportList, 'format');

        //Call the test function
        $actual = $this->export->getOptions(
            $export_type,
            $db,
            $table,
            '',
            $num_tables_str,
            $unlim_num_rows_str,
            $exportList
        );

        $expected = [
            'export_type' => $export_type,
            'db' => $db,
            'table' => $table,
            'templates' => [
                'is_enabled' => '',
                'templates' => [],
                'selected' => null,
            ],
            'sql_query' => '',
            'hidden_inputs' => [
                'db' => $db,
                'table' => $table,
                'export_type' => $export_type,
                'export_method' => $cfg['Export']['method'],
                'template_id' => '',
            ],
            'export_method' => $cfg['Export']['method'],
            'dropdown' => $dropdown,
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $cfg['ExecTimeLimit'],
            'rows' => [],
            'has_save_dir' => true,
            'save_dir' => Util::userDir($cfg['SaveDir']),
            'export_is_checked' => $cfg['Export']['quick_export_onserver'],
            'export_overwrite_is_checked' => $cfg['Export']['quick_export_onserver_overwrite'],
            'has_aliases' => false,
            'aliases' => [],
            'is_checked_lock_tables' => $cfg['Export']['lock_tables'],
            'is_checked_asfile' => $cfg['Export']['asfile'],
            'is_checked_as_separate_files' => $cfg['Export']['as_separate_files'],
            'is_checked_export' => $cfg['Export']['onserver'],
            'is_checked_export_overwrite' => $cfg['Export']['onserver_overwrite'],
            'is_checked_remember_file_template' => $cfg['Export']['remember_file_template'],
            'repopulate' => '',
            'lock_tables' => '',
            'is_encoding_supported' => true,
            'encodings' => Encoding::listEncodings(),
            'export_charset' => $cfg['Export']['charset'],
            'export_asfile' => $cfg['Export']['asfile'],
            'has_zip' => $cfg['ZipDump'],
            'has_gzip' => $cfg['GZipDump'],
            'selected_compression' => 'none',
            'filename_template' => 'user value for test',
        ];

        $this->assertIsArray($actual);
        $this->assertEquals($expected, $actual);
    }
}
