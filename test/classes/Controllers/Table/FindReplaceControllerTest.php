<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ColumnFull;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Table\FindReplaceController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Types;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FindReplaceController::class)]
class FindReplaceControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        parent::setGlobalConfig();

        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->types = new Types($dbi);

        $columns = [
            new ColumnFull('Field1', 'Type1', 'Collation1', false, '', null, '', '', ''),
            new ColumnFull('Field2', 'Type2', 'Collation2', false, '', null, '', '', ''),
        ];
        $dbi->expects($this->any())->method('getColumns')
            ->willReturn($columns);

        $showCreateTable = "CREATE TABLE `table` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `query` text COLLATE utf8_bin NOT NULL,
        PRIMARY KEY (`id`),
        KEY `foreign_field` (`foreign_db`,`foreign_table`)
        ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin "
            . "COMMENT='table'";

        $dbi->expects($this->any())->method('fetchValue')
            ->willReturn($showCreateTable);
        $dbi->expects($this->any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
    }

    public function testReplace(): void
    {
        $dbi = DatabaseInterface::getInstance();
        $tableSearch = new FindReplaceController(
            ResponseRenderer::getInstance(),
            new Template(),
            $dbi,
            new DbTableExists($dbi),
        );
        $columnIndex = 0;
        $find = 'Field';
        $replaceWith = 'Column';
        $useRegex = false;
        $charSet = 'UTF-8';
        $tableSearch->replace($columnIndex, $find, $replaceWith, $useRegex, $charSet);

        $sqlQuery = $GLOBALS['sql_query'];
        $result = 'UPDATE `table` SET `Field1` = '
            . "REPLACE(`Field1`, 'Field', 'Column') "
            . "WHERE `Field1` LIKE '%Field%' COLLATE UTF-8_bin";
        $this->assertEquals($result, $sqlQuery);
    }

    public function testReplaceWithRegex(): void
    {
        $dbi = DatabaseInterface::getInstance();
        $tableSearch = new FindReplaceController(
            ResponseRenderer::getInstance(),
            new Template(),
            $dbi,
            new DbTableExists($dbi),
        );

        $columnIndex = 0;
        $find = 'Field';
        $replaceWith = 'Column';
        $useRegex = true;
        $charSet = 'UTF-8';

        $tableSearch->replace($columnIndex, $find, $replaceWith, $useRegex, $charSet);

        $sqlQuery = $GLOBALS['sql_query'];

        $result = 'UPDATE `table` SET `Field1` = `Field1`'
            . " WHERE `Field1` RLIKE 'Field' COLLATE UTF-8_bin";

        $this->assertEquals($result, $sqlQuery);
    }
}
