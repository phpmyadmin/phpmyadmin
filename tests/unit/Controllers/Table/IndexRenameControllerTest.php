<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Table\IndexRenameController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(IndexRenameController::class)]
class IndexRenameControllerTest extends AbstractTestCase
{
    public function testIndexRenameController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table_index_rename';
        Current::$lang = 'en';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table_index_rename` LIMIT 1;', [['1']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $template = new Template();
        $expected = $template->render('table/index_rename_form', [
            'index' => new Index(['Key_name' => 'index']),
            'form_params' => ['db' => 'test_db', 'table' => 'test_table_index_rename', 'old_index' => 'index'],
        ]);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table_index_rename'])
            ->withParsedBody(['index' => 'index']);

        $response = new ResponseRenderer();
        (new IndexRenameController(
            $response,
            $template,
            $dbi,
            new Indexes($dbi),
            new DbTableExists($dbi),
        ))($request);
        self::assertSame($expected, $response->getHTMLResult());
    }

    public function testPreviewSqlWithOldStatement(): void
    {
        $indexRegistry = new ReflectionProperty(Index::class, 'registry');
        $indexRegistry->setValue(null, []);

        Config::getInstance()->selectedServer['DisableIS'] = true;

        Current::$database = 'test_db';
        Current::$table = 'test_table_index_rename';
        $_POST['db'] = 'test_db';
        $_POST['table'] = 'test_table_index_rename';
        $_POST['old_index'] = 'old_name';
        $_POST['index'] = ['Key_name' => 'new_name'];
        $_POST['do_save_data'] = '1';
        $_POST['preview_sql'] = '1';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table_index_rename` LIMIT 1;', [['1']], ['1']);
        $dbiDummy->addResult(
            'SHOW INDEXES FROM `test_db`.`test_table_index_rename`',
            [
                ['test_table_index_rename', '0', 'PRIMARY', 'id', 'BTREE'],
                ['test_table_index_rename', '1', 'old_name', 'name', 'BTREE'],
            ],
            ['Table', 'Non_unique', 'Key_name', 'Column_name', 'Index_type'],
        );

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $dbi->setVersion(['@@version' => '5.5.0']);
        DatabaseInterface::$instance = $dbi;

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
        <div class="preview_sql">
                    <pre><code class="sql" dir="ltr">ALTER TABLE `test_db`.`test_table_index_rename` DROP INDEX `old_name`, ADD INDEX `new_name` (`name`) USING BTREE;</code></pre>
            </div>

        HTML;
        // phpcs:enable

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table_index_rename'])
            ->withParsedBody([
                'old_index' => 'old_name',
                'index' => 'new_name',
                'do_save_data' => '1',
                'preview_sql' => '1',
            ]);

        $responseRenderer = new ResponseRenderer();
        $template = new Template();
        $controller = new IndexRenameController(
            $responseRenderer,
            $template,
            $dbi,
            new Indexes($dbi),
            new DbTableExists($dbi),
        );
        $controller($request);

        self::assertSame(['sql_data' => $expected], $responseRenderer->getJSONResult());

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();

        $indexRegistry->setValue(null, []);
    }
}
