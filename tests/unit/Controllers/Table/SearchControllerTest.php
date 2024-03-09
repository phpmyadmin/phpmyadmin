<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ColumnFull;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\SearchController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(SearchController::class)]
class SearchControllerTest extends AbstractTestCase
{
    private DatabaseInterface&MockObject $mockedDbi;

    private ResponseStub $response;

    private Template $template;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * SET these to avoid undefined index error
         */
        $_POST['zoom_submit'] = 'zoom';

        Current::$database = 'PMA';
        Current::$table = 'PMA_BookMark';
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $this->mockedDbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockedDbi->types = new Types($this->mockedDbi);

        $columns = [
            new ColumnFull('Field1', 'Type1', 'Collation1', false, '', null, '', '', ''),
            new ColumnFull('Field2', 'Type2', 'Collation2', false, '', null, '', '', ''),
        ];
        $this->mockedDbi->expects(self::any())->method('getColumns')
            ->willReturn($columns);

        $showCreateTable = "CREATE TABLE `pma_bookmark` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `query` text COLLATE utf8_bin NOT NULL,
        PRIMARY KEY (`id`),
        KEY `foreign_field` (`foreign_db`,`foreign_table`)
        ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin "
        . "COMMENT='Bookmarks'";

        $this->mockedDbi->expects(self::any())->method('fetchValue')
            ->willReturn($showCreateTable);

        DatabaseInterface::$instance = $this->mockedDbi;

        $this->response = new ResponseStub();
        $this->template = new Template();
    }

    /**
     * Tests for getColumnMinMax()
     */
    public function testGetColumnMinMax(): void
    {
        $expected = 'SELECT MIN(`column`) AS `min`, MAX(`column`) AS `max` FROM `PMA`.`PMA_BookMark`';

        $this->mockedDbi->expects(self::any())
            ->method('fetchSingleRow')
            ->with($expected)
            ->willReturn([$expected]);

        $ctrl = new SearchController(
            $this->response,
            $this->template,
            new Search($this->mockedDbi),
            new Relation($this->mockedDbi),
            $this->mockedDbi,
            new DbTableExists($this->mockedDbi),
        );

        $result = $ctrl->getColumnMinMax('column');
        self::assertSame([$expected], $result);
    }
}
