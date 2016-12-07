<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/controllers/TableIndexesController.php
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\controllers\table\TableIndexesController;
use PMA\libraries\di\Container;
use PMA\libraries\Theme;
use PMA\libraries\URL;
use PMA\libraries\Response;

/*
 * Include to test.
 */
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/relation.lib.php';

require_once 'test/libraries/stubs/ResponseStub.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for libraries/controllers/TableIndexesController.php
 *
 * @package PhpMyAdmin-test
 */
class TableIndexesControllerTest extends PMATestCase
{
    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['url_params'] = array(
            'db' => 'db',
            'server' => 1
        );

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $indexs = array(
            array(
                "Schema" => "Schema1",
                "Key_name"=>"Key_name1",
                "Column_name"=>"Column_name1"
            ),
            array(
                "Schema" => "Schema2",
                "Key_name"=>"Key_name2",
                "Column_name"=>"Column_name2"
            ),
            array(
                "Schema" => "Schema3",
                "Key_name"=>"Key_name3",
                "Column_name"=>"Column_name3"
            ),
        );

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

        $table = $this->getMockBuilder('PMA\libraries\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getSqlQueryForIndexCreateOrEdit')
            ->will($this->returnValue($sql_query));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $container = Container::getDefaultContainer();
        $container->set('db', 'db');
        $container->set('table', 'table');
        $container->set('dbi', $GLOBALS['dbi']);
        $response = new \PMA\Test\Stubs\Response();
        $container->set('PMA\libraries\Response', $response);
        $container->alias('response', 'PMA\libraries\Response');

        $ctrl = new TableIndexesController(null);

        // Preview SQL
        $_REQUEST['preview_sql'] = true;
        $ctrl->doSaveDataAction();
        $jsonArray = $response->getJSONResult();
        $this->assertArrayHasKey('sql_data', $jsonArray);
        $this->assertContains(
            $sql_query,
            $jsonArray['sql_data']
        );

        // Alter success
        $response->clear();
        Response::getInstance()->setAjax(true);
        unset($_REQUEST['preview_sql']);
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
        $table = $this->getMockBuilder('PMA\libraries\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->any())->method('getStatusInfo')
            ->will($this->returnValue(""));
        $table->expects($this->any())->method('isView')
            ->will($this->returnValue(false));
        $table->expects($this->any())->method('getNameAndTypeOfTheColumns')
            ->will($this->returnValue(array("field_name" => "field_type")));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $container = Container::getDefaultContainer();
        $container->set('db', 'db');
        $container->set('table', 'table');
        $container->set('dbi', $GLOBALS['dbi']);
        $response = new \PMA\Test\Stubs\Response();
        $container->set('PMA\libraries\Response', $response);
        $container->alias('response', 'PMA\libraries\Response');
        $index = new PMA\libraries\Index();

        $ctrl = new TableIndexesController($index);

        $_REQUEST['create_index'] = true;
        $_REQUEST['added_fields'] = 3;
        $ctrl->displayFormAction();
        $html = $response->getHTMLResult();

        //URL::getHiddenInputs
        $this->assertContains(
            URL::getHiddenInputs(
                array(
                    'db' => 'db',
                    'table' => 'table',
                    'create_index' => 1,
                )
            ),
            $html
        );

        $doc_html = PMA\libraries\Util::showHint(
            PMA\libraries\Message::notice(
                __(
                    '"PRIMARY" <b>must</b> be the name of'
                    . ' and <b>only of</b> a primary key!'
                )
            )
        );
        $this->assertContains(
            $doc_html,
            $html
        );

        $this->assertContains(
            PMA\libraries\Util::showMySQLDocu('ALTER_TABLE'),
            $html
        );

        // generateIndexSelector
        $this->assertContains(
            $index->generateIndexChoiceSelector(false),
            $html
        );

        $this->assertContains(
            sprintf(__('Add %s column(s) to index'), 1),
            $html
        );

        //$field_name & $field_type
        $this->assertContains(
            "field_name",
            $html
        );
        $this->assertContains(
            "field_type",
            $html
        );
    }
}
