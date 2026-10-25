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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

use function hash;

use const MYSQLI_TYPE_LONG;

/**
 * @covers \PhpMyAdmin\Controllers\Table\SearchController
 */
#[CoversClass(SearchController::class)]
#[AllowMockObjectsWithoutExpectations]
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

        $dbi = $this->createMock(DatabaseInterface::class);
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
        $dbi->method('getColumns')->willReturn($columns);

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

        $dbi->method('fetchValue')->willReturn($show_create_table);
        $dbi->method('escapeString')->willReturnArgument(0);

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

        $GLOBALS['dbi']->expects($this->once())
            ->method('fetchSingleRow')
            ->with($expected)
            ->willReturn([$expected]);

        $ctrl = new SearchController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Search($GLOBALS['dbi']),
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );

        $result = $ctrl->getColumnMinMax('column');
        self::assertSame([$expected], $result);
    }

    /**
     * Tests for getDataRowAction()
     */
    public function testGetDataRowAction(): void
    {
        global $containerBuilder;

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

        $containerBuilder->setParameter('db', 'PMA');
        $containerBuilder->setParameter('table', 'PMA_BookMark');

        /** @var SearchController $ctrl */
        $ctrl = $containerBuilder->get(SearchController::class);

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
        self::assertSame($expected, $json['row_info']);
    }
}
