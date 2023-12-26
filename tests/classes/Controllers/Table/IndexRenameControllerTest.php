<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\IndexRenameController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Index;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(IndexRenameController::class)]
class IndexRenameControllerTest extends AbstractTestCase
{
    public function testIndexRenameController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $GLOBALS['lang'] = 'en';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $template = new Template();
        $expected = $template->render('table/index_rename_form', [
            'index' => new Index(),
            'form_params' => ['db' => 'test_db', 'table' => 'test_table'],
        ]);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $response = new ResponseRenderer();
        (new IndexRenameController(
            $response,
            $template,
            $dbi,
            new Indexes($response, $template, $dbi),
            new DbTableExists($dbi),
        ))($request);
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
