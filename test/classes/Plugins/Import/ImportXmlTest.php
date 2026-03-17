<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportXml;
use PhpMyAdmin\Tests\AbstractTestCase;

use function __;

/**
 * @covers \PhpMyAdmin\Plugins\Import\ImportXml
 * @requires extension xml
 * @requires extension xmlwriter
 */
class ImportXmlTest extends AbstractTestCase
{
    /** @var ImportXml */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;

        $this->object = new ImportXml();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin_importXML_For_Testing.xml';
        $GLOBALS['import_text'] = 'ImportXml_Test';
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Xml';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for getProperties
     *
     * @group medium
     */
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
        self::assertSame(__('XML'), $properties->getText());
        self::assertSame('xml', $properties->getExtension());
        self::assertSame('text/xml', $properties->getMimeType());
        self::assertNull($properties->getOptions());
        self::assertSame(__('Options'), $properties->getOptionsText());
    }

    /**
     * Test for doImport
     *
     * @group medium
     * @requires extension simplexml
     */
    public function testDoImport(): void
    {
        //$import_notice will show the import detail result
        global $sql_query;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedQuery = <<<'SQL'
CREATE DATABASE IF NOT EXISTS `phpmyadmintest` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;USE `phpmyadmintest`;
CREATE TABLE IF NOT EXISTS `pma_bookmarktest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `query` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';
            ;INSERT INTO `phpmyadmintest`.`pma_bookmarktest` (`id`, `dbase`, `user`, `label`, `query`) VALUES (1, 'pma_dbase', '', 'pma_label', 'SELECT * FROM `db_content` WHERE 1');;
SQL;
        // phpcs:enable Generic.Files.LineLength.TooLong

        //assert that all sql are executed
        self::assertSame(
            $expectedQuery,
            $sql_query
        );

        self::assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for doImport using the GIS dataset
     *
     * @group medium
     * @requires extension simplexml
     */
    public function testDoImportDatasetGIS(): void
    {
        global $import_notice, $sql_query;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin_importXML_GIS_For_Testing.xml';

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        $this->object->doImport($importHandle);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedQuery = <<<'SQL'
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;USE `test`;
CREATE TABLE IF NOT EXISTS `test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `binary` binary(16) NOT NULL,
  `name` varchar(20) NOT NULL,
  `shape` geometry DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ;INSERT INTO `test`.`test` (`id`, `binary`, `name`, `shape`) VALUES (1, 0x10000000000000000000000000000000, 'POLYGON', 0x00000000010300000001000000040000000000000000405f40000000000000000000000000008063400000000000006940000000000040664000000000004057400000000000405f400000000000000000),
 (2, 0x20000000000000000000000000000000, 'MULTIPOLYGON', 0x00000000010600000002000000010300000001000000040000000000000000006140000000000000444000000000006062400000000000c0544000000000004064400000000000c0524000000000000061400000000000004440010300000001000000040000000000000000405a4000000000000000000000000000004c400000000000006940000000000080534000000000004057400000000000405a400000000000000000),
 (3, 0x30000000000000000000000000000000, 'MULTIPOINT', 0x0000000001040000000400000001010000000000000000405f400000000000004940010100000000000000008063400000000000406f40010100000000000000004066400000000000e0614001010000000000000000e065400000000000005440),
 (4, 0x40000000000000000000000000000000, 'MULTILINESTRING', 0x000000000105000000020000000102000000030000000000000000004240000000000080614000000000008047400000000000206d400000000000004f400000000000c052400102000000030000000000000000004240000000000000594000000000000031400000000000206d4000000000004066400000000000405740),
 (5, 0x50000000000000000000000000000000, 'POINT', 0x00000000010100000000000000000059400000000000406f40),
 (6, 0x60000000000000000000000000000000, 'LINESTRING', 0x0000000001020000000400000000000000008063400000000000003840000000000040664000000000000058400000000000606d4000000000000069400000000000a063400000000000c06140),
 (7, 0x70000000000000000000000000000000, 'GEOMETRYCOLLECTION', 0x000000000107000000030000000101000000000000000000594000000000000059400102000000050000000000000000000000000000000000000000000000000059400000000000005940000000000000694000000000000069400000000000c072400000000000c07240000000000000794000000000000079400103000000020000000500000000000000008041400000000000002440000000000000244000000000000034400000000000002e40000000000000444000000000008046400000000000804640000000000080414000000000000024400400000000000000000034400000000000003e40000000000080414000000000008041400000000000003e40000000000000344000000000000034400000000000003e40),
 (8, 0x80000000000000000000000000000000, 'TEST', NULL);;
SQL;
        // phpcs:enable Generic.Files.LineLength.TooLong

        //assert that all sql are executed
        self::assertSame(
            $expectedQuery,
            $sql_query
        );

        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            $import_notice
        );
        self::assertStringContainsString('Go to database: `test`', $import_notice);
        self::assertStringContainsString('Edit settings for `test`', $import_notice);
        self::assertStringContainsString('Go to table: `test`', $import_notice);
        self::assertStringContainsString('Edit settings for `test`', $import_notice);
        self::assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for doImport using no database dataset
     *
     * @group medium
     * @requires extension simplexml
     */
    public function testDoImportDatasetNoDatabase(): void
    {
        global $import_notice, $sql_query;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin_importXML_No_Database_For_Testing.xml';

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        $this->object->doImport($importHandle);

        $expectedQuery = <<<'SQL'
CREATE DATABASE IF NOT EXISTS `test25` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;USE `test25`;
CREATE TABLE IF NOT EXISTS `test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ;
SQL;

        //assert that all sql are executed
        self::assertSame(
            $expectedQuery,
            $sql_query
        );

        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            $import_notice
        );
        self::assertStringContainsString('Go to database: `test25`', $import_notice);
        self::assertStringContainsString('Edit settings for `test`', $import_notice);
        self::assertStringContainsString('Go to table: `test`', $import_notice);
        self::assertStringContainsString('Edit settings for `test`', $import_notice);
        self::assertTrue($GLOBALS['finished']);
    }
}
