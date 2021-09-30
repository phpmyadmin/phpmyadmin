<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;

/**
 * @covers \PhpMyAdmin\Table\Indexes
 */
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
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['urlParams'] = [
            'db' => 'db',
            'server' => 1,
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indexs = [
            [
                'Schema' => 'Schema1',
                'Key_name' => 'Key_name1',
                'Column_name' => 'Column_name1',
            ],
            [
                'Schema' => 'Schema2',
                'Key_name' => 'Key_name2',
                'Column_name' => 'Column_name2',
            ],
            [
                'Schema' => 'Schema3',
                'Key_name' => 'Key_name3',
                'Column_name' => 'Column_name3',
            ],
        ];

        $dbi->expects($this->any())->method('getTableIndexes')
            ->will($this->returnValue($indexs));

        $GLOBALS['dbi'] = $dbi;

        //$_SESSION
    }

    public function testDoSaveData(): void
    {
        $sql_query = 'ALTER TABLE `db`.`table` DROP PRIMARY KEY, ADD UNIQUE ;';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getSqlQueryForIndexCreateOrEdit')
            ->will($this->returnValue($sql_query));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $response = new ResponseStub();
        $index = new Index();

        $indexes = new Indexes($response, new Template(), $GLOBALS['dbi']);

        // Preview SQL
        $_POST['preview_sql'] = true;
        $indexes->doSaveData($index, false, $GLOBALS['db'], $GLOBALS['table']);
        $jsonArray = $response->getJSONResult();
        $this->assertArrayHasKey('sql_data', $jsonArray);
        $this->assertStringContainsString($sql_query, $jsonArray['sql_data']);

        // Alter success
        $response->clear();
        ResponseRenderer::getInstance()->setAjax(true);
        unset($_POST['preview_sql']);
        $indexes->doSaveData($index, false, $GLOBALS['db'], $GLOBALS['table']);
        $jsonArray = $response->getJSONResult();
        $this->assertArrayHasKey('index_table', $jsonArray);
        $this->assertArrayHasKey('message', $jsonArray);
        ResponseRenderer::getInstance()->setAjax(false);
    }
}
