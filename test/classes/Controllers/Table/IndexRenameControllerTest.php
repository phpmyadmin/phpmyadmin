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

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);

        $template = new Template();
        $expected = $template->render('table/index_rename_form', [
            'index' => new Index(),
            'form_params' => ['db' => 'test_db', 'table' => 'test_table'],
        ]);

        $response = new ResponseRenderer();
        (new IndexRenameController($response, $template, $this->dbi, new Indexes($response, $template, $this->dbi)))();
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
