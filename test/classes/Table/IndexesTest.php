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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\Table\Indexes
 */
#[CoversClass(Indexes::class)]
#[AllowMockObjectsWithoutExpectations]
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

        $dbi = $this->createMock(DatabaseInterface::class);

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

        $dbi->method('getTableIndexes')->willReturn($indexs);

        $GLOBALS['dbi'] = $dbi;

        //$_SESSION
    }

    public function testDoSaveData(): void
    {
        $sql_query = 'ALTER TABLE `db`.`table` DROP PRIMARY KEY, ADD UNIQUE ;';

        $table = $this->createMock(Table::class);
        $table->method('getSqlQueryForIndexCreateOrEdit')->willReturn($sql_query);

        $GLOBALS['dbi']->method('getTable')->willReturn($table);

        $response = new ResponseStub();
        $index = new Index();

        $indexes = new Indexes($response, new Template(), $GLOBALS['dbi']);

        // Preview SQL
        $_POST['preview_sql'] = true;
        $indexes->doSaveData($index, false, $GLOBALS['db'], $GLOBALS['table']);
        $jsonArray = $response->getJSONResult();
        self::assertArrayHasKey('sql_data', $jsonArray);
        self::assertStringContainsString($sql_query, $jsonArray['sql_data']);

        // Alter success
        $response->clear();
        ResponseRenderer::getInstance()->setAjax(true);
        unset($_POST['preview_sql']);
        $indexes->doSaveData($index, false, $GLOBALS['db'], $GLOBALS['table']);
        $jsonArray = $response->getJSONResult();
        self::assertArrayHasKey('index_table', $jsonArray);
        self::assertArrayHasKey('message', $jsonArray);
        ResponseRenderer::getInstance()->setAjax(false);
    }
}
