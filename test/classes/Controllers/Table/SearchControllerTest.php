<?php
/**
 * Tests for PMA_TableSearch
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\SearchController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PhpMyAdmin\Types;
use stdClass;
use function hash;

/**
 * Tests for PMA_TableSearch
 */
class SearchControllerTest extends AbstractTestCase
{
    /** @var ResponseStub */
    private $response;

    /** @var Template */
    private $template;

    /**
     * Setup function for test cases
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::loadDefaultConfig();
        parent::setTheme();

        /**
         * SET these to avoid undefined index error
         */
        $_POST['zoom_submit'] = 'zoom';

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'PMA';
        $GLOBALS['table'] = 'PMA_BookMark';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $relation = new Relation($GLOBALS['dbi']);
        $GLOBALS['cfgRelation'] = $relation->getRelationsParam();
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->types = new Types($dbi);

        $columns = [
            [
                'Field' => 'Field1',
                'Type' => 'Type1',
                'Null' => 'Null1',
                'Collation' => 'Collation1',
            ],
            [
                'Field' => 'Field2',
                'Type' => 'Type2',
                'Null' => 'Null2',
                'Collation' => 'Collation2',
            ],
        ];
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($columns));

        $show_create_table = "CREATE TABLE `pma_bookmark` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `query` text COLLATE utf8_bin NOT NULL,
        PRIMARY KEY (`id`),
        KEY `foreign_field` (`foreign_db`,`foreign_table`)
        ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin "
        . "COMMENT='Bookmarks'";

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($show_create_table));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $relation->dbi = $dbi;

        $this->response = new ResponseStub();
        $this->template = new Template();
    }

    /**
     * Tests for getColumnMinMax()
     */
    public function testGetColumnMinMax(): void
    {
        $expected = 'SELECT MIN(`column`) AS `min`, '
            . 'MAX(`column`) AS `max` '
            . 'FROM `PMA`.`PMA_BookMark`';

        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchSingleRow')
            ->with($expected)
            ->will($this->returnValue([$expected]));

        $ctrl = new SearchController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Search($GLOBALS['dbi']),
            new Relation($GLOBALS['dbi'], $this->template),
            $GLOBALS['dbi']
        );

        $result = $ctrl->getColumnMinMax('column');
        $this->assertEquals([$expected], $result);
    }

    /**
     * Tests for getDataRowAction()
     */
    public function testGetDataRowAction(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $meta_one = new stdClass();
        $meta_one->type = 'int';
        $meta_one->length = 11;
        $meta_two = new stdClass();
        $meta_two->length = 11;
        $meta_two->type = 'int';
        $fields_meta = [
            $meta_one,
            $meta_two,
        ];
        $GLOBALS['dbi']->expects($this->any())->method('getFieldsMeta')
            ->will($this->returnValue($fields_meta));

        $GLOBALS['dbi']->expects($this->any())->method('fetchAssoc')
            ->will(
                $this->returnCallback(
                    static function () {
                        static $count = 0;
                        if ($count == 0) {
                            $count++;

                            return [
                                'col1' => 1,
                                'col2' => 2,
                            ];
                        }

                        return null;
                    }
                )
            );

        $ctrl = new SearchController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Search($GLOBALS['dbi']),
            new Relation($GLOBALS['dbi'], $this->template),
            $GLOBALS['dbi']
        );

        $_POST['db'] = 'PMA';
        $_POST['table'] = 'PMA_BookMark';
        $_POST['where_clause'] = '`col1` = 1';
        $_POST['where_clause_sign'] = Core::signSqlQuery($_POST['where_clause']);
        $expected = [
            'col1' => 1,
            'col2' => 2,
        ];
        $ctrl->getDataRowAction();

        $json = $this->response->getJSONResult();
        $this->assertEquals(
            $expected,
            $json['row_info']
        );
    }
}
