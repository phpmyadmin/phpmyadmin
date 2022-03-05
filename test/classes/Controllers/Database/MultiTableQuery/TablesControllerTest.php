<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\Database\MultiTableQuery\TablesController;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Controllers\Database\MultiTableQuery\TablesController
 */
class TablesControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalDbi();
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = '';
        parent::loadResponseIntoContainerBuilder();
    }

    public function testGetForeignKeyConstrainsForTable(): void
    {
        $_GET['tables'] = [
            'table1',
            'table2',
        ];
        $_GET['db'] = 'test';

        /** @var TablesController $multiTableQueryController */
        $multiTableQueryController = $GLOBALS['containerBuilder']->get(TablesController::class);
        $multiTableQueryController();
        $this->assertSame(
            [
                'foreignKeyConstrains' => [
                    [
                        'TABLE_NAME' => 'table2',
                        'COLUMN_NAME' => 'idtable2',
                        'REFERENCED_TABLE_NAME' => 'table1',
                        'REFERENCED_COLUMN_NAME' => 'idtable1',
                    ],
                ],
            ],
            $this->getResponseJsonResult()
        );
    }
}
