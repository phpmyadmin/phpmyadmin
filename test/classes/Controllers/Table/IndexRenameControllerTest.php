<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\IndexRenameController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use ReflectionProperty;

use const PHP_VERSION_ID;

/** @covers \PhpMyAdmin\Controllers\Table\IndexRenameController */
final class IndexRenameControllerTest extends AbstractTestCase
{
    public function testPreviewSqlWithOldStatement(): void
    {
        $indexRegistry = new ReflectionProperty(Index::class, 'registry');
        if (PHP_VERSION_ID < 80100) {
            $indexRegistry->setAccessible(true);
        }

        $indexRegistry->setValue(null, []);

        $GLOBALS['cfg']['Server'] = $GLOBALS['cfg']['Servers'][1];
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $_POST['db'] = 'test_db';
        $_POST['table'] = 'test_table';
        $_POST['old_index'] = 'old_name';
        $_POST['index'] = ['Key_name' => 'new_name'];
        $_POST['do_save_data'] = '1';
        $_POST['preview_sql'] = '1';

        $dbiDummy = new DbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);
        $dbiDummy->addResult(
            'SHOW INDEXES FROM `test_db`.`test_table`',
            [
                ['test_table', '0', 'PRIMARY', 'id', 'BTREE'],
                ['test_table', '1', 'old_name', 'name', 'BTREE'],
            ],
            ['Table', 'Non_unique', 'Key_name', 'Column_name', 'Index_type']
        );

        $dbi = DatabaseInterface::load($dbiDummy);
        $dbi->setVersion(['@@version' => '5.5.0']);
        $GLOBALS['dbi'] = $dbi;

        $expected = <<<'HTML'
<div class="preview_sql">
            <code class="sql" dir="ltr"><pre>
ALTER TABLE `test_db`.`test_table` DROP INDEX `old_name`, ADD INDEX `new_name` (`name`) USING BTREE;
</pre></code>
    </div>

HTML;

        $responseRenderer = new ResponseRenderer();
        $template = new Template();
        $controller = new IndexRenameController(
            $responseRenderer,
            $template,
            'test_db',
            'test_table',
            $dbi,
            new Indexes($responseRenderer, $template, $dbi)
        );
        $controller();

        self::assertSame(['sql_data' => $expected], $responseRenderer->getJSONResult());

        $this->assertAllSelectsConsumed();
        $this->assertAllQueriesConsumed();

        $indexRegistry->setValue(null, []);
    }
}
