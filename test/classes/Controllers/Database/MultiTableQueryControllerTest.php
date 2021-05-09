<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\MultiTableQueryController;
use PhpMyAdmin\Tests\AbstractTestCase;

class MultiTableQueryControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
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

        global $containerBuilder;
        $containerBuilder->setParameter('db', $_GET['db']);
        /** @var MultiTableQueryController $multiTableQueryController */
        $multiTableQueryController = $containerBuilder->get(MultiTableQueryController::class);
        $multiTableQueryController->table();
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
