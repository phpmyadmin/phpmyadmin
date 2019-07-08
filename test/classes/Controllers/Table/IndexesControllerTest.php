<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Controllers\Table\IndexesController
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\IndexesController;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Tests for PhpMyAdmin\Controllers\Table\IndexesController
 * @package PhpMyAdmin-test
 */
class IndexesControllerTest extends PmaTestCase
{
    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['url_params'] = [
            'db' => 'db',
            'server' => 1,
        ];

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $indexs = [
            [
                "Schema" => "Schema1",
                "Key_name" => "Key_name1",
                "Column_name" => "Column_name1",
            ],
            [
                "Schema" => "Schema2",
                "Key_name" => "Key_name2",
                "Column_name" => "Column_name2",
            ],
            [
                "Schema" => "Schema3",
                "Key_name" => "Key_name3",
                "Column_name" => "Column_name3",
            ],
        ];

        $dbi->expects($this->any())->method('getTableIndexes')
            ->will($this->returnValue($indexs));

        $GLOBALS['dbi'] = $dbi;

        //$_SESSION
    }

    /**
     * Tests for doSaveDataAction() method
     *
     * @return void
     * @test
     */
    public function testDoSaveDataAction()
    {
        $sql_query = 'ALTER TABLE `db`.`table` DROP PRIMARY KEY, ADD UNIQUE ;';

        $table = $this->getMockBuilder('PhpMyAdmin\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getSqlQueryForIndexCreateOrEdit')
            ->will($this->returnValue($sql_query));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $response = new ResponseStub();

        $ctrl = new IndexesController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $GLOBALS['db'],
            $GLOBALS['table'],
            null
        );

        // Preview SQL
        $_POST['preview_sql'] = true;
        $ctrl->doSaveDataAction();
        $jsonArray = $response->getJSONResult();
        $this->assertArrayHasKey('sql_data', $jsonArray);
        $this->assertStringContainsString(
            $sql_query,
            $jsonArray['sql_data']
        );

        // Alter success
        $response->clear();
        Response::getInstance()->setAjax(true);
        unset($_POST['preview_sql']);
        $ctrl->doSaveDataAction();
        $jsonArray = $response->getJSONResult();
        $this->assertArrayHasKey('index_table', $jsonArray);
        $this->assertArrayHasKey('message', $jsonArray);
        Response::getInstance()->setAjax(false);
    }

    /**
     * Tests for displayFormAction()
     *
     * @return void
     * @test
     */
    public function testDisplayFormAction()
    {
        $table = $this->getMockBuilder('PhpMyAdmin\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getStatusInfo')
            ->will($this->returnValue(""));
        $table->expects($this->any())->method('isView')
            ->will($this->returnValue(false));
        $table->expects($this->any())->method('getNameAndTypeOfTheColumns')
            ->will($this->returnValue(["field_name" => "field_type"]));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $response = new ResponseStub();
        $index = new Index();

        $ctrl = new IndexesController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $GLOBALS['db'],
            $GLOBALS['table'],
            $index
        );

        $_POST['create_index'] = true;
        $_POST['added_fields'] = 3;
        $ctrl->displayFormAction();
        $html = $response->getHTMLResult();

        //Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs(
                [
                    'db' => 'db',
                    'table' => 'table',
                    'create_index' => 1,
                ]
            ),
            $html
        );

        $doc_html = Util::showHint(
            Message::notice(
                __(
                    '"PRIMARY" <b>must</b> be the name of'
                    . ' and <b>only of</b> a primary key!'
                )
            )
        );
        $this->assertStringContainsString(
            $doc_html,
            $html
        );

        $this->assertStringContainsString(
            Util::showMySQLDocu('ALTER_TABLE'),
            $html
        );

        // generateIndexSelector
        $this->assertStringContainsString(
            $index->generateIndexChoiceSelector(false),
            $html
        );

        $this->assertStringContainsString(
            sprintf(__('Add %s column(s) to index'), 1),
            $html
        );

        //$field_name & $field_type
        $this->assertStringContainsString(
            "field_name",
            $html
        );
        $this->assertStringContainsString(
            "field_type",
            $html
        );
    }
}
