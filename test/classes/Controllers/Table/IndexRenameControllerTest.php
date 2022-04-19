<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\IndexRenameController;
use PhpMyAdmin\Index;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\IndexRenameController
 */
class IndexRenameControllerTest extends AbstractTestCase
{
    public function testIndexRenameController(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['lang'] = 'en';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $template = new Template();
        $expected = $template->render('table/index_rename_form', [
            'index' => new Index(),
            'form_params' => ['db' => 'test_db', 'table' => 'test_table'],
        ]);

        $response = new ResponseRenderer();
        (new IndexRenameController($response, $template, $dbi, new Indexes($response, $template, $dbi)))();
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
