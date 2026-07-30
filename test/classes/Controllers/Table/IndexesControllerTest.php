<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\IndexesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Table;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;

use function __;
use function sprintf;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Controllers\Table\IndexesController
 */
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

    /**
     * Tests for displayFormAction()
     */
    public function testDisplayFormAction(): void
    {
        $table = $this->createMock(Table::class);
        $table->method('getStatusInfo')->willReturn('');
        $table->method('isView')->willReturn(false);
        $table->method('getNameAndTypeOfTheColumns')->willReturn(['field_name' => 'field_type']);

        $GLOBALS['dbi']->method('getTable')->willReturn($table);

        $response = new ResponseStub();
        $index = new Index();
        $template = new Template();

        $method = new ReflectionMethod(IndexesController::class, 'displayForm');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $ctrl = new IndexesController(
            $response,
            $template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['dbi'],
            new Indexes($response, $template, $GLOBALS['dbi'])
        );

        $_POST['create_index'] = true;
        $_POST['added_fields'] = 3;
        $method->invoke($ctrl, $index);
        $html = $response->getHTMLResult();

        //Url::getHiddenInputs
        self::assertStringContainsString(Url::getHiddenInputs(
            [
                'db' => 'db',
                'table' => 'table',
                'create_index' => 1,
            ]
        ), $html);

        $doc_html = Generator::showHint(
            Message::notice(
                __(
                    '"PRIMARY" <b>must</b> be the name of and <b>only of</b> a primary key!'
                )
            )->getMessage()
        );
        self::assertStringContainsString($doc_html, $html);

        self::assertStringContainsString(MySQLDocumentation::show('ALTER_TABLE'), $html);

        self::assertStringContainsString(sprintf(__('Add %s column(s) to index'), 1), $html);

        //$field_name & $field_type
        self::assertStringContainsString('field_name', $html);
        self::assertStringContainsString('field_type', $html);
    }
}
