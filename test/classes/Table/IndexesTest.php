<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Index;
use PhpMyAdmin\Table;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Indexes::class)]
class IndexesTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        parent::setTheme();

        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['text_dir'] = 'ltr';
        $config = Config::getInstance();
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['DisableIS'] = false;
        $GLOBALS['urlParams'] = ['db' => 'db', 'server' => 1];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indexs = [
            ['Schema' => 'Schema1', 'Key_name' => 'Key_name1', 'Column_name' => 'Column_name1'],
            ['Schema' => 'Schema2', 'Key_name' => 'Key_name2', 'Column_name' => 'Column_name2'],
            ['Schema' => 'Schema3', 'Key_name' => 'Key_name3', 'Column_name' => 'Column_name3'],
        ];

        $dbi->expects($this->any())->method('getTableIndexes')
            ->willReturn($indexs);

        DatabaseInterface::$instance = $dbi;

        //$_SESSION
    }

    public function testDoSaveData(): void
    {
        $sqlQuery = 'ALTER TABLE `db`.`table` DROP PRIMARY KEY, ADD UNIQUE ;';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getSqlQueryForIndexCreateOrEdit')
            ->willReturn($sqlQuery);

        $dbi = DatabaseInterface::getInstance();
        $dbi->expects($this->any())->method('getTable')
            ->willReturn($table);

        $response = new ResponseStub();
        $index = new Index();

        $indexes = new Indexes($response, new Template(), $dbi);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['ajax_request' => '1']);

        // Preview SQL
        $indexes->doSaveData($request, $index, false, $GLOBALS['db'], $GLOBALS['table'], true);
        $jsonArray = $response->getJSONResult();
        $this->assertArrayHasKey('sql_data', $jsonArray);
        $this->assertStringContainsString($sqlQuery, $jsonArray['sql_data']);

        // Alter success
        $response->clear();
        $indexes->doSaveData($request, $index, false, $GLOBALS['db'], $GLOBALS['table'], false);
        $jsonArray = $response->getJSONResult();
        $this->assertArrayHasKey('index_table', $jsonArray);
        $this->assertArrayHasKey('message', $jsonArray);
    }
}
