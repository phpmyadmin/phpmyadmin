<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ImportController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Import\Upload\UploadNoplugin;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[CoversClass(ImportController::class)]
class ImportControllerTest extends AbstractTestCase
{
    #[RequiresPhpExtension('bz2')]
    #[RequiresPhpExtension('zip')]
    public function testImportController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$lang = 'en';
        $config = Config::getInstance();
        $config->selectedServer = $config->getSettings()->Servers[1]->asArray();

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dummyDbi->addResult('SELECT @@local_infile;', [['1']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        ImportSettings::$importType = 'table';
        $importList = Plugins::getImport();
        $choice = Plugins::getChoice($importList, 'xml');
        $options = Plugins::getOptions('Import', $importList);

        $template = new Template($config);
        $userPreferences = new UserPreferences($dbi, new Relation($dbi, $config), $template, $config, new Clock());
        $pageSettings = new PageSettings($userPreferences);
        $pageSettings->init('Import');
        $expected = $template->render('table/import/index', [
            'page_settings_error_html' => $pageSettings->getErrorHTML(),
            'page_settings_html' => $pageSettings->getHTML(),
            'upload_id' => 'abc1234567890',
            'handler' => UploadNoplugin::class,
            'hidden_inputs' => [
                'noplugin' => 'abc1234567890',
                'import_type' => 'table',
                'db' => 'test_db',
                'table' => 'test_table',
            ],
            'db' => 'test_db',
            'table' => 'test_table',
            'max_upload_size' => 2097152,
            'formatted_maximum_upload_size' => '(Max: 2,048KiB)',
            'plugins_choice' => $choice,
            'options' => $options,
            'skip_queries_default' => '0',
            'is_allow_interrupt_checked' => ' checked',
            'local_import_file' => null,
            'is_upload' => true,
            'upload_dir' => '',
            'timeout_passed_global' => null,
            'compressions' => ['gzip', 'bzip2', 'zip'],
            'is_encoding_supported' => true,
            'encodings' => Encoding::listEncodings(),
            'import_charset' => '',
            'timeout_passed' => null,
            'offset' => null,
            'can_convert_kanji' => false,
            'charsets' => Charsets::getCharsets($dbi, false),
            'is_foreign_key_check' => true,
            'user_upload_dir' => '',
            'local_files' => '',
        ]);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table', 'format' => 'xml']);

        $response = new ResponseRenderer();
        (new ImportController($response, $dbi, $pageSettings, new DbTableExists($dbi), $config))($request);
        self::assertSame($expected, $response->getHTMLResult());
    }
}
