<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\FindReplaceController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Types;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\Controllers\Table\FindReplaceController
 */
#[CoversClass(FindReplaceController::class)]
#[AllowMockObjectsWithoutExpectations]
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
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
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

        $show_create_table = "CREATE TABLE `table` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
        `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `query` text COLLATE utf8_bin NOT NULL,
        PRIMARY KEY (`id`),
        KEY `foreign_field` (`foreign_db`,`foreign_table`)
        ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin "
            . "COMMENT='table'";

        $dbi->method('fetchValue')->willReturn($show_create_table);
        $dbi->method('escapeString')->willReturnArgument(0);

        $GLOBALS['dbi'] = $dbi;
    }

    public function testReplace(): void
    {
        $tableSearch = new FindReplaceController(
            ResponseRenderer::getInstance(),
            new Template(),
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['dbi']
        );
        $columnIndex = 0;
        $find = 'Field';
        $replaceWith = 'Column';
        $useRegex = false;
        $charSet = 'UTF-8';
        $tableSearch->replace($columnIndex, $find, $replaceWith, $useRegex, $charSet);

        $sql_query = $GLOBALS['sql_query'];
        $result = 'UPDATE `table` SET `Field1` = '
            . "REPLACE(`Field1`, 'Field', 'Column') "
            . "WHERE `Field1` LIKE '%Field%' COLLATE UTF-8_bin";
        self::assertSame($result, $sql_query);
    }

    public function testReplaceWithRegex(): void
    {
        $tableSearch = new FindReplaceController(
            ResponseRenderer::getInstance(),
            new Template(),
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['dbi']
        );

        $columnIndex = 0;
        $find = 'Field';
        $replaceWith = 'Column';
        $useRegex = true;
        $charSet = 'UTF-8';

        $tableSearch->replace($columnIndex, $find, $replaceWith, $useRegex, $charSet);

        $sql_query = $GLOBALS['sql_query'];

        $result = 'UPDATE `table` SET `Field1` = `Field1`'
            . " WHERE `Field1` RLIKE 'Field' COLLATE UTF-8_bin";

        self::assertSame($result, $sql_query);
    }

    public function testReplaceWithRegexKeepsLetterCase(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('getColumns')->willReturn([
            ['Field' => 'Field1', 'Type' => 'varchar(50)', 'Null' => 'NO', 'Collation' => 'utf8mb4_general_ci'],
        ]);
        $dbi->method('escapeString')->willReturnArgument(0);
        $dbi->expects(self::once())
            ->method('fetchResult')
            ->with(
                'SELECT `Field1`, 1, COUNT(*) FROM `db`.`table`'
                . " WHERE `Field1` RLIKE '[0-9]' COLLATE utf8mb4_bin"
                . ' GROUP BY `Field1`, CAST(`Field1` AS BINARY) ORDER BY `Field1` ASC',
                0
            )
            ->willReturn([
                'AB1' => ['AB1', 1, 1],
                'Ab1' => ['Ab1', 1, 1],
            ]);

        $tableSearch = new FindReplaceController(ResponseRenderer::getInstance(), new Template(), 'db', 'table', $dbi);

        $tableSearch->replace(0, '[0-9]', 'X', true, 'utf8mb4');

        $result = 'UPDATE `table` SET `Field1` = CASE'
            . "\n WHEN `Field1` = 'AB1' COLLATE utf8mb4_bin THEN 'ABX'"
            . "\n WHEN `Field1` = 'Ab1' COLLATE utf8mb4_bin THEN 'AbX' END"
            . " WHERE `Field1` RLIKE '[0-9]' COLLATE utf8mb4_bin";

        self::assertSame($result, $GLOBALS['sql_query']);
    }
}
