<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ImportController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UserPreferences;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ImportController::class)]
class ImportControllerTest extends AbstractTestCase
{
    /**
     * @requires extension bz2
     * @requires extension zip
     */
    public function testImportController(): void
    {
        $this->setTheme();
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['lang'] = 'en';
        $config = Config::getInstance();
        $config->selectedServer = $config->getSettings()->Servers[1]->asArray();
        $_GET['format'] = 'xml';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);
        $dummyDbi->addResult('SELECT @@local_infile;', [['1']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $importList = Plugins::getImport('table');
        $choice = Plugins::getChoice($importList, 'xml');
        $options = Plugins::getOptions('Import', $importList);

        $pageSettings = new PageSettings(
            new UserPreferences($dbi, new Relation($dbi), new Template()),
        );
        $pageSettings->init('Import');
        $template = new Template();
        $expected = $template->render('table/import/index', [
            'page_settings_error_html' => $pageSettings->getErrorHTML(),
            'page_settings_html' => $pageSettings->getHTML(),
            'upload_id' => 'abc1234567890',
            'handler' => 'PhpMyAdmin\Plugins\Import\Upload\UploadNoplugin',
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
            'is_allow_interrupt_checked' => ' checked="checked"',
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
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $response = new ResponseRenderer();
        (new ImportController($response, $template, $dbi, $pageSettings, new DbTableExists($dbi)))($request);
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
