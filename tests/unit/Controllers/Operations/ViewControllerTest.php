<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Operations;

use PhpMyAdmin\Controllers\Operations\ViewController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ViewController::class)]
class ViewControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    /**
     * The reason a rename fails has to survive into the response instead of being
     * replaced by a bare "Error", and it has to be escaped because it embeds the
     * name the user submitted.
     */
    public function testRenameFailureKeepsTheReportedReason(): void
    {
        Current::$lang = 'en';
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$sqlQuery = '';

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $this->dummyDbi->addResult('SELECT @@lower_case_table_names', [['0']]);
        $this->dummyDbi->addResult('SHOW WARNINGS', []);

        // a trailing space makes the name invalid, so rename() reports it without
        // ever reaching the server
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table'])
            ->withParsedBody(['submitoptions' => 'something', 'new_name' => '<img src=x onerror=alert(1)> ']);

        $responseRenderer = new ResponseRenderer();
        $controller = new ViewController($responseRenderer, $this->dbi, new DbTableExists($this->dbi));
        $controller($request);

        $output = $responseRenderer->getHTMLResult();

        self::assertStringContainsString('Invalid table name: test_db.&lt;img src=x onerror=alert(1)&gt;', $output);
        self::assertStringNotContainsString('<img src=x onerror=alert(1)>', $output);
        self::assertStringContainsString('alert alert-danger', $output);
    }
}
