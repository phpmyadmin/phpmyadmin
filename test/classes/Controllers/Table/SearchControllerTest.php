<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\SearchController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Types;

use function hash;

use const MYSQLI_TYPE_LONG;

/**
 * @covers \PhpMyAdmin\Controllers\Table\SearchController
 */
class SearchControllerTest extends AbstractTestCase
{
    /** @var ResponseStub */
    private $response;

    /** @var Template */
    private $template;

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
        $_POST['zoom_submit'] = 'zoom';

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'PMA';
        $GLOBALS['table'] = 'PMA_BookMark';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $relation = new Relation($GLOBALS['dbi']);
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
        $expected = 'SELECT MIN(`column`) AS `min`, MAX(`column`) AS `max` FROM `PMA`.`PMA_BookMark`';

        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchSingleRow')
            ->with($expected)
            ->will($this->returnValue([$expected]));

        $ctrl = new SearchController(
            $this->response,
            $this->template,
            new Search($GLOBALS['dbi']),
            new Relation($GLOBALS['dbi']),
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
        parent::setGlobalDbi();
        parent::loadDbiIntoContainerBuilder();
        parent::loadResponseIntoContainerBuilder();

        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');

        $this->dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `PMA`.`PMA_BookMark`',
            []
        );

        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `PMA`.`PMA_BookMark`',
            []
        );

        $this->dummyDbi->addResult(
            'SELECT * FROM `PMA`.`PMA_BookMark` WHERE `col1` = 1;',
            [
                [
                    1,
                    2,
                ],
            ],
            [
                'col1',
                'col2',
            ],
            [
                new FieldMetadata(MYSQLI_TYPE_LONG, 0, (object) ['length' => 11]),
                new FieldMetadata(MYSQLI_TYPE_LONG, 0, (object) ['length' => 11]),
            ]
        );

        $GLOBALS['containerBuilder']->setParameter('db', 'PMA');
        $GLOBALS['containerBuilder']->setParameter('table', 'PMA_BookMark');

        /** @var SearchController $ctrl */
        $ctrl = $GLOBALS['containerBuilder']->get(SearchController::class);

        $_POST['db'] = 'PMA';
        $_POST['table'] = 'PMA_BookMark';
        $_POST['where_clause'] = '`col1` = 1';
        $_POST['where_clause_sign'] = Core::signSqlQuery($_POST['where_clause']);
        $expected = [
            'col1' => 1,
            'col2' => 2,
        ];
        $ctrl->getDataRowAction();

        $json = $this->getResponseJsonResult();
        $this->assertEquals($expected, $json['row_info']);
    }
}
