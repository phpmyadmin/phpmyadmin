<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportType;
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

        $this->setLanguage();

        $this->setGlobalConfig();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        Current::$table = 'table';
        Current::$database = 'PMA';

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
        $config = Config::getInstance();
        $config->set('SaveDir', '/tmp');

        Export::$singleTable = false;

        $exportType = ExportType::Server;
        $db = 'PMA';
        $table = 'PMA_test';
        $numTablesStr = '10';
        $unlimNumRowsStr = 'unlim_num_rows_str';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::any())->method('getCompatibilities')
            ->willReturn([]);

        DatabaseInterface::$instance = $dbi;

        $exportList = Plugins::getExport($exportType, true);
        $dropdown = Plugins::getChoice($exportList, 'sql');
        $config->selectedServer['host'] = 'localhost';
        $config->selectedServer['user'] = 'pma_user';
        $_POST['filename_template'] = 'user value for test';

        //Call the test function
        $actual = $this->export->getOptions(
            $exportType,
            $db,
            $table,
            '',
            $numTablesStr,
            $unlimNumRowsStr,
            $exportList,
            'sql',
            null,
        );

        $expected = [
            'export_type' => $exportType->value,
            'db' => $db,
            'table' => $table,
            'templates' => ['is_enabled' => '', 'templates' => [], 'selected' => null],
            'sql_query' => '',
            'hidden_inputs' => [
                'db' => $db,
                'table' => $table,
                'export_type' => $exportType->value,
                'export_method' => $config->config->Export->method,
                'template_id' => '',
            ],
            'export_method' => $config->config->Export->method,
            'plugins_choice' => $dropdown,
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $config->settings['ExecTimeLimit'],
            'rows' => [],
            'has_save_dir' => true,
            'save_dir' => Util::userDir($config->config->SaveDir),
            'export_is_checked' => $config->settings['Export']['quick_export_onserver'],
            'export_overwrite_is_checked' => $config->settings['Export']['quick_export_onserver_overwrite'],
            'has_aliases' => false,
            'aliases' => [],
            'is_checked_lock_tables' => $config->settings['Export']['lock_tables'],
            'is_checked_asfile' => $config->settings['Export']['asfile'],
            'is_checked_as_separate_files' => $config->settings['Export']['as_separate_files'],
            'is_checked_export' => $config->settings['Export']['onserver'],
            'is_checked_export_overwrite' => $config->settings['Export']['onserver_overwrite'],
            'is_checked_remember_file_template' => $config->settings['Export']['remember_file_template'],
            'repopulate' => false,
            'lock_tables' => false,
            'is_encoding_supported' => true,
            'encodings' => Encoding::listEncodings(),
            'export_charset' => $config->config->Export->charset,
            'export_asfile' => $config->settings['Export']['asfile'],
            'has_zip' => $config->config->ZipDump,
            'has_gzip' => $config->config->GZipDump,
            'selected_compression' => 'none',
            'filename_template' => 'user value for test',
        ];

        self::assertEquals($expected, $actual);
    }
}
