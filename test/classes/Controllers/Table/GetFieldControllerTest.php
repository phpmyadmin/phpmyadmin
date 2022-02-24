<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\GetFieldController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function bin2hex;

/**
 * @covers \PhpMyAdmin\Controllers\Table\GetFieldController
 */
class GetFieldControllerTest extends AbstractTestCase
{
    public function testGetFieldController(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'table_with_blob';
        $_GET['transform_key'] = 'file';
        $_GET['sql_query'] = 'SELECT * FROM `test_db`.`table_with_blob`';
        $_GET['where_clause'] = '`table_with_blob`.`id` = 1';
        $_GET['where_clause_sign'] = Core::signSqlQuery('`table_with_blob`.`id` = 1');

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `test_db`.`table_with_blob`',
            [
                ['id', 'int(11)', 'NO', 'PRI', null, 'auto_increment'],
                ['file', 'blob', 'NO', '', null, ''],
            ],
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra']
        );
        $this->dummyDbi->addResult(
            'SHOW INDEXES FROM `test_db`.`table_with_blob`',
            [['table_with_blob', 'PRIMARY', 'id']],
            ['Table', 'Key_name', 'Column_name']
        );
        $this->dummyDbi->addResult(
            'SELECT `file` FROM `table_with_blob` WHERE `table_with_blob`.`id` = 1;',
            [[bin2hex('FILE')]],
            ['file']
        );

        (new GetFieldController(new ResponseRenderer(), new Template(), $this->dbi))();
        $this->expectOutputString('46494c45');
    }
}
