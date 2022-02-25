<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\DropColumnConfirmationController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\DropColumnConfirmationController
 */
class DropColumnConfirmationControllerTest extends AbstractTestCase
{
    public function testDropColumnConfirmation(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'table_type' => 'table',
            'selected_fld' => ['name', 'datetimefield'],
        ];

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);

        $response = new ResponseRenderer();
        $template = new Template();
        $expected = $template->render('table/structure/drop_confirm', [
            'db' => 'test_db',
            'table' => 'test_table',
            'fields' => ['name', 'datetimefield'],
        ]);

        (new DropColumnConfirmationController($response, $template))();
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
