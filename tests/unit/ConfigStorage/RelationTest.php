<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Foreigners;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Message;
use PhpMyAdmin\SqlParser\Utils\ForeignKey;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function implode;

#[CoversClass(Relation::class)]
#[Medium]
class RelationTest extends AbstractTestCase
{
    /**
     * Test for getDisplayField
     */
    public function testPMAGetDisplayField(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'root';
        $config->selectedServer['pmadb'] = 'phpmyadmin';
        $config->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->addSelectDb('phpmyadmin');
        $db = 'information_schema';
        $table = 'CHARACTER_SETS';
        self::assertSame(
            'DESCRIPTION',
            $relation->getDisplayField($db, $table),
        );
        $dummyDbi->assertAllSelectsConsumed();

        $db = 'information_schema';
        $table = 'TABLES';
        self::assertSame(
            'TABLE_COMMENT',
            $relation->getDisplayField($db, $table),
        );

        $dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'information_schema\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'PMA\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [],
        );

        $db = 'information_schema';
        $table = 'PMA';
        self::assertSame(
            '',
            $relation->getDisplayField($db, $table),
        );

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::RELATION => 'relation',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $dummyDbi->addResult(
            'SELECT `display_field` FROM `pmadb`.`table_info`'
            . ' WHERE `db_name` = \'information_schema\' AND `table_name` = \'PMA\'',
            [['TABLE_COMMENT']],
            ['display_field'],
        );
        $db = 'information_schema';
        $table = 'PMA';
        self::assertSame(
            'TABLE_COMMENT',
            $relation->getDisplayField($db, $table),
        );

        $dummyDbi->addResult(
            'SELECT `display_field` FROM `pmadb`.`table_info`'
            . ' WHERE `db_name` = \'information_schema\' AND `table_name` = \'NON_EXISTING_TABLE\'',
            [],
        );
        $dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'information_schema\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'NON_EXISTING_TABLE\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [],
        );
        $db = 'information_schema';
        $table = 'NON_EXISTING_TABLE';
        self::assertSame(
            '',
            $relation->getDisplayField($db, $table),
        );

        $dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Test for getComments
     */
    public function testPMAGetComments(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['ServerDefault'] = 0;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $getColumnsResult = [
            new Column('field1', 'int(11)', null, false, '', null, '', '', 'Comment1'),
            new Column('field2', 'text', null, false, '', null, '', '', 'Comment1'),
        ];
        $dbi->expects(self::any())->method('getColumns')
            ->willReturn($getColumnsResult);

        $relation = new Relation($dbi);

        DatabaseInterface::$instance = $dbi;

        $db = 'information_schema';
        self::assertSame(
            [''],
            $relation->getComments($db),
        );

        $db = 'information_schema';
        $table = 'TABLES';
        self::assertSame(
            ['field1' => 'Comment1', 'field2' => 'Comment1'],
            $relation->getComments($db, $table),
        );
    }

    /**
     * Test for tryUpgradeTransformations
     */
    public function testPMATryUpgradeTransformations(): void
    {
        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::any())
            ->method('tryQueryAsControlUser')
            ->willReturn($resultStub);
        $resultStub->expects(self::any())
            ->method('numRows')
            ->willReturn(0);
        $dbi->expects(self::any())
            ->method('getError')
            ->willReturn('Error', '');
        DatabaseInterface::$instance = $dbi;

        $relation = new Relation($dbi);

        $config = Config::getInstance();
        $config->selectedServer['pmadb'] = 'pmadb';
        $config->selectedServer['column_info'] = 'column_info';

        // Case 1
        $actual = $relation->tryUpgradeTransformations();
        self::assertFalse($actual);

        // Case 2
        $actual = $relation->tryUpgradeTransformations();
        self::assertTrue($actual);
    }

    public function testSearchColumnInForeignersError(): void
    {
        $relation = new Relation($this->createDatabaseInterface());
        self::assertFalse($relation->searchColumnInForeigners(new Foreigners(), 'id'));
    }

    /**
     * Test for searchColumnInForeigners
     */
    public function testPMASearchColumnInForeigners(): void
    {
        $foreignerKey = new ForeignKey('ad', ['id', 'value']);
        $foreignerKey->refDbName = 'GSoC14';
        $foreignerKey->refTableName = 'table_1';
        $foreignerKey->refIndexList = ['id', 'value'];
        $foreignerKey->onDelete = 'CASCADE';
        $foreignerKey->onUpdate = 'CASCADE';

        $foreigners = [
            'value' => [
                'master_field' => 'value',
                'foreign_db' => 'GSoC14',
                'foreign_table' => 'test',
                'foreign_field' => 'value',
            ],
        ];
        $foreigners = new Foreigners($foreigners, [$foreignerKey]);

        $relation = new Relation($this->createDatabaseInterface());

        $foreigner = $relation->searchColumnInForeigners($foreigners, 'id');
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
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult('SHOW TABLES FROM `db_pma`;', false);

        $relation->fixPmaTables('db_pma', false);
        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testFixPmaTablesNormal(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [['pma__userconfig']],
            ['Tables_in_db_pma'],
        );

        $dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [['pma__userconfig']],
            ['Tables_in_db_pma'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__userconfig` LIMIT 0', []);
        $dummyDbi->addSelectDb('db_pma');

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $relation->fixPmaTables('db_pma', false);

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db_pma',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $relation->getRelationParameters()->toArray());

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();
    }

    public function testFixPmaTablesNormalFixTables(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [['pma__userconfig']],
            ['Tables_in_db_pma'],
        );

        $dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [['pma__userconfig']],
            ['Tables_in_db_pma'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__userconfig` LIMIT 0', []);
        $dummyDbi->addSelectDb('db_pma');
        $dummyDbi->addSelectDb('db_pma');

        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__bookmark` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__bookmark` ( '
                . '`id` int(10) unsigned NOT NULL auto_increment,'
                . ' `dbase` varchar(255) NOT NULL default \'\','
                . ' `user` varchar(255) NOT NULL default \'\','
                . ' `label` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `query` text NOT NULL, PRIMARY KEY (`id`) )'
                . ' COMMENT=\'Bookmarks\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
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
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_info`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_info` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `display_field` varchar(64) NOT NULL default \'\', PRIMARY KEY (`db_name`,`table_name`) )'
                . ' COMMENT=\'Table information for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );

        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_coords`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_coords` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `pdf_page_number` int(11) NOT NULL default \'0\', `x` float unsigned NOT NULL default \'0\','
                . ' `y` float unsigned NOT NULL default \'0\','
                . ' PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`) )'
                . ' COMMENT=\'Table coordinates for phpMyAdmin PDF output\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__pdf_pages`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__pdf_pages` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `page_nr` int(10) unsigned NOT NULL auto_increment,'
                . ' `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default \'\', PRIMARY KEY (`page_nr`),'
                . ' KEY `db_name` (`db_name`) ) COMMENT=\'PDF relation pages for phpMyAdmin\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
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
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__history` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__history` ( '
                . '`id` bigint(20) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db` varchar(64) NOT NULL default \'\', `table` varchar(64) NOT NULL default \'\','
                . ' `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP, `sqlquery` text NOT NULL,'
                . ' PRIMARY KEY (`id`), KEY `username` (`username`,`db`,`table`,`timevalue`) )'
                . ' COMMENT=\'SQL history for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__recent` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__recent` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Recently accessed tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__favorite` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__favorite` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Favorite tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_uiprefs`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` ( '
                . '`username` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL, `prefs` text NOT NULL,'
                . ' `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
                . ' PRIMARY KEY (`username`,`db_name`,`table_name`) ) COMMENT=\'Tables\'\' UI preferences\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
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
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__users` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__users` ( '
                . '`username` varchar(64) NOT NULL, `usergroup` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`usergroup`) )'
                . ' COMMENT=\'Users and their assignments to user groups\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__usergroups`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__usergroups` ( '
                . '`usergroup` varchar(64) NOT NULL, `tab` varchar(64) NOT NULL,'
                . ' `allowed` enum(\'Y\',\'N\') NOT NULL DEFAULT \'N\','
                . ' PRIMARY KEY (`usergroup`,`tab`,`allowed`) )'
                . ' COMMENT=\'User groups with configured menu items\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__navigationhiding`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__navigationhiding` ( '
                . '`username` varchar(64) NOT NULL, `item_name` varchar(64)'
                . ' NOT NULL, `item_type` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`) )'
                . ' COMMENT=\'Hidden items of navigation tree\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__savedsearches`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__savedsearches` ( '
                . '`id` int(5) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db_name` varchar(64) NOT NULL default \'\', `search_name` varchar(64) NOT NULL default \'\','
                . ' `search_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`) )'
                . ' COMMENT=\'Saved searches\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__central_columns`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__central_columns` ( '
                . '`db_name` varchar(64) NOT NULL, `col_name` varchar(64) NOT NULL, `col_type` varchar(64) NOT NULL,'
                . ' `col_length` text, `col_collation` varchar(64) NOT NULL, `col_isNull` boolean NOT NULL,'
                . ' `col_extra` varchar(255) default \'\', `col_default` text,'
                . ' PRIMARY KEY (`db_name`,`col_name`) )'
                . ' COMMENT=\'Central list of columns\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__designer_settings`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__designer_settings` ( '
                . '`username` varchar(64) NOT NULL, `settings_data` text NOT NULL,'
                . ' PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Settings related to Designer\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__export_templates`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__export_templates` ( '
                . '`id` int(5) unsigned NOT NULL AUTO_INCREMENT, `username` varchar(64) NOT NULL,'
                . ' `export_type` varchar(10) NOT NULL, `template_name` varchar(64) NOT NULL,'
                . ' `template_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`) )'
                . ' COMMENT=\'Saved export templates\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );

        self::assertSame('', $config->selectedServer['pmadb']);

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $relation->fixPmaTables('db_pma', true);
        self::assertSame('db_pma', $config->selectedServer['pmadb']);

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db_pma',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $relation->getRelationParameters()->toArray());

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();
    }

    public function testFixPmaTablesNormalFixTablesWithCustomOverride(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = 'db_pma';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = 'custom_relation_pma';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [
                ['pma__userconfig'],
                // This is important as it tricks default existing table detection
                // If the check does not consider the custom name it will skip the table
                ['pma__relation'],
            ],
            ['Tables_in_db_pma'],
        );

        $dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [
                ['pma__userconfig'],
                // This is important as it tricks default existing table detection
                // If the check does not consider the custom name it will skip the table
                ['pma__relation'],
            ],
            ['Tables_in_db_pma'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__userconfig` LIMIT 0', []);

        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__bookmark` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__bookmark` ( '
                . '`id` int(10) unsigned NOT NULL auto_increment,'
                . ' `dbase` varchar(255) NOT NULL default \'\','
                . ' `user` varchar(255) NOT NULL default \'\','
                . ' `label` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `query` text NOT NULL, PRIMARY KEY (`id`) )'
                . ' COMMENT=\'Bookmarks\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
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
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_info`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_info` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `display_field` varchar(64) NOT NULL default \'\', PRIMARY KEY (`db_name`,`table_name`) )'
                . ' COMMENT=\'Table information for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );

        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_coords`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_coords` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `table_name` varchar(64) NOT NULL default \'\','
                . ' `pdf_page_number` int(11) NOT NULL default \'0\', `x` float unsigned NOT NULL default \'0\','
                . ' `y` float unsigned NOT NULL default \'0\','
                . ' PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`) )'
                . ' COMMENT=\'Table coordinates for phpMyAdmin PDF output\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__pdf_pages`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__pdf_pages` ( '
                . '`db_name` varchar(64) NOT NULL default \'\', `page_nr` int(10) unsigned NOT NULL auto_increment,'
                . ' `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default \'\', PRIMARY KEY (`page_nr`),'
                . ' KEY `db_name` (`db_name`) ) COMMENT=\'PDF relation pages for phpMyAdmin\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
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
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__history` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__history` ( '
                . '`id` bigint(20) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db` varchar(64) NOT NULL default \'\', `table` varchar(64) NOT NULL default \'\','
                . ' `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP, `sqlquery` text NOT NULL,'
                . ' PRIMARY KEY (`id`), KEY `username` (`username`,`db`,`table`,`timevalue`) )'
                . ' COMMENT=\'SQL history for phpMyAdmin\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__recent` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__recent` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Recently accessed tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__favorite` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__favorite` ( '
                . '`username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Favorite tables\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__table_uiprefs`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` ( '
                . '`username` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL, `prefs` text NOT NULL,'
                . ' `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
                . ' PRIMARY KEY (`username`,`db_name`,`table_name`) ) COMMENT=\'Tables\'\' UI preferences\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
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
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__users` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__users` ( '
                . '`username` varchar(64) NOT NULL, `usergroup` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`usergroup`) )'
                . ' COMMENT=\'Users and their assignments to user groups\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__usergroups`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__usergroups` ( '
                . '`usergroup` varchar(64) NOT NULL, `tab` varchar(64) NOT NULL,'
                . ' `allowed` enum(\'Y\',\'N\') NOT NULL DEFAULT \'N\','
                . ' PRIMARY KEY (`usergroup`,`tab`,`allowed`) )'
                . ' COMMENT=\'User groups with configured menu items\''
                . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__navigationhiding`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__navigationhiding` ( '
                . '`username` varchar(64) NOT NULL, `item_name` varchar(64)'
                . ' NOT NULL, `item_type` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL,'
                . ' `table_name` varchar(64) NOT NULL,'
                . ' PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`) )'
                . ' COMMENT=\'Hidden items of navigation tree\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__savedsearches`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__savedsearches` ( '
                . '`id` int(5) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default \'\','
                . ' `db_name` varchar(64) NOT NULL default \'\', `search_name` varchar(64) NOT NULL default \'\','
                . ' `search_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`) )'
                . ' COMMENT=\'Saved searches\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__central_columns`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__central_columns` ( '
                . '`db_name` varchar(64) NOT NULL, `col_name` varchar(64) NOT NULL, `col_type` varchar(64) NOT NULL,'
                . ' `col_length` text, `col_collation` varchar(64) NOT NULL, `col_isNull` boolean NOT NULL,'
                . ' `col_extra` varchar(255) default \'\', `col_default` text,'
                . ' PRIMARY KEY (`db_name`,`col_name`) )'
                . ' COMMENT=\'Central list of columns\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__designer_settings`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__designer_settings` ( '
                . '`username` varchar(64) NOT NULL, `settings_data` text NOT NULL,'
                . ' PRIMARY KEY (`username`) )'
                . ' COMMENT=\'Settings related to Designer\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__export_templates`'
            . ' -- CREATE TABLE IF NOT EXISTS `pma__export_templates` ( '
                . '`id` int(5) unsigned NOT NULL AUTO_INCREMENT, `username` varchar(64) NOT NULL,'
                . ' `export_type` varchar(10) NOT NULL, `template_name` varchar(64) NOT NULL,'
                . ' `template_data` text NOT NULL, PRIMARY KEY (`id`),'
                . ' UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`) )'
                . ' COMMENT=\'Saved export templates\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            true,
        );

        self::assertSame('db_pma', $config->selectedServer['pmadb']);

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $dummyDbi->addSelectDb('db_pma');
        $dummyDbi->addSelectDb('db_pma');
        $relation->fixPmaTables('db_pma', true);
        self::assertSame('db_pma', $config->selectedServer['pmadb']);

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db_pma',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $relation->getRelationParameters()->toArray());

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();
    }

    public function testFixPmaTablesNormalFixTablesFails(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `db_pma`;',
            [['pma__userconfig']],
            ['Tables_in_db_pma'],
        );

        // Fail the query
        $dummyDbi->addErrorCode('MYSQL_ERROR');
        $dummyDbi->addResult(
            '-- -------------------------------------------------------- -- --'
            . ' Table structure for table `pma__bookmark` '
            . '-- CREATE TABLE IF NOT EXISTS `pma__bookmark` ( '
                . '`id` int(10) unsigned NOT NULL auto_increment,'
                . ' `dbase` varchar(255) NOT NULL default \'\','
                . ' `user` varchar(255) NOT NULL default \'\','
                . ' `label` varchar(255) COLLATE utf8_general_ci NOT NULL default \'\','
                . ' `query` text NOT NULL, PRIMARY KEY (`id`) )'
                . ' COMMENT=\'Bookmarks\' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;',
            false,
        );
        $dummyDbi->addSelectDb('db_pma');

        self::assertSame('', $config->selectedServer['pmadb']);

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $relation->fixPmaTables('db_pma', true);

        self::assertEquals(Message::error('MYSQL_ERROR'), Current::$message);
        self::assertSame('', $config->selectedServer['pmadb']);

        self::assertNull((new ReflectionProperty(Relation::class, 'cache'))->getValue());

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllErrorCodesConsumed();
        $dummyDbi->assertAllSelectsConsumed();
    }

    public function testCreatePmaDatabase(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'root';
        $config->selectedServer['pmadb'] = 'phpmyadmin';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult('CREATE DATABASE IF NOT EXISTS `phpmyadmin`', true);

        $dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [],
        );
        $dummyDbi->addSelectDb('phpmyadmin');

        self::assertTrue(
            $relation->createPmaDatabase('phpmyadmin'),
        );

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllErrorCodesConsumed();
        $dummyDbi->assertAllSelectsConsumed();
    }

    public function testCreatePmaDatabaseFailsError1044(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addErrorCode('MYSQL_ERROR');
        $dummyDbi->addResult('CREATE DATABASE IF NOT EXISTS `phpmyadmin`', false);

        DatabaseInterface::$errorNumber = 1044;// ER_DBACCESS_DENIED_ERROR

        self::assertFalse(
            $relation->createPmaDatabase('phpmyadmin'),
        );

        self::assertEquals(
            Message::error('You do not have necessary privileges to create a database named'
            . ' \'phpmyadmin\'. You may go to \'Operations\' tab of any'
            . ' database to set up the phpMyAdmin configuration storage there.'),
            Current::$message,
        );

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllErrorCodesConsumed();
    }

    public function testCreatePmaDatabaseFailsError1040(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addErrorCode('Too many connections');
        $dummyDbi->addResult('CREATE DATABASE IF NOT EXISTS `pma_1040`', false);

        DatabaseInterface::$errorNumber = 1040;

        self::assertFalse(
            $relation->createPmaDatabase('pma_1040'),
        );

        self::assertEquals(Message::error('Too many connections'), Current::$message);

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllErrorCodesConsumed();
    }

    public function testGetDefaultPmaTableNames(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

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

        self::assertSame(
            $data,
            $relation->getCreateTableSqlQueries([]),
        );

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
            $relation->getCreateTableSqlQueries(['pma__export_templates' => 'db_exporttemplates_pma']),
        );
    }

    public function testInitRelationParamsCacheDefaultDbNameDbDoesNotExist(): void
    {
        Current::$database = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult('SHOW TABLES FROM `phpmyadmin`;', false);

        $relation = new Relation($dbi);
        $relation->initRelationParamsCache();

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsServerZero(): void
    {
        Current::$database = '';
        Current::$server = 0;
        $config = Config::getInstance();
        $config->selectedServer = [];

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [['pma__userconfig']],
            ['Tables_in_phpmyadmin'],
        );

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $relation = new Relation($dbi);
        $relation->initRelationParamsCache();

        // Should all be false for server = 0
        $relationParameters = RelationParameters::fromArray([]);
        self::assertSame($relationParameters->toArray(), $relation->getRelationParameters()->toArray());

        self::assertEquals([
            'userconfig' => 'pma__userconfig',
            'pmadb' => false,// This is the expected value for server = 0
        ], $config->selectedServer);
        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServer(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer = [];
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [['pma__userconfig']],
            ['Tables_in_phpmyadmin'],
        );

        $dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [['pma__userconfig']],
            ['Tables_in_phpmyadmin'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__userconfig` LIMIT 0', [], ['NULL']);

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $dummyDbi->addSelectDb('phpmyadmin');
        $relation = new Relation($dbi);
        $relation->initRelationParamsCache();
        $dummyDbi->assertAllSelectsConsumed();

        // Should all be false for server = 0
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $relation->getRelationParameters()->toArray());

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
        ], $config->selectedServer);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServerNotWorkingTable(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer = [];
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [['pma__userconfig']],
            ['Tables_in_phpmyadmin'],
        );

        $dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [['pma__userconfig']],
            ['Tables_in_phpmyadmin'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__userconfig` LIMIT 0', false);

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $dummyDbi->addSelectDb('phpmyadmin');
        $relation = new Relation($dbi);
        $relation->initRelationParamsCache();
        $dummyDbi->assertAllSelectsConsumed();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::USER_CONFIG_WORK => false,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        self::assertSame($relationParameters->toArray(), $relation->getRelationParameters()->toArray());

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
        ], $config->selectedServer);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsCacheDefaultDbNameDbExistsFirstServerOverride(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer = [];
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = 'PMA-storage';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = 'pma__userconfig_custom';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [['pma__userconfig_custom', 'pma__usergroups']],
            ['Tables_in_PMA-storage'],
        );

        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [['pma__userconfig_custom', 'pma__usergroups']],
            ['Tables_in_PMA-storage'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__userconfig_custom` LIMIT 0', [], ['NULL']);

        $dummyDbi->addSelectDb('PMA-storage');

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $relation = new Relation($dbi);
        $relation->initRelationParamsCache();

        self::assertSame(
            'pma__userconfig_custom',
            $relation->getRelationParameters()->toArray()[RelationParameters::USER_CONFIG],
            'The cache is expected to be filled because the custom override'
            . 'was understood (pma__userconfig vs pma__userconfig_custom)',
        );

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();

        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [['pma__userconfig_custom', 'pma__usergroups']],
            ['Tables_in_PMA-storage'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__userconfig_custom` LIMIT 0', [], ['NULL']);

        $dummyDbi->addSelectDb('PMA-storage');
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $relationData = $relation->getRelationParameters();
        $dummyDbi->assertAllSelectsConsumed();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'PMA-storage',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig_custom',
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
        ], $config->selectedServer);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsDisabledTracking(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer = [];
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = 'PMA-storage';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = false;
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                ['pma__tracking'],
            ],
            ['Tables_in_PMA-storage'],
        );
        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                ['pma__tracking'],
            ],
            ['Tables_in_PMA-storage'],
        );

        $dummyDbi->addSelectDb('PMA-storage');

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $relation = new Relation($dbi);
        $relation->initRelationParamsCache();

        self::assertNull(
            $relation->getRelationParameters()->toArray()[RelationParameters::TRACKING],
            'The cache is expected to be filled because the custom override'
            . 'was understood',
        );

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();

        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                [
                    'pma__userconfig_custom',
                    'pma__usergroups',
                ],
            ],
            ['Tables_in_PMA-storage'],
        );

        $dummyDbi->addSelectDb('PMA-storage');
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $relationData = $relation->getRelationParameters();
        $dummyDbi->assertAllSelectsConsumed();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'PMA-storage',
            RelationParameters::TRACKING_WORK => false,
            RelationParameters::TRACKING => false,
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
        ], $config->selectedServer);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testInitRelationParamsDisabledTrackingOthersExist(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->selectedServer = [];
        $config->selectedServer['user'] = '';
        $config->selectedServer['pmadb'] = 'PMA-storage';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = 'pma__favorite_custom';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = false;
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('PMA-storage');
        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                ['pma__favorite_custom'],
            ],
            ['Tables_in_PMA-storage'],
        );

        $dummyDbi->addResult(
            'SHOW TABLES FROM `PMA-storage`;',
            [
                ['pma__favorite_custom'],
            ],
            ['Tables_in_PMA-storage'],
        );

        $dummyDbi->addResult('SELECT NULL FROM `pma__favorite_custom` LIMIT 0', [], ['NULL']);

        $_SESSION['tmpval'] = [];
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        (new ReflectionProperty(RecentFavoriteTables::class, 'instances'))->setValue(null, []);

        $relation = new Relation($dbi);
        $relation->initRelationParamsCache();

        self::assertSame(
            'pma__favorite_custom',
            $relation->getRelationParameters()->toArray()[RelationParameters::FAVORITE],
            'The cache is expected to be filled because the custom override'
            . 'was understood',
        );

        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();

        $relationData = $relation->getRelationParameters();
        $dummyDbi->assertAllSelectsConsumed();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'PMA-storage',
            RelationParameters::TRACKING_WORK => false,
            RelationParameters::TRACKING => false,
            RelationParameters::FAVORITE => 'pma__favorite_custom',
            RelationParameters::FAVORITE_WORK => true,
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
        ], $config->selectedServer);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testArePmadbTablesDefinedAndArePmadbTablesAllDisabled(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $relation = new Relation($dbi);

        self::assertFalse($relation->arePmadbTablesDefined());
        self::assertFalse($relation->arePmadbTablesAllDisabled());

        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = 'pma__favorite_custom';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = false;
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        self::assertFalse($relation->arePmadbTablesDefined());
        self::assertFalse($relation->arePmadbTablesAllDisabled());

        $config->selectedServer['bookmarktable'] = 'pma__bookmark';
        $config->selectedServer['relation'] = 'pma__relation';
        $config->selectedServer['table_info'] = 'pma__table_info';
        $config->selectedServer['table_coords'] = 'pma__table_coords';
        $config->selectedServer['pdf_pages'] = 'pma__pdf_pages';
        $config->selectedServer['column_info'] = 'pma__column_info';
        $config->selectedServer['history'] = 'pma__history';
        $config->selectedServer['table_uiprefs'] = 'pma__table_uiprefs';
        $config->selectedServer['tracking'] = 'pma__tracking';
        $config->selectedServer['userconfig'] = 'pma__userconfig';
        $config->selectedServer['recent'] = 'pma__recent';
        $config->selectedServer['favorite'] = 'pma__favorite';
        $config->selectedServer['users'] = 'pma__users';
        $config->selectedServer['usergroups'] = 'pma__usergroups';
        $config->selectedServer['navigationhiding'] = 'pma__navigationhiding';
        $config->selectedServer['savedsearches'] = 'pma__savedsearches';
        $config->selectedServer['central_columns'] = 'pma__central_columns';
        $config->selectedServer['designer_settings'] = 'pma__designer_settings';
        $config->selectedServer['export_templates'] = 'pma__export_templates';

        self::assertTrue($relation->arePmadbTablesDefined());
        self::assertFalse($relation->arePmadbTablesAllDisabled());

        $config->selectedServer['bookmarktable'] = 'pma__bookmark';
        $config->selectedServer['relation'] = 'pma__relation';
        $config->selectedServer['table_info'] = 'pma__table_info';
        $config->selectedServer['table_coords'] = 'pma__table_coords';
        $config->selectedServer['pdf_pages'] = 'pma__pdf_pages';
        $config->selectedServer['column_info'] = 'pma__column_info';
        $config->selectedServer['history'] = 'custom_name';
        $config->selectedServer['table_uiprefs'] = 'pma__table_uiprefs';
        $config->selectedServer['tracking'] = 'pma__tracking';
        $config->selectedServer['userconfig'] = 'pma__userconfig';
        $config->selectedServer['recent'] = 'pma__recent';
        $config->selectedServer['favorite'] = 'pma__favorite';
        $config->selectedServer['users'] = 'pma__users';
        $config->selectedServer['usergroups'] = 'pma__usergroups';
        $config->selectedServer['navigationhiding'] = 'pma__navigationhiding';
        $config->selectedServer['savedsearches'] = 'pma__savedsearches';
        $config->selectedServer['central_columns'] = 'pma__central_columns';
        $config->selectedServer['designer_settings'] = 'pma__designer_settings';
        $config->selectedServer['export_templates'] = 'pma__export_templates';

        self::assertTrue($relation->arePmadbTablesDefined());
        self::assertFalse($relation->arePmadbTablesAllDisabled());

        $config->selectedServer['bookmarktable'] = 'pma__bookmark';
        $config->selectedServer['relation'] = 'pma__relation';
        $config->selectedServer['table_info'] = 'pma__table_info';
        $config->selectedServer['table_coords'] = 'pma__table_coords';
        $config->selectedServer['pdf_pages'] = 'pma__pdf_pages';
        $config->selectedServer['column_info'] = 'pma__column_info';
        $config->selectedServer['history'] = 'pma__history';
        $config->selectedServer['table_uiprefs'] = 'pma__table_uiprefs';
        $config->selectedServer['tracking'] = 'pma__tracking';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['recent'] = 'pma__recent';
        $config->selectedServer['favorite'] = 'pma__favorite';
        $config->selectedServer['users'] = 'pma__users';
        $config->selectedServer['usergroups'] = 'pma__usergroups';
        $config->selectedServer['navigationhiding'] = 'pma__navigationhiding';
        $config->selectedServer['savedsearches'] = 'pma__savedsearches';
        $config->selectedServer['central_columns'] = 'pma__central_columns';
        $config->selectedServer['designer_settings'] = 'pma__designer_settings';
        $config->selectedServer['export_templates'] = 'pma__export_templates';

        self::assertFalse($relation->arePmadbTablesDefined());
        self::assertFalse($relation->arePmadbTablesAllDisabled());

        $config->selectedServer['bookmarktable'] = false; //'pma__bookmark';
        $config->selectedServer['relation'] = false; //'pma__relation';
        $config->selectedServer['table_info'] = false; //'pma__table_info';
        $config->selectedServer['table_coords'] = false; //'pma__table_coords';
        $config->selectedServer['pdf_pages'] = false; //'pma__pdf_pages';
        $config->selectedServer['column_info'] = false; //'pma__column_info';
        $config->selectedServer['history'] = false; //'pma__history';
        $config->selectedServer['table_uiprefs'] = false; //'pma__table_uiprefs';
        $config->selectedServer['tracking'] = false; //'pma__tracking';
        $config->selectedServer['userconfig'] = false; //'pma__userconfig';
        $config->selectedServer['recent'] = false; //'pma__recent';
        $config->selectedServer['favorite'] = false; //'pma__favorite';
        $config->selectedServer['users'] = false; //'pma__users';
        $config->selectedServer['usergroups'] = false; //'pma__usergroups';
        $config->selectedServer['navigationhiding'] = false; //'pma__navigationhiding';
        $config->selectedServer['savedsearches'] = false; //'pma__savedsearches';
        $config->selectedServer['central_columns'] = false; //'pma__central_columns';
        $config->selectedServer['designer_settings'] = false; //'pma__designer_settings';
        $config->selectedServer['export_templates'] = false; //'pma__export_templates';

        self::assertFalse($relation->arePmadbTablesDefined());
        self::assertTrue($relation->arePmadbTablesAllDisabled());
    }

    /**
     * @param array<string, bool|string> $params
     * @param string[]                   $queries
     */
    #[DataProvider('providerForTestRenameTable')]
    public function testRenameTable(array $params, array $queries): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $relationParameters = RelationParameters::fromArray($params);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        foreach ($queries as $query) {
            $dummyDbi->addResult($query, true);
        }

        $relation->renameTable('db_1', 'db_2', 'table_1', 'table_2');

        $dummyDbi->assertAllQueriesConsumed();
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
                [RelationParameters::USER => 'user', RelationParameters::DATABASE => 'pmadb', RelationParameters::COMM_WORK => true, RelationParameters::COLUMN_INFO => 'column_info'],
                ['UPDATE `pmadb`.`column_info` SET db_name = \'db_2\', table_name = \'table_2\' WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                [RelationParameters::USER => 'user', RelationParameters::DATABASE => 'pmadb', RelationParameters::DISPLAY_WORK => true, RelationParameters::RELATION => 'relation', RelationParameters::TABLE_INFO => 'table_info'],
                ['UPDATE `pmadb`.`table_info` SET db_name = \'db_2\', table_name = \'table_2\' WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                [RelationParameters::USER => 'user', RelationParameters::DATABASE => 'pmadb', RelationParameters::REL_WORK => true, RelationParameters::RELATION => 'relation'],
                [
                    'UPDATE `pmadb`.`relation` SET foreign_db = \'db_2\', foreign_table = \'table_2\' WHERE foreign_db = \'db_1\' AND foreign_table = \'table_1\'',
                    'UPDATE `pmadb`.`relation` SET master_db = \'db_2\', master_table = \'table_2\' WHERE master_db = \'db_1\' AND master_table = \'table_1\'',
                ],
            ],
            [
                [RelationParameters::USER => 'user', RelationParameters::DATABASE => 'pmadb', RelationParameters::PDF_WORK => true, RelationParameters::PDF_PAGES => 'pdf_pages', RelationParameters::TABLE_COORDS => 'table_coords'],
                ['DELETE FROM `pmadb`.`table_coords` WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                [RelationParameters::USER => 'user', RelationParameters::DATABASE => 'pmadb', RelationParameters::UI_PREFS_WORK => true, RelationParameters::TABLE_UI_PREFS => 'table_uiprefs'],
                ['UPDATE `pmadb`.`table_uiprefs` SET db_name = \'db_2\', table_name = \'table_2\' WHERE db_name = \'db_1\' AND table_name = \'table_1\''],
            ],
            [
                [RelationParameters::USER => 'user', RelationParameters::DATABASE => 'pmadb', RelationParameters::NAV_WORK => true, RelationParameters::NAVIGATION_HIDING => 'navigationhiding'],
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
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $relation = new Relation($dbi);

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::USER => 'user',
            RelationParameters::DATABASE => 'pma`db',
            RelationParameters::PDF_WORK => true,
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::TABLE_COORDS => 'table`coords',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $dummyDbi->addResult(
            'UPDATE `pma``db`.`table``coords` SET db_name = \'db\\\'1\', table_name = \'table\\\'2\''
                . ' WHERE db_name = \'db\\\'1\' AND table_name = \'table\\\'1\'',
            true,
        );

        $relation->renameTable('db\'1', 'db\'1', 'table\'1', 'table\'2');

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testGetTablesReturnsFilteredTables(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->addResult(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'somedb' AND ENGINE = 'InnoDB'",
            [
                ['table1'],
                ['table3'],
            ],
        );

        $relation = new Relation($dbi);

        $tables = $relation->getTables('somedb', 'InnoDB');
        self::assertEquals(['table1', 'table3'], $tables);

        $dummyDbi->assertAllQueriesConsumed();
    }
}
