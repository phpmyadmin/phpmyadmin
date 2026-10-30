<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Table\IndexesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;

use function __;
use function sprintf;

#[CoversClass(IndexesController::class)]
#[AllowMockObjectsWithoutExpectations]
class IndexesControllerTest extends AbstractTestCase
{
    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * SET these to avoid undefined index error
         */
        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['DisableIS'] = false;
        UrlParams::$params = ['db' => 'db', 'server' => 1];
    }

    /**
     * Tests for displayFormAction()
     */
    public function testDisplayFormAction(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);

        $indexs = [
            ['Schema' => 'Schema1', 'Key_name' => 'Key_name1', 'Column_name' => 'Column_name1'],
            ['Schema' => 'Schema2', 'Key_name' => 'Key_name2', 'Column_name' => 'Column_name2'],
            ['Schema' => 'Schema3', 'Key_name' => 'Key_name3', 'Column_name' => 'Column_name3'],
        ];

        $dbi->method('getTableIndexes')->willReturn($indexs);

        DatabaseInterface::$instance = $dbi;

        $table = $this->createMock(Table::class);
        $table->method('getStatusInfo')->willReturn('');
        $table->method('isView')->willReturn(false);
        $table->method('getNameAndTypeOfTheColumns')->willReturn(['field_name' => 'field_type']);

        $dbi->method('getTable')->willReturn($table);

        $response = new ResponseStub();
        $index = new Index();
        $config = Config::getInstance();
        $template = new Template($config);

        $method = new ReflectionMethod(IndexesController::class, 'displayForm');

        $ctrl = new IndexesController(
            $response,
            $template,
            $dbi,
            new Indexes($dbi),
            new DbTableExists($dbi),
            $config,
        );

        $_POST['create_index'] = true;
        $_POST['added_fields'] = 3;
        $method->invoke($ctrl, $index);
        $html = $response->getHTMLResult();

        //Url::getHiddenInputs
        self::assertStringContainsString(
            Url::getHiddenInputs(
                ['db' => 'db', 'table' => 'table', 'create_index' => 1],
            ),
            $html,
        );

        $docHtml = Generator::showHint(
            Message::notice(
                __(
                    '"PRIMARY" <b>must</b> be the name of and <b>only of</b> a primary key!',
                ),
            )->getMessage(),
        );
        self::assertStringContainsString($docHtml, $html);

        self::assertStringContainsString(
            MySQLDocumentation::show('ALTER_TABLE'),
            $html,
        );

        self::assertStringContainsString(
            sprintf(__('Add %s column(s) to index'), 1),
            $html,
        );

        //$field_name & $field_type
        self::assertStringContainsString('field_name', $html);
        self::assertStringContainsString('field_type', $html);
    }
}
