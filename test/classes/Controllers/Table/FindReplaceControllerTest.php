<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\FindReplaceController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Types;

/** @covers \PhpMyAdmin\Controllers\Table\FindReplaceController */
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
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->types = new Types($dbi);

        $columns = [
            ['Field' => 'Field1', 'Type' => 'Type1', 'Null' => 'Null1', 'Collation' => 'Collation1'],
            ['Field' => 'Field2', 'Type' => 'Type2', 'Null' => 'Null2', 'Collation' => 'Collation2'],
        ];
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($columns));

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
            ->will($this->returnValue($showCreateTable));
        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $GLOBALS['dbi'] = $dbi;
    }

    public function testReplace(): void
    {
        $tableSearch = new FindReplaceController(
            ResponseRenderer::getInstance(),
            new Template(),
            $GLOBALS['dbi'],
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
        $tableSearch = new FindReplaceController(ResponseRenderer::getInstance(), new Template(), $GLOBALS['dbi']);

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
