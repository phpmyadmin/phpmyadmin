<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\Table\ImportController;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\ImportController
 */
class ImportControllerTest extends AbstractTestCase
{
    public function testImportController(): void
    {
        $this->setTheme();
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server'] = $GLOBALS['config']->defaultServer;
        $_GET['format'] = 'xml';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);
        $dummyDbi->addResult('SELECT @@local_infile;', [['1']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $importList = Plugins::getImport('table');
        $choice = Plugins::getChoice($importList, 'xml');
        $options = Plugins::getOptions('Import', $importList);

        $pageSettings = new PageSettings('Import');
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

        $response = new ResponseRenderer();
        (new ImportController($response, $template, $dbi))();
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
