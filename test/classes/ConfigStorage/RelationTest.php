<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use ReflectionClass;

use function implode;

/**
 * @covers \PhpMyAdmin\ConfigStorage\Relation
 * @group medium
 */
class RelationTest extends AbstractTestCase
{
    /** @var Relation */
    private $relation;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ZeroConf'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $_SESSION['relation'] = [];

        $this->relation = new Relation($GLOBALS['dbi']);
    }

    /**
     * Test for getDisplayField
     */
    public function testPMAGetDisplayField(): void
    {
        $this->dummyDbi->addSelectDb('phpmyadmin');
        $db = 'information_schema';
        $table = 'CHARACTER_SETS';
        self::assertSame('DESCRIPTION', $this->relation->getDisplayField($db, $table));
        $this->assertAllSelectsConsumed();

        $db = 'information_schema';
        $table = 'TABLES';
        self::assertSame('TABLE_COMMENT', $this->relation->getDisplayField($db, $table));

        $db = 'information_schema';
        $table = 'PMA';
        self::assertFalse($this->relation->getDisplayField($db, $table));
    }

    /**
     * Test for getComments
     */
    public function testPMAGetComments(): void
    {
        $GLOBALS['cfg']['ServerDefault'] = 0;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $getColumnsResult = [
            [
                'Field' => 'field1',
                'Type' => 'int(11)',
                'Comment' => 'Comment1',
            ],
            [
                'Field' => 'field2',
                'Type' => 'text',
                'Comment' => 'Comment1',
            ],
        ];
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($getColumnsResult));

        $GLOBALS['dbi'] = $dbi;
        $this->relation->dbi = $GLOBALS['dbi'];

        $db = 'information_schema';
        self::assertSame([''], $this->relation->getComments($db));

        $db = 'information_schema';
        $table = 'TABLES';
        self::assertSame([
            'field1' => 'Comment1',
            'field2' => 'Comment1',
        ], $this->relation->getComments($db, $table));
    }

    /**
     * Test for tryUpgradeTransformations
     */
    public function testPMATryUpgradeTransformations(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQueryAsControlUser')
            ->will($this->returnValue($resultStub));
        $resultStub->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(0));
        $dbi->expects($this->any())
            ->method('getError')
            ->will($this->onConsecutiveCalls('Error', ''));
        $GLOBALS['dbi'] = $dbi;
        $this->relation->dbi = $GLOBALS['dbi'];

        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['column_info'] = 'column_info';

        // Case 1
        $actual = $this->relation->tryUpgradeTransformations();
        self::assertFalse($actual);

        // Case 2
        $actual = $this->relation->tryUpgradeTransformations();
        self::assertTrue($actual);
    }

    public function testSearchColumnInForeignersError(): void
    {
        self::assertFalse($this->relation->searchColumnInForeigners([], 'id'));
    }

    /**
     * Test for searchColumnInForeigners
     */
    public function testPMASearchColumnInForeigners(): void
    {
        $foreigners = [
            'value' => [
                'master_field' => 'value',
                'foreign_db' => 'GSoC14',
                'foreign_table' => 'test',
                'foreign_field' => 'value',
            ],
            'foreign_keys_data' => [
                0 => [
                    'constraint' => 'ad',
                    'index_list' => [
                        'id',
                        'value',
                    ],
                    'ref_db_name' => 'GSoC14',
                    'ref_table_name' => 'table_1',
                    'ref_index_list' => [
                        'id',
                        'value',
                    ],
                    'on_delete' => 'CASCADE',
                    'on_update' => 'CASCADE',
                ],
            ],
        ];

        $foreigner = $this->relation->searchColumnInForeigners($foreigners, 'id');
        $expected = [];
        $expected['foreign_field'] = 'id';
        $expected['foreign_db'] = 'GSoC14';
        $expected['foreign_table'] = 'table_1';
        $expected['constraint'] = 'ad';
        $expected['on_delete'] = 'CASCADE';
        $expected['on_update'] = 'CASCADE';

        self::assertEquals($expected, $foreigner);
    }

    public function testFixPmaTablesNothingWorks(): void
    {
        parent::setGlobalDbi();

        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult('SHOW TABLES FROM `db_pma`;', false);

        $this->relation->fixPmaTables('db_pma', false);
        $this->assertAllQueriesConsumed();
    }

    public function testFixPmaTablesNormal(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_db_pma']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_db_pma']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            [['NULL']]
        );
        $this->dummyDbi->addSelectDb('db_pma');

        $_SESSION['relation'] = [];

        $this->relation->fixPmaTables('db_pma', false);

        self::assertArrayHasKey($GLOBALS['server'], $_SESSION['relation'], 'The cache is expected to be filled');
        /** @psalm-suppress EmptyArrayAccess */
        self::assertIsArray($_SESSION['relation'][$GLOBALS['server']]);

        $relationParameters = RelationParameters::fromArray([
            'db' => 'db_pma',
            'userconfigwork' => true,
            'userconfig' => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $_SESSION['relation'][$GLOBALS['server']]);

        $this->assertAllQueriesConsumed();
        $this->assertAllSelectsConsumed();
    }

    public function testFixPmaTablesNormalFixTables(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_db_pma']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_db_pma']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            [['NULL']]
        );
        $this->dummyDbi->addSelectDb('db_pma');
        $this->dummyDbi->addSelectDb('db_pma');

        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__bookmark` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__bookmark` ( '
                . '`id` int(10) unsigned NOT NULL auto_increment,'
                . ' `dbase` varchar(255) NOT NULL default \'\','
                . ' `user` varchar(255) NOT NULL default \'\','
                . ' `label` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `query` text NOT NULL, PRIMARY KEY (`id`) )'
                . ' COMMENT=\'Bookmarks\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__relation` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__relation` ( '
                . '`master_db` varchar(64) NOT NULL default \'\', `master_table` varchar(64) NOT NULL default \'\','
                . ' `master_field` varchar(64) NOT NULL default \'\', `foreign_db` varchar(64) NOT NULL default \'\','
                . ' `foreign_table` varchar(64) NOT NULL default \'\','
                . ' `foreign_field` varchar(64) NOT NULL default \'\','
                . ' PRIMARY KEY (`master_db`,`master_table`,`master_field`),'
                . ' KEY `foreign_field` (`foreign_db`,`foreign_table`) ) COMMENT=\'Relation table\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_info`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_info` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `display_field` varchar(64) NOT NULL default \'\', PRIMARY KEY (`db_name`,`table_name`) )'
                . ' COMMENT=\'Table information for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );

        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_coords`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_coords` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `pdf_page_number` int(11) NOT NULL default \'0\', `x` float unsigned NOT NULL default \'0\','
                . ' `y` float unsigned NOT NULL default \'0\','
                . ' PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`) )'
                . ' COMMENT=\'Table coordinates for phpMyAdmin PDF output\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__pdf_pages`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__pdf_pages` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `page_nr` int(10) unsigned NOT NULL auto_increment,'
                . ' `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default \'\', PRIMARY KEY (`page_nr`),'
                . ' KEY `db_name` (`db_name`) ) COMMENT=\'PDF relation pages for phpMyAdmin\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__column_info`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__column_info` ( '
                . '`id` int(5) unsigned NOT NULL auto_increment, `db_name` varchar(64) NOT NULL default \'\','
                . ' `table_name` varchar(64) NOT NULL default \'\', `column_name` varchar(64) NOT NULL default \'\','
                . ' `comment` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `mimetype` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `transformation` varchar(255) NOT NULL default \'\','
                . ' `transformation_options` varchar(255) NOT NULL default \'\','
                . ' `input_transformation` varchar(255) NOT NULL default \'\','
                . ' `input_transformation_options` varchar(255) NOT NULL default \'\','
                . ' PRIMARY KEY (`id`), UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`) )'
                . ' COMMENT=\'Column information for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__history` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__history` ( '
                . '`id` bigint(20) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db` varchar(64) NOT NULL default \'\', `table` varchar(64) NOT NULL default \'\','
                . ' `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP, `sqlquery` text NOT NULL,'
                . ' PRIMARY KEY (`id`), KEY `username` (`username`,`db`,`table`,`timevalue`) )'
                . ' COMMENT=\'SQL history for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__recent` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__recent` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Recently accessed tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__favorite` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__favorite` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Favorite tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_uiprefs`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` ( '
                . '`username` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL, `prefs` text NOT NULL,'
                . ' `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
                . ' PRIMARY KEY (`username`,`db_name`,`table_name`) ) COMMENT=\'Tables\'\' UI preferences\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__tracking` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__tracking` ( '
                . '`db_name` varchar(64) NOT NULL, `table_name` varchar(64) NOT NULL,'
                . ' `version` int(10) unsigned NOT NULL, `date_created` datetime NOT NULL,'
                . ' `date_updated` datetime NOT NULL, `schema_snapshot` text NOT NULL,'
                . ' `schema_sql` text, `data_sql` longtext, `tracking`'
                . ' set(\'UPDATE\',\'REPLACE\',\'INSERT\',\'DELETE\','
                . '\'TRUNCATE\',\'CREATE DATABASE\',\'ALTER DATABASE\','
                . '\'DROP DATABASE\',\'CREATE TABLE\',\'ALTER TABLE\','
                . '\'RENAME TABLE\',\'DROP TABLE\',\'CREATE INDEX\','
                . '\'DROP INDEX\',\'CREATE VIEW\',\'ALTER VIEW\',\'DROP VIEW\')'
                . ' default NULL, `tracking_active` int(1) unsigned NOT NULL'
                . ' default \'1\', PRIMARY KEY (`db_name`,`table_name`,`version`) )'
                . ' COMMENT=\'Database changes tracking for phpMyAdmin\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__users` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__users` ( '
                . '`username` varchar(64) NOT NULL, `usergroup` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`usergroup`) )'
                . ' COMMENT=\'Users and their assignments to user groups\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__usergroups`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__usergroups` ( '
                . '`usergroup` varchar(64) NOT NULL, `tab` varchar(64) NOT NULL,'
                . ' `allowed` enum(\'Y\',\'N\') NOT NULL DEFAULT \'N\','
                . ' PRIMARY KEY (`usergroup`,`tab`,`allowed`) )'
                . ' COMMENT=\'User groups with configured menu items\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__navigationhiding`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__navigationhiding` ( '
                . '`username` varchar(64) NOT NULL, `item_name` varchar(64)'
                . ' NOT NULL, `item_type` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`) )'
                . ' COMMENT=\'Hidden items of navigation tree\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__savedsearches`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__savedsearches` ( '
                . '`id` int(5) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db_name` varchar(64) NOT NULL default \'\', `search_name` varchar(64) NOT NULL default \'\','
                . ' `search_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`) )'
                . ' COMMENT=\'Saved searches\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__central_columns`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__central_columns` ( '
                . '`db_name` varchar(64) NOT NULL, `col_name` varchar(64) NOT NULL, `col_type` varchar(64) NOT NULL,'
                . ' `col_length` text, `col_collation` varchar(64) NOT NULL, `col_isNull` boolean NOT NULL,'
                . ' `col_extra` varchar(255) default \'\', `col_default` text,'
                . ' PRIMARY KEY (`db_name`,`col_name`) )'
                . ' COMMENT=\'Central list of columns\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__designer_settings`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__designer_settings` ( '
                . '`username` varchar(64) NOT NULL, `settings_data` text NOT NULL,'
                . ' PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Settings related to Designer\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__export_templates`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__export_templates` ( '
                . '`id` int(5) unsigned NOT NULL AUTO_INCREMENT, `username` varchar(64) NOT NULL,'
                . ' `export_type` varchar(10) NOT NULL, `template_name` varchar(64) NOT NULL,'
                . ' `template_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`) )'
                . ' COMMENT=\'Saved export templates\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );

        self::assertSame('', $GLOBALS['cfg']['Server']['pmadb']);

        $_SESSION['relation'] = [];

        $this->relation->fixPmaTables('db_pma', true);
        self::assertArrayNotHasKey('message', $GLOBALS);
        self::assertArrayHasKey($GLOBALS['server'], $_SESSION['relation'], 'The cache is expected to be filled');
        /** @psalm-suppress EmptyArrayAccess */
        self::assertIsArray($_SESSION['relation'][$GLOBALS['server']]);
        self::assertSame('db_pma', $GLOBALS['cfg']['Server']['pmadb']);

        $relationParameters = RelationParameters::fromArray([
            'db' => 'db_pma',
            'userconfigwork' => true,
            'userconfig' => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $_SESSION['relation'][$GLOBALS['server']]);

        $this->assertAllQueriesConsumed();
        $this->assertAllSelectsConsumed();
    }

    public function testFixPmaTablesNormalFixTablesWithCustomOverride(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = 'db_pma';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = 'custom_relation_pma';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [
                ['pma__userconfig'],
                // This is important as it tricks default existing table detection
                // If the check does not consider the custom name it will skip the table
                ['pma__relation'],
            ],
            ['Tables_in_db_pma']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`',
            [
                ['pma__userconfig'],
                // This is important as it tricks default existing table detection
                // If the check does not consider the custom name it will skip the table
                ['pma__relation'],
            ],
            ['Tables_in_db_pma']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            [['NULL']]
        );

        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__bookmark` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__bookmark` ( '
                . '`id` int(10) unsigned NOT NULL auto_increment,'
                . ' `dbase` varchar(255) NOT NULL default \'\','
                . ' `user` varchar(255) NOT NULL default \'\','
                . ' `label` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `query` text NOT NULL, PRIMARY KEY (`id`) )'
                . ' COMMENT=\'Bookmarks\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `custom_relation_pma` '
            . '-- CREATE TABLE IF NOT EXISTS `custom_relation_pma` ( '
                . '`master_db` varchar(64) NOT NULL default \'\', `master_table` varchar(64) NOT NULL default \'\','
                . ' `master_field` varchar(64) NOT NULL default \'\', `foreign_db` varchar(64) NOT NULL default \'\','
                . ' `foreign_table` varchar(64) NOT NULL default \'\','
                . ' `foreign_field` varchar(64) NOT NULL default \'\','
                . ' PRIMARY KEY (`master_db`,`master_table`,`master_field`),'
                . ' KEY `foreign_field` (`foreign_db`,`foreign_table`) ) COMMENT=\'Relation table\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_info`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_info` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `display_field` varchar(64) NOT NULL default \'\', PRIMARY KEY (`db_name`,`table_name`) )'
                . ' COMMENT=\'Table information for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );

        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_coords`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_coords` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `pdf_page_number` int(11) NOT NULL default \'0\', `x` float unsigned NOT NULL default \'0\','
                . ' `y` float unsigned NOT NULL default \'0\','
                . ' PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`) )'
                . ' COMMENT=\'Table coordinates for phpMyAdmin PDF output\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__pdf_pages`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__pdf_pages` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `page_nr` int(10) unsigned NOT NULL auto_increment,'
                . ' `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default \'\', PRIMARY KEY (`page_nr`),'
                . ' KEY `db_name` (`db_name`) ) COMMENT=\'PDF relation pages for phpMyAdmin\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__column_info`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__column_info` ( '
                . '`id` int(5) unsigned NOT NULL auto_increment, `db_name` varchar(64) NOT NULL default \'\','
                . ' `table_name` varchar(64) NOT NULL default \'\', `column_name` varchar(64) NOT NULL default \'\','
                . ' `comment` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `mimetype` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `transformation` varchar(255) NOT NULL default \'\','
                . ' `transformation_options` varchar(255) NOT NULL default \'\','
                . ' `input_transformation` varchar(255) NOT NULL default \'\','
                . ' `input_transformation_options` varchar(255) NOT NULL default \'\','
                . ' PRIMARY KEY (`id`), UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`) )'
                . ' COMMENT=\'Column information for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__history` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__history` ( '
                . '`id` bigint(20) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db` varchar(64) NOT NULL default \'\', `table` varchar(64) NOT NULL default \'\','
                . ' `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP, `sqlquery` text NOT NULL,'
                . ' PRIMARY KEY (`id`), KEY `username` (`username`,`db`,`table`,`timevalue`) )'
                . ' COMMENT=\'SQL history for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__recent` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__recent` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Recently accessed tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__favorite` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__favorite` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Favorite tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_uiprefs`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` ( '
                . '`username` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL, `prefs` text NOT NULL,'
                . ' `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
                . ' PRIMARY KEY (`username`,`db_name`,`table_name`) ) COMMENT=\'Tables\'\' UI preferences\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__tracking` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__tracking` ( '
                . '`db_name` varchar(64) NOT NULL, `table_name` varchar(64) NOT NULL,'
                . ' `version` int(10) unsigned NOT NULL, `date_created` datetime NOT NULL,'
                . ' `date_updated` datetime NOT NULL, `schema_snapshot` text NOT NULL,'
                . ' `schema_sql` text, `data_sql` longtext, `tracking`'
                . ' set(\'UPDATE\',\'REPLACE\',\'INSERT\',\'DELETE\','
                . '\'TRUNCATE\',\'CREATE DATABASE\',\'ALTER DATABASE\','
                . '\'DROP DATABASE\',\'CREATE TABLE\',\'ALTER TABLE\','
                . '\'RENAME TABLE\',\'DROP TABLE\',\'CREATE INDEX\','
                . '\'DROP INDEX\',\'CREATE VIEW\',\'ALTER VIEW\',\'DROP VIEW\')'
                . ' default NULL, `tracking_active` int(1) unsigned NOT NULL'
                . ' default \'1\', PRIMARY KEY (`db_name`,`table_name`,`version`) )'
                . ' COMMENT=\'Database changes tracking for phpMyAdmin\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__users` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__users` ( '
                . '`username` varchar(64) NOT NULL, `usergroup` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`usergroup`) )'
                . ' COMMENT=\'Users and their assignments to user groups\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__usergroups`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__usergroups` ( '
                . '`usergroup` varchar(64) NOT NULL, `tab` varchar(64) NOT NULL,'
                . ' `allowed` enum(\'Y\',\'N\') NOT NULL DEFAULT \'N\','
                . ' PRIMARY KEY (`usergroup`,`tab`,`allowed`) )'
                . ' COMMENT=\'User groups with configured menu items\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__navigationhiding`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__navigationhiding` ( '
                . '`username` varchar(64) NOT NULL, `item_name` varchar(64)'
                . ' NOT NULL, `item_type` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`) )'
                . ' COMMENT=\'Hidden items of navigation tree\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__savedsearches`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__savedsearches` ( '
                . '`id` int(5) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db_name` varchar(64) NOT NULL default \'\', `search_name` varchar(64) NOT NULL default \'\','
                . ' `search_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`) )'
                . ' COMMENT=\'Saved searches\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__central_columns`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__central_columns` ( '
                . '`db_name` varchar(64) NOT NULL, `col_name` varchar(64) NOT NULL, `col_type` varchar(64) NOT NULL,'
                . ' `col_length` text, `col_collation` varchar(64) NOT NULL, `col_isNull` boolean NOT NULL,'
                . ' `col_extra` varchar(255) default \'\', `col_default` text,'
                . ' PRIMARY KEY (`db_name`,`col_name`) )'
                . ' COMMENT=\'Central list of columns\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__designer_settings`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__designer_settings` ( '
                . '`username` varchar(64) NOT NULL, `settings_data` text NOT NULL,'
                . ' PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Settings related to Designer\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__export_templates`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__export_templates` ( '
                . '`id` int(5) unsigned NOT NULL AUTO_INCREMENT, `username` varchar(64) NOT NULL,'
                . ' `export_type` varchar(10) NOT NULL, `template_name` varchar(64) NOT NULL,'
                . ' `template_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`) )'
                . ' COMMENT=\'Saved export templates\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            []
        );

        self::assertSame('db_pma', $GLOBALS['cfg']['Server']['pmadb']);

        $_SESSION['relation'] = [];

        $this->dummyDbi->addSelectDb('db_pma');
        $this->dummyDbi->addSelectDb('db_pma');
        $this->relation->fixPmaTables('db_pma', true);
        self::assertArrayNotHasKey('message', $GLOBALS);
        self::assertArrayHasKey($GLOBALS['server'], $_SESSION['relation'], 'The cache is expected to be filled');
        /** @psalm-suppress EmptyArrayAccess */
        self::assertIsArray($_SESSION['relation'][$GLOBALS['server']]);
        self::assertSame('db_pma', $GLOBALS['cfg']['Server']['pmadb']);

        $relationParameters = RelationParameters::fromArray([
            'db' => 'db_pma',
            'userconfigwork' => true,
            'userconfig' => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $_SESSION['relation'][$GLOBALS['server']]);

        $this->assertAllQueriesConsumed();
        $this->assertAllSelectsConsumed();
    }

    public function testFixPmaTablesNormalFixTablesFails(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_db_pma']
        );

        // Fail the query
        $this->dummyDbi->addErrorCode('MYSQL_ERROR');
        $this->dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__bookmark` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__bookmark` ( '
                . '`id` int(10) unsigned NOT NULL auto_increment,'
                . ' `dbase` varchar(255) NOT NULL default \'\','
                . ' `user` varchar(255) NOT NULL default \'\','
                . ' `label` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `query` text NOT NULL, PRIMARY KEY (`id`) )'
                . ' COMMENT=\'Bookmarks\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            false
        );
        $this->dummyDbi->addSelectDb('db_pma');

        self::assertSame('', $GLOBALS['cfg']['Server']['pmadb']);

        $_SESSION['relation'] = [];

        $this->relation->fixPmaTables('db_pma', true);

        self::assertArrayHasKey('message', $GLOBALS);
        self::assertSame('MYSQL_ERROR', $GLOBALS['message']);
        self::assertSame('', $GLOBALS['cfg']['Server']['pmadb']);

        self::assertSame([], $_SESSION['relation']);

        $this->assertAllQueriesConsumed();
        $this->assertAllErrorCodesConsumed();
        $this->assertAllSelectsConsumed();
    }

    public function testCreatePmaDatabase(): void
    {
        parent::setGlobalDbi();
        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'CREATE DATABASE IF NOT EXISTS `phpmyadmin`',
            []
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`',
            []
        );
        $this->dummyDbi->addSelectDb('phpmyadmin');

        self::assertArrayNotHasKey('errno', $GLOBALS);

        self::assertTrue($this->relation->createPmaDatabase('phpmyadmin'));

        self::assertArrayNotHasKey('message', $GLOBALS);

        $this->assertAllQueriesConsumed();
        $this->assertAllErrorCodesConsumed();
        $this->assertAllSelectsConsumed();
    }

    public function testCreatePmaDatabaseFailsError1044(): void
    {
        parent::setGlobalDbi();
        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addErrorCode('MYSQL_ERROR');
        $this->dummyDbi->addResult('CREATE DATABASE IF NOT EXISTS `phpmyadmin`', false);

        $GLOBALS['errno'] = 1044;// ER_DBACCESS_DENIED_ERROR

        self::assertFalse($this->relation->createPmaDatabase('phpmyadmin'));

        self::assertArrayHasKey('message', $GLOBALS);
        self::assertSame('You do not have necessary privileges to create a database named'
        . ' \'phpmyadmin\'. You may go to \'Operations\' tab of any'
        . ' database to set up the phpMyAdmin configuration storage there.', $GLOBALS['message']);

        $this->assertAllQueriesConsumed();
        $this->assertAllErrorCodesConsumed();
    }

    public function testCreatePmaDatabaseFailsError1040(): void
    {
        parent::setGlobalDbi();
        $this->relation = new Relation($this->dbi);

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addErrorCode('Too many connections');
        $this->dummyDbi->addResult('CREATE DATABASE IF NOT EXISTS `pma_1040`', false);

        $GLOBALS['errno'] = 1040;

        self::assertFalse($this->relation->createPmaDatabase('pma_1040'));

        self::assertArrayHasKey('message', $GLOBALS);
        self::assertSame('Too many connections', $GLOBALS['message']);

        $this->assertAllQueriesConsumed();
        $this->assertAllErrorCodesConsumed();
    }

    public function testGetDefaultPmaTableNames(): void
    {
        $this->relation = new Relation($this->dbi);

        $data = [
            'pma__bookmark' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__bookmark`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__bookmark` (',
                '  `id` int(10) unsigned NOT NULL auto_increment,',
                '  `dbase` varchar(255) NOT NULL default \'\',',
                '  `user` varchar(255) NOT NULL default \'\',',
                '  `label` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\',',
                '  `query` text NOT NULL,',
                '  PRIMARY KEY  (`id`)',
                ')',
                '  COMMENT=\'Bookmarks\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__column_info' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__column_info`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__column_info` (',
                '  `id` int(5) unsigned NOT NULL auto_increment,',
                '  `db_name` varchar(64) NOT NULL default \'\',',
                '  `table_name` varchar(64) NOT NULL default \'\',',
                '  `column_name` varchar(64) NOT NULL default \'\',',
                '  `comment` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\',',
                '  `mimetype` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\',',
                '  `transformation` varchar(255) NOT NULL default \'\',',
                '  `transformation_options` varchar(255) NOT NULL default \'\',',
                '  `input_transformation` varchar(255) NOT NULL default \'\',',
                '  `input_transformation_options` varchar(255) NOT NULL default \'\',',
                '  PRIMARY KEY  (`id`),',
                '  UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`)',
                ')',
                '  COMMENT=\'Column information for phpMyAdmin\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__history' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__history`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__history` (',
                '  `id` bigint(20) unsigned NOT NULL auto_increment,',
                '  `username` varchar(64) NOT NULL default \'\',',
                '  `db` varchar(64) NOT NULL default \'\',',
                '  `table` varchar(64) NOT NULL default \'\',',
                '  `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP,',
                '  `sqlquery` text NOT NULL,',
                '  PRIMARY KEY  (`id`),',
                '  KEY `username` (`username`,`db`,`table`,`timevalue`)',
                ')',
                '  COMMENT=\'SQL history for phpMyAdmin\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__pdf_pages' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__pdf_pages`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__pdf_pages` (',
                '  `db_name` varchar(64) NOT NULL default \'\',',
                '  `page_nr` int(10) unsigned NOT NULL auto_increment,',
                '  `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default \'\',',
                '  PRIMARY KEY  (`page_nr`),',
                '  KEY `db_name` (`db_name`)',
                ')',
                '  COMMENT=\'PDF relation pages for phpMyAdmin\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__recent' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__recent`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__recent` (',
                '  `username` varchar(64) NOT NULL,',
                '  `tables` text NOT NULL,',
                '  PRIMARY KEY (`username`)',
                ')',
                '  COMMENT=\'Recently accessed tables\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__favorite' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__favorite`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__favorite` (',
                '  `username` varchar(64) NOT NULL,',
                '  `tables` text NOT NULL,',
                '  PRIMARY KEY (`username`)',
                ')',
                '  COMMENT=\'Favorite tables\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__table_uiprefs' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__table_uiprefs`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` (',
                '  `username` varchar(64) NOT NULL,',
                '  `db_name` varchar(64) NOT NULL,',
                '  `table_name` varchar(64) NOT NULL,',
                '  `prefs` text NOT NULL,',
                '  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
                '  PRIMARY KEY (`username`,`db_name`,`table_name`)',
                ')',
                '  COMMENT=\'Tables\'\' UI preferences\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__relation' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__relation`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__relation` (',
                '  `master_db` varchar(64) NOT NULL default \'\',',
                '  `master_table` varchar(64) NOT NULL default \'\',',
                '  `master_field` varchar(64) NOT NULL default \'\',',
                '  `foreign_db` varchar(64) NOT NULL default \'\',',
                '  `foreign_table` varchar(64) NOT NULL default \'\',',
                '  `foreign_field` varchar(64) NOT NULL default \'\',',
                '  PRIMARY KEY  (`master_db`,`master_table`,`master_field`),',
                '  KEY `foreign_field` (`foreign_db`,`foreign_table`)',
                ')',
                '  COMMENT=\'Relation table\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__table_coords' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__table_coords`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__table_coords` (',
                '  `db_name` varchar(64) NOT NULL default \'\',',
                '  `table_name` varchar(64) NOT NULL default \'\',',
                '  `pdf_page_number` int(11) NOT NULL default \'0\',',
                '  `x` float unsigned NOT NULL default \'0\',',
                '  `y` float unsigned NOT NULL default \'0\',',
                '  PRIMARY KEY  (`db_name`,`table_name`,`pdf_page_number`)',
                ')',
                '  COMMENT=\'Table coordinates for phpMyAdmin PDF output\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__table_info' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__table_info`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__table_info` (',
                '  `db_name` varchar(64) NOT NULL default \'\',',
                '  `table_name` varchar(64) NOT NULL default \'\',',
                '  `display_field` varchar(64) NOT NULL default \'\',',
                '  PRIMARY KEY  (`db_name`,`table_name`)',
                ')',
                '  COMMENT=\'Table information for phpMyAdmin\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__tracking' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__tracking`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__tracking` (',
                '  `db_name` varchar(64) NOT NULL,',
                '  `table_name` varchar(64) NOT NULL,',
                '  `version` int(10) unsigned NOT NULL,',
                '  `date_created` datetime NOT NULL,',
                '  `date_updated` datetime NOT NULL,',
                '  `schema_snapshot` text NOT NULL,',
                '  `schema_sql` text,',
                '  `data_sql` longtext,',
                '  `tracking` set(\'UPDATE\',\'REPLACE\',\'INSERT\',\'DELETE\','
                    . '\'TRUNCATE\',\'CREATE DATABASE\',\'ALTER DATABASE\',\'DROP DATABASE\','
                    . '\'CREATE TABLE\',\'ALTER TABLE\',\'RENAME TABLE\',\'DROP TABLE\','
                    . '\'CREATE INDEX\',\'DROP INDEX\',\'CREATE VIEW\',\'ALTER VIEW\','
                    . '\'DROP VIEW\') default NULL,',
                '  `tracking_active` int(1) unsigned NOT NULL default \'1\',',
                '  PRIMARY KEY  (`db_name`,`table_name`,`version`)',
                ')',
                '  COMMENT=\'Database changes tracking for phpMyAdmin\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__userconfig' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__userconfig`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__userconfig` (',
                '  `username` varchar(64) NOT NULL,',
                '  `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
                '  `config_data` text NOT NULL,',
                '  PRIMARY KEY  (`username`)',
                ')',
                '  COMMENT=\'User preferences storage for phpMyAdmin\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__users' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__users`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__users` (',
                '  `username` varchar(64) NOT NULL,',
                '  `usergroup` varchar(64) NOT NULL,',
                '  PRIMARY KEY (`username`,`usergroup`)',
                ')',
                '  COMMENT=\'Users and their assignments to user groups\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__usergroups' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__usergroups`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__usergroups` (',
                '  `usergroup` varchar(64) NOT NULL,',
                '  `tab` varchar(64) NOT NULL,',
                '  `allowed` enum(\'Y\',\'N\') NOT NULL DEFAULT \'N\',',
                '  PRIMARY KEY (`usergroup`,`tab`,`allowed`)',
                ')',
                '  COMMENT=\'User groups with configured menu items\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__navigationhiding' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__navigationhiding`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__navigationhiding` (',
                '  `username` varchar(64) NOT NULL,',
                '  `item_name` varchar(64) NOT NULL,',
                '  `item_type` varchar(64) NOT NULL,',
                '  `db_name` varchar(64) NOT NULL,',
                '  `table_name` varchar(64) NOT NULL,',
                '  PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`)',
                ')',
                '  COMMENT=\'Hidden items of navigation tree\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__savedsearches' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__savedsearches`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__savedsearches` (',
                '  `id` int(5) unsigned NOT NULL auto_increment,',
                '  `username` varchar(64) NOT NULL default \'\',',
                '  `db_name` varchar(64) NOT NULL default \'\',',
                '  `search_name` varchar(64) NOT NULL default \'\',',
                '  `search_data` text NOT NULL,',
                '  PRIMARY KEY  (`id`),',
                '  UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`)',
                ')',
                '  COMMENT=\'Saved searches\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__central_columns' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__central_columns`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__central_columns` (',
                '  `db_name` varchar(64) NOT NULL,',
                '  `col_name` varchar(64) NOT NULL,',
                '  `col_type` varchar(64) NOT NULL,',
                '  `col_length` text,',
                '  `col_collation` varchar(64) NOT NULL,',
                '  `col_isNull` boolean NOT NULL,',
                '  `col_extra` varchar(255) default \'\',',
                '  `col_default` text,',
                '  PRIMARY KEY (`db_name`,`col_name`)',
                ')',
                '  COMMENT=\'Central list of columns\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__designer_settings' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__designer_settings`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__designer_settings` (',
                '  `username` varchar(64) NOT NULL,',
                '  `settings_data` text NOT NULL,',
                '  PRIMARY KEY (`username`)',
                ')',
                '  COMMENT=\'Settings related to Designer\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
            'pma__export_templates' => implode("\n", [
                '',
                '',
                '-- --------------------------------------------------------',
                '',
                '--',
                '-- Table structure for table `pma__export_templates`',
                '--',
                '',
                'CREATE TABLE IF NOT EXISTS `pma__export_templates` (',
                '  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,',
                '  `username` varchar(64) NOT NULL,',
                '  `export_type` varchar(10) NOT NULL,',
                '  `template_name` varchar(64) NOT NULL,',
                '  `template_data` text NOT NULL,',
                '  PRIMARY KEY (`id`),',
                '  UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`)',
                ')',
                '  COMMENT=\'Saved export templates\'',
                '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            ]),
        ];

        self::assertSame($data, $this->relation->getDefaultPmaTableNames([]));

        $data['pma__export_templates'] = implode("\n", [
            '',
            '',
            '-- --------------------------------------------------------',
            '',
            '--',
            '-- Table structure for table `db_exporttemplates_pma`',
            '--',
            '',
            'CREATE TABLE IF NOT EXISTS `db_exporttemplates_pma` (',
            '  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,',
            '  `username` varchar(64) NOT NULL,',
            '  `export_type` varchar(10) NOT NULL,',
            '  `template_name` varchar(64) NOT NULL,',
            '  `template_data` text NOT NULL,',
            '  PRIMARY KEY (`id`),',
            '  UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`)',
            ')',
            '  COMMENT=\'Saved export templates\'',
            '  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
        ]);

        self::assertSame(
            $data,
            $this->relation->getDefaultPmaTableNames(['pma__export_templates' => 'db_exporttemplates_pma'])
        );
    }

    public function testInitRelationParamsCacheDefaultDbNameDbDoesNotExist(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 0;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult('SHOW TABLES FROM `phpmyadmin`;', false);

        $relation = new Relation($this->dbi);
        $relation->initRelationParamsCache();

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsServerZero(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server'] = [];

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $_SESSION['relation'] = [];

        $relation = new Relation($this->dbi);
        $relation->initRelationParamsCache();

        self::assertArrayHasKey($GLOBALS['server'], $_SESSION['relation'], 'The cache is expected to be filled');
        /** @psalm-suppress EmptyArrayAccess */
        self::assertIsArray($_SESSION['relation'][$GLOBALS['server']]);

        // Should all be false for server = 0
        $relationParameters = RelationParameters::fromArray([]);
        self::assertSame($relationParameters->toArray(), $_SESSION['relation'][$GLOBALS['server']]);

        self::assertSame([
            'userconfig' => 'pma__userconfig',
            'pmadb' => false,// This is the expected value for server = 0
        ], $GLOBALS['cfg']['Server']);
        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServer(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server'] = [];
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $_SESSION['relation'] = [];

        $this->dummyDbi->addSelectDb('phpmyadmin');
        $relation = new Relation($this->dbi);
        $relation->initRelationParamsCache();
        $this->assertAllSelectsConsumed();

        self::assertArrayHasKey($GLOBALS['server'], $_SESSION['relation'], 'The cache is expected to be filled');
        /** @psalm-suppress EmptyArrayAccess */
        self::assertIsArray($_SESSION['relation'][$GLOBALS['server']]);

        // Should all be false for server = 0
        $relationParameters = RelationParameters::fromArray([
            'db' => 'phpmyadmin',
            'userconfigwork' => true,
            'userconfig' => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $_SESSION['relation'][$GLOBALS['server']]);

        self::assertSame([
            'user' => '',
            'pmadb' => 'phpmyadmin',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => '',
            'table_uiprefs' => '',
            'tracking' => '',
            'userconfig' => 'pma__userconfig',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServerNotWorkingTable(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server'] = [];
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`',
            [
                ['pma__userconfig'],
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult('SELECT NULL FROM `pma__userconfig` LIMIT 0', false);

        $_SESSION['relation'] = [];

        $this->dummyDbi->addSelectDb('phpmyadmin');
        $relation = new Relation($this->dbi);
        $relation->initRelationParamsCache();
        $this->assertAllSelectsConsumed();

        self::assertArrayHasKey($GLOBALS['server'], $_SESSION['relation'], 'The cache is expected to be filled');
        /** @psalm-suppress EmptyArrayAccess */
        self::assertIsArray($_SESSION['relation'][$GLOBALS['server']]);

        $relationParameters = RelationParameters::fromArray([
            'db' => 'phpmyadmin',
            'userconfigwork' => false,
            'userconfig' => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $_SESSION['relation'][$GLOBALS['server']]);

        self::assertSame([
            'user' => '',
            'pmadb' => 'phpmyadmin',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => '',
            'table_uiprefs' => '',
            'tracking' => '',
            'userconfig' => 'pma__userconfig',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServerOverride(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server'] = [];
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = 'PMA-storage';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = 'pma__userconfig_custom';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                [
                    'pma__userconfig_custom',
                    'pma__usergroups',
                ],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`',
            [
                [
                    'pma__userconfig_custom',
                    'pma__usergroups',
                ],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig_custom` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $this->dummyDbi->addSelectDb('PMA-storage');

        $_SESSION['relation'] = [];

        $relation = new Relation($this->dbi);
        $relation->initRelationParamsCache();

        self::assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled because the custom override'
        . 'was understood (pma__userconfig vs pma__userconfig_custom)');

        $this->assertAllQueriesConsumed();
        $this->assertAllSelectsConsumed();

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`',
            [
                [
                    'pma__userconfig_custom',
                    'pma__usergroups',
                ],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig_custom` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $this->dummyDbi->addSelectDb('PMA-storage');
        /** @psalm-suppress EmptyArrayAccess */
        unset($_SESSION['relation'][$GLOBALS['server']]);
        $relationData = $relation->getRelationParameters();
        $this->assertAllSelectsConsumed();

        $relationParameters = RelationParameters::fromArray([
            'db' => 'PMA-storage',
            'userconfigwork' => true,
            'userconfig' => 'pma__userconfig_custom',
        ]);
        self::assertSame($relationParameters->toArray(), $relationData->toArray());

        self::assertSame([
            'user' => '',
            'pmadb' => 'PMA-storage',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => '',
            'table_uiprefs' => '',
            'tracking' => '',
            'userconfig' => 'pma__userconfig_custom',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsDisabledTracking(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server'] = [];
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = 'PMA-storage';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = false;
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                ['pma__tracking'],
            ],
            ['Tables_in_PMA-storage']
        );

        $_SESSION['relation'] = [];

        $relation = new Relation($this->dbi);
        $relation->initRelationParamsCache();

        self::assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled because the custom override'
        . 'was understood');

        $this->assertAllQueriesConsumed();
        $this->assertAllSelectsConsumed();

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`',
            [
                [
                    'pma__userconfig_custom',
                    'pma__usergroups',
                ],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addSelectDb('PMA-storage');
        /** @psalm-suppress EmptyArrayAccess */
        unset($_SESSION['relation'][$GLOBALS['server']]);
        $relationData = $relation->getRelationParameters();
        $this->assertAllSelectsConsumed();

        $relationParameters = RelationParameters::fromArray([
            'db' => 'PMA-storage',
            'trackingwork' => false,
            'tracking' => false,
        ]);
        self::assertSame($relationParameters->toArray(), $relationData->toArray());
        self::assertNull($relationParameters->trackingFeature, 'The feature should not be enabled');

        self::assertSame([
            'user' => '',
            'pmadb' => 'PMA-storage',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => '',
            'table_uiprefs' => '',
            'tracking' => false,
            'userconfig' => '',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsDisabledTrackingOthersExist(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['db'] = '';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server'] = [];
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['pmadb'] = 'PMA-storage';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = 'pma__favorite_custom';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = false;
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addSelectDb('PMA-storage');
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                ['pma__favorite_custom'],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`',
            [
                ['pma__favorite_custom'],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__favorite_custom` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        $_SESSION['relation'] = [];
        $_SESSION['tmpval'] = [];
        $recentFavoriteTableInstances = (new ReflectionClass(RecentFavoriteTable::class))->getProperty('instances');
        $recentFavoriteTableInstances->setAccessible(true);
        $recentFavoriteTableInstances->setValue(null, []);

        $relation = new Relation($this->dbi);
        $relation->initRelationParamsCache();

        self::assertArrayHasKey('relation', $_SESSION, 'The cache is expected to be filled because the custom override'
        . 'was understood');

        $this->assertAllQueriesConsumed();
        $this->assertAllSelectsConsumed();

        $this->dummyDbi->addSelectDb('PMA-storage');

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`',
            [
                ['pma__favorite_custom'],
            ],
            ['Tables_in_PMA-storage']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__favorite_custom` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );

        /** @psalm-suppress EmptyArrayAccess */
        unset($_SESSION['relation'][$GLOBALS['server']]);
        $relationData = $relation->getRelationParameters();
        $this->assertAllSelectsConsumed();

        $relationParameters = RelationParameters::fromArray([
            'db' => 'PMA-storage',
            'trackingwork' => false,
            'tracking' => false,
            'favorite' => 'pma__favorite_custom',
            'favoritework' => true,
        ]);
        self::assertSame($relationParameters->toArray(), $relationData->toArray());
        self::assertNull($relationParameters->trackingFeature, 'The feature should not be enabled');

        self::assertSame([
            'user' => '',
            'pmadb' => 'PMA-storage',
            'bookmarktable' => '',
            'relation' => '',
            'table_info' => '',
            'table_coords' => '',
            'column_info' => '',
            'pdf_pages' => '',
            'history' => '',
            'recent' => '',
            'favorite' => 'pma__favorite_custom',
            'table_uiprefs' => '',
            'tracking' => false,
            'userconfig' => '',
            'users' => '',
            'usergroups' => '',
            'navigationhiding' => '',
            'savedsearches' => '',
            'central_columns' => '',
            'designer_settings' => '',
            'export_templates' => '',
        ], $GLOBALS['cfg']['Server']);

        $this->assertAllQueriesConsumed();
    }

    public function testArePmadbTablesDefinedAndArePmadbTablesAllDisabled(): void
    {
        parent::setGlobalDbi();

        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        self::assertFalse($this->relation->arePmadbTablesDefined());
        self::assertFalse($this->relation->arePmadbTablesAllDisabled());

        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = 'pma__favorite_custom';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = false;
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        self::assertFalse($this->relation->arePmadbTablesDefined());
        self::assertFalse($this->relation->arePmadbTablesAllDisabled());

        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma__bookmark';
        $GLOBALS['cfg']['Server']['relation'] = 'pma__relation';
        $GLOBALS['cfg']['Server']['table_info'] = 'pma__table_info';
        $GLOBALS['cfg']['Server']['table_coords'] = 'pma__table_coords';
        $GLOBALS['cfg']['Server']['pdf_pages'] = 'pma__pdf_pages';
        $GLOBALS['cfg']['Server']['column_info'] = 'pma__column_info';
        $GLOBALS['cfg']['Server']['history'] = 'pma__history';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = 'pma__table_uiprefs';
        $GLOBALS['cfg']['Server']['tracking'] = 'pma__tracking';
        $GLOBALS['cfg']['Server']['userconfig'] = 'pma__userconfig';
        $GLOBALS['cfg']['Server']['recent'] = 'pma__recent';
        $GLOBALS['cfg']['Server']['favorite'] = 'pma__favorite';
        $GLOBALS['cfg']['Server']['users'] = 'pma__users';
        $GLOBALS['cfg']['Server']['usergroups'] = 'pma__usergroups';
        $GLOBALS['cfg']['Server']['navigationhiding'] = 'pma__navigationhiding';
        $GLOBALS['cfg']['Server']['savedsearches'] = 'pma__savedsearches';
        $GLOBALS['cfg']['Server']['central_columns'] = 'pma__central_columns';
        $GLOBALS['cfg']['Server']['designer_settings'] = 'pma__designer_settings';
        $GLOBALS['cfg']['Server']['export_templates'] = 'pma__export_templates';

        self::assertTrue($this->relation->arePmadbTablesDefined());
        self::assertFalse($this->relation->arePmadbTablesAllDisabled());

        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma__bookmark';
        $GLOBALS['cfg']['Server']['relation'] = 'pma__relation';
        $GLOBALS['cfg']['Server']['table_info'] = 'pma__table_info';
        $GLOBALS['cfg']['Server']['table_coords'] = 'pma__table_coords';
        $GLOBALS['cfg']['Server']['pdf_pages'] = 'pma__pdf_pages';
        $GLOBALS['cfg']['Server']['column_info'] = 'pma__column_info';
        $GLOBALS['cfg']['Server']['history'] = 'custom_name';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = 'pma__table_uiprefs';
        $GLOBALS['cfg']['Server']['tracking'] = 'pma__tracking';
        $GLOBALS['cfg']['Server']['userconfig'] = 'pma__userconfig';
        $GLOBALS['cfg']['Server']['recent'] = 'pma__recent';
        $GLOBALS['cfg']['Server']['favorite'] = 'pma__favorite';
        $GLOBALS['cfg']['Server']['users'] = 'pma__users';
        $GLOBALS['cfg']['Server']['usergroups'] = 'pma__usergroups';
        $GLOBALS['cfg']['Server']['navigationhiding'] = 'pma__navigationhiding';
        $GLOBALS['cfg']['Server']['savedsearches'] = 'pma__savedsearches';
        $GLOBALS['cfg']['Server']['central_columns'] = 'pma__central_columns';
        $GLOBALS['cfg']['Server']['designer_settings'] = 'pma__designer_settings';
        $GLOBALS['cfg']['Server']['export_templates'] = 'pma__export_templates';

        self::assertTrue($this->relation->arePmadbTablesDefined());
        self::assertFalse($this->relation->arePmadbTablesAllDisabled());

        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma__bookmark';
        $GLOBALS['cfg']['Server']['relation'] = 'pma__relation';
        $GLOBALS['cfg']['Server']['table_info'] = 'pma__table_info';
        $GLOBALS['cfg']['Server']['table_coords'] = 'pma__table_coords';
        $GLOBALS['cfg']['Server']['pdf_pages'] = 'pma__pdf_pages';
        $GLOBALS['cfg']['Server']['column_info'] = 'pma__column_info';
        $GLOBALS['cfg']['Server']['history'] = 'pma__history';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = 'pma__table_uiprefs';
        $GLOBALS['cfg']['Server']['tracking'] = 'pma__tracking';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['recent'] = 'pma__recent';
        $GLOBALS['cfg']['Server']['favorite'] = 'pma__favorite';
        $GLOBALS['cfg']['Server']['users'] = 'pma__users';
        $GLOBALS['cfg']['Server']['usergroups'] = 'pma__usergroups';
        $GLOBALS['cfg']['Server']['navigationhiding'] = 'pma__navigationhiding';
        $GLOBALS['cfg']['Server']['savedsearches'] = 'pma__savedsearches';
        $GLOBALS['cfg']['Server']['central_columns'] = 'pma__central_columns';
        $GLOBALS['cfg']['Server']['designer_settings'] = 'pma__designer_settings';
        $GLOBALS['cfg']['Server']['export_templates'] = 'pma__export_templates';

        self::assertFalse($this->relation->arePmadbTablesDefined());
        self::assertFalse($this->relation->arePmadbTablesAllDisabled());

        $GLOBALS['cfg']['Server']['bookmarktable'] = false; //'pma__bookmark';
        $GLOBALS['cfg']['Server']['relation'] = false; //'pma__relation';
        $GLOBALS['cfg']['Server']['table_info'] = false; //'pma__table_info';
        $GLOBALS['cfg']['Server']['table_coords'] = false; //'pma__table_coords';
        $GLOBALS['cfg']['Server']['pdf_pages'] = false; //'pma__pdf_pages';
        $GLOBALS['cfg']['Server']['column_info'] = false; //'pma__column_info';
        $GLOBALS['cfg']['Server']['history'] = false; //'pma__history';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = false; //'pma__table_uiprefs';
        $GLOBALS['cfg']['Server']['tracking'] = false; //'pma__tracking';
        $GLOBALS['cfg']['Server']['userconfig'] = false; //'pma__userconfig';
        $GLOBALS['cfg']['Server']['recent'] = false; //'pma__recent';
        $GLOBALS['cfg']['Server']['favorite'] = false; //'pma__favorite';
        $GLOBALS['cfg']['Server']['users'] = false; //'pma__users';
        $GLOBALS['cfg']['Server']['usergroups'] = false; //'pma__usergroups';
        $GLOBALS['cfg']['Server']['navigationhiding'] = false; //'pma__navigationhiding';
        $GLOBALS['cfg']['Server']['savedsearches'] = false; //'pma__savedsearches';
        $GLOBALS['cfg']['Server']['central_columns'] = false; //'pma__central_columns';
        $GLOBALS['cfg']['Server']['designer_settings'] = false; //'pma__designer_settings';
        $GLOBALS['cfg']['Server']['export_templates'] = false; //'pma__export_templates';

        self::assertFalse($this->relation->arePmadbTablesDefined());
        self::assertTrue($this->relation->arePmadbTablesAllDisabled());
    }

    /**
     * @param array<string, bool|string> $params
     * @param string[]                   $queries
     *
     * @dataProvider providerForTestRenameTable
     */
    public function testRenameTable(array $params, array $queries): void
    {
        $GLOBALS['server'] = 1;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray($params)->toArray();

        foreach ($queries as $query) {
            $this->dummyDbi->addResult($query, []);
        }

        $this->relation->renameTable('db_1', 'db_2', 'table_1', 'table_2');

        $this->assertAllQueriesConsumed();
    }

    /**
     * @return array<int, array<int, array<int|string, bool|string>>>
     * @psalm-return list<array{array<string, bool|string>, string[]}>
     */
    public static function providerForTestRenameTable(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            [
                ['user' => 'user', 'db' => 'pmadb', 'commwork' => true, 'column_info' => 'column_info'],
                ['UPDATE `pmadb`.`column_info` SET db_name = \'db_2\', table_name = \'table_2\' WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                ['user' => 'user', 'db' => 'pmadb', 'displaywork' => true, 'relation' => 'relation', 'table_info' => 'table_info'],
                ['UPDATE `pmadb`.`table_info` SET db_name = \'db_2\', table_name = \'table_2\' WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                ['user' => 'user', 'db' => 'pmadb', 'relwork' => true, 'relation' => 'relation'],
                [
                    'UPDATE `pmadb`.`relation` SET foreign_db = \'db_2\', foreign_table = \'table_2\' WHERE foreign_db = \'db_1\' AND foreign_table = \'table_1\'',
                    'UPDATE `pmadb`.`relation` SET master_db = \'db_2\', master_table = \'table_2\' WHERE master_db = \'db_1\' AND master_table = \'table_1\'',
                ],
            ],
            [
                ['user' => 'user', 'db' => 'pmadb', 'pdfwork' => true, 'pdf_pages' => 'pdf_pages', 'table_coords' => 'table_coords'],
                ['DELETE FROM `pmadb`.`table_coords` WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                ['user' => 'user', 'db' => 'pmadb', 'uiprefswork' => true, 'table_uiprefs' => 'table_uiprefs'],
                ['UPDATE `pmadb`.`table_uiprefs` SET db_name = \'db_2\', table_name = \'table_2\' WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                ['user' => 'user', 'db' => 'pmadb', 'navwork' => true, 'navigationhiding' => 'navigationhiding'],
                [
                    'UPDATE `pmadb`.`navigationhiding` SET db_name = \'db_2\', table_name = \'table_2\' WHERE db_name = \'db_1\' AND table_name = \'table_1\'',
                    'UPDATE `pmadb`.`navigationhiding` SET db_name = \'db_2\', item_name = \'table_2\' WHERE db_name = \'db_1\' AND item_name = \'table_1\' AND item_type = \'table\'',
                ],
            ],
        ];
        // phpcs:enable
    }

    public function testRenameTableEscaping(): void
    {
        $GLOBALS['server'] = 1;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'user' => 'user',
            'db' => 'pma`db',
            'pdfwork' => true,
            'pdf_pages' => 'pdf_pages',
            'table_coords' => 'table`coords',
        ])->toArray();

        $this->dummyDbi->addResult(
            'UPDATE `pma``db`.`table``coords` SET db_name = \'db\\\'1\', table_name = \'table\\\'2\''
                . ' WHERE db_name = \'db\\\'1\' AND table_name = \'table\\\'1\'',
            []
        );

        $this->relation->renameTable('db\'1', 'db\'1', 'table\'1', 'table\'2');

        $this->assertAllQueriesConsumed();
    }
}
