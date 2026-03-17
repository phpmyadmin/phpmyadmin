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
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //assert that all sql are executed
        self::assertSame(
            'CREATE DATABASE IF NOT EXISTS `phpmyadmintest` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;'
            . 'USE `phpmyadmintest`;' . "\n"
            . 'CREATE TABLE IF NOT EXISTS `pma_bookmarktest` (' . "\n"
            . '  `id` int(11) NOT NULL AUTO_INCREMENT,' . "\n"
            . '  `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT \'\',' . "\n"
            . '  `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT \'\',' . "\n"
            . '  `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT \'\',' . "\n"
            . '  `query` text COLLATE utf8_bin NOT NULL,' . "\n"
            . '  PRIMARY KEY (`id`)' . "\n"
            . ') ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT=\'Bookmarks\';' . "\n"
            . '            ;'
            . 'INSERT INTO `phpmyadmintest`.`pma_bookmarktest` (`id`, `dbase`, `user`, `label`, `query`) '
            . 'VALUES (, \'\', \'\', \'\', \'\');;',
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
        global $import_notice;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin_importXML_GIS_For_Testing.xml';

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        $this->object->doImport($importHandle);

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
        global $import_notice;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin_importXML_No_Database_For_Testing.xml';

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        $this->object->doImport($importHandle);

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
