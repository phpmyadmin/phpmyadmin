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
use ReflectionMethod;

use function __;
use function sprintf;

/**
 * @covers \PhpMyAdmin\Controllers\Table\IndexesController
 */
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

    /**
     * Tests for displayFormAction()
     */
    public function testDisplayFormAction(): void
    {
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getStatusInfo')
            ->will($this->returnValue(''));
        $table->expects($this->any())->method('isView')
            ->will($this->returnValue(false));
        $table->expects($this->any())->method('getNameAndTypeOfTheColumns')
            ->will($this->returnValue(['field_name' => 'field_type']));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $response = new ResponseStub();
        $index = new Index();
        $template = new Template();

        $method = new ReflectionMethod(IndexesController::class, 'displayForm');
        $method->setAccessible(true);

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
