<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Tracking\Tracker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

#[CoversClass(CheckRelationsController::class)]
class CheckRelationsControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testCheckRelationsController(): void
    {
        Current::$database = '';
        Current::$table = '';

        $request = self::createStub(ServerRequest::class);

        $response = new ResponseRenderer();
        Config::getInstance()->selectedServer['pmadb'] = '';
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $controller = new CheckRelationsController($response, new Relation($this->dbi));
        $controller($request);

        $actual = $response->getHTMLResult();

        self::assertStringContainsString('phpMyAdmin configuration storage', $actual);
        self::assertStringContainsString(
            'Configuration of pmadb…' . "\n" . '      <span class="text-danger"><strong>not OK</strong></span>',
            $actual,
        );
        self::assertStringContainsString(
            'Create</a> a database named &#039;phpmyadmin&#039; and setup the phpMyAdmin configuration storage there.',
            $actual,
        );
    }

    #[Test]
    public function createConfigStorage(): void
    {
        $relationParameters = RelationParameters::fromArray([]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
        Tracker::enable();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['create_pmadb' => '1']);

        $config = Config::$instance = new Config();

        $dbiDummy = $this->createDbiDummy();
        $dbi = DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('CREATE DATABASE IF NOT EXISTS `phpmyadmin`', true);
        $dbiDummy->addSelectDb('phpmyadmin');
        $dbiDummy->addResult('SHOW TABLES FROM `phpmyadmin`;', []);
        $dbiDummy->addResult('SHOW TABLES FROM `phpmyadmin`;', []);
        $dbiDummy->addSelectDb('phpmyadmin');
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__bookmark` -- CREATE TABLE IF NOT EXISTS `pma__bookmark` ( `id` int(10) unsigned NOT NULL auto_increment, `dbase` varchar(255) NOT NULL default '', `user` varchar(255) NOT NULL default '', `label` varchar(255) COLLATE utf8_general_ci NOT NULL default '', `query` text NOT NULL, PRIMARY KEY (`id`) ) COMMENT='Bookmarks' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__relation` -- CREATE TABLE IF NOT EXISTS `pma__relation` ( `master_db` varchar(64) NOT NULL default '', `master_table` varchar(64) NOT NULL default '', `master_field` varchar(64) NOT NULL default '', `foreign_db` varchar(64) NOT NULL default '', `foreign_table` varchar(64) NOT NULL default '', `foreign_field` varchar(64) NOT NULL default '', PRIMARY KEY (`master_db`,`master_table`,`master_field`), KEY `foreign_field` (`foreign_db`,`foreign_table`) ) COMMENT='Relation table' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__table_info` -- CREATE TABLE IF NOT EXISTS `pma__table_info` ( `db_name` varchar(64) NOT NULL default '', `table_name` varchar(64) NOT NULL default '', `display_field` varchar(64) NOT NULL default '', PRIMARY KEY (`db_name`,`table_name`) ) COMMENT='Table information for phpMyAdmin' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__table_coords` -- CREATE TABLE IF NOT EXISTS `pma__table_coords` ( `db_name` varchar(64) NOT NULL default '', `table_name` varchar(64) NOT NULL default '', `pdf_page_number` int(11) NOT NULL default '0', `x` float unsigned NOT NULL default '0', `y` float unsigned NOT NULL default '0', PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`) ) COMMENT='Table coordinates for phpMyAdmin PDF output' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__pdf_pages` -- CREATE TABLE IF NOT EXISTS `pma__pdf_pages` ( `db_name` varchar(64) NOT NULL default '', `page_nr` int(10) unsigned NOT NULL auto_increment, `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default '', PRIMARY KEY (`page_nr`), KEY `db_name` (`db_name`) ) COMMENT='PDF relation pages for phpMyAdmin' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__column_info` -- CREATE TABLE IF NOT EXISTS `pma__column_info` ( `id` int(5) unsigned NOT NULL auto_increment, `db_name` varchar(64) NOT NULL default '', `table_name` varchar(64) NOT NULL default '', `column_name` varchar(64) NOT NULL default '', `comment` varchar(255) COLLATE utf8_general_ci NOT NULL default '', `mimetype` varchar(255) COLLATE utf8_general_ci NOT NULL default '', `transformation` varchar(255) NOT NULL default '', `transformation_options` varchar(255) NOT NULL default '', `input_transformation` varchar(255) NOT NULL default '', `input_transformation_options` varchar(255) NOT NULL default '', PRIMARY KEY (`id`), UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`) ) COMMENT='Column information for phpMyAdmin' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__history` -- CREATE TABLE IF NOT EXISTS `pma__history` ( `id` bigint(20) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default '', `db` varchar(64) NOT NULL default '', `table` varchar(64) NOT NULL default '', `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP, `sqlquery` text NOT NULL, PRIMARY KEY (`id`), KEY `username` (`username`,`db`,`table`,`timevalue`) ) COMMENT='SQL history for phpMyAdmin' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__recent` -- CREATE TABLE IF NOT EXISTS `pma__recent` ( `username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) ) COMMENT='Recently accessed tables' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__favorite` -- CREATE TABLE IF NOT EXISTS `pma__favorite` ( `username` varchar(64) NOT NULL, `tables` text NOT NULL, PRIMARY KEY (`username`) ) COMMENT='Favorite tables' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__table_uiprefs` -- CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` ( `username` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL, `table_name` varchar(64) NOT NULL, `prefs` text NOT NULL, `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`username`,`db_name`,`table_name`) ) COMMENT='Tables'' UI preferences' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__tracking` -- CREATE TABLE IF NOT EXISTS `pma__tracking` ( `db_name` varchar(64) NOT NULL, `table_name` varchar(64) NOT NULL, `version` int(10) unsigned NOT NULL, `date_created` datetime NOT NULL, `date_updated` datetime NOT NULL, `schema_snapshot` text NOT NULL, `schema_sql` text, `data_sql` longtext, `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') default NULL, `tracking_active` int(1) unsigned NOT NULL default '1', PRIMARY KEY (`db_name`,`table_name`,`version`) ) COMMENT='Database changes tracking for phpMyAdmin' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__userconfig` -- CREATE TABLE IF NOT EXISTS `pma__userconfig` ( `username` varchar(64) NOT NULL, `timevalue` timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `config_data` text NOT NULL, PRIMARY KEY (`username`) ) COMMENT='User preferences storage for phpMyAdmin' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__users` -- CREATE TABLE IF NOT EXISTS `pma__users` ( `username` varchar(64) NOT NULL, `usergroup` varchar(64) NOT NULL, PRIMARY KEY (`username`,`usergroup`) ) COMMENT='Users and their assignments to user groups' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__usergroups` -- CREATE TABLE IF NOT EXISTS `pma__usergroups` ( `usergroup` varchar(64) NOT NULL, `tab` varchar(64) NOT NULL, `allowed` enum('Y','N') NOT NULL DEFAULT 'N', PRIMARY KEY (`usergroup`,`tab`,`allowed`) ) COMMENT='User groups with configured menu items' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__navigationhiding` -- CREATE TABLE IF NOT EXISTS `pma__navigationhiding` ( `username` varchar(64) NOT NULL, `item_name` varchar(64) NOT NULL, `item_type` varchar(64) NOT NULL, `db_name` varchar(64) NOT NULL, `table_name` varchar(64) NOT NULL, PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`) ) COMMENT='Hidden items of navigation tree' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__savedsearches` -- CREATE TABLE IF NOT EXISTS `pma__savedsearches` ( `id` int(5) unsigned NOT NULL auto_increment, `username` varchar(64) NOT NULL default '', `db_name` varchar(64) NOT NULL default '', `search_name` varchar(64) NOT NULL default '', `search_data` text NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`) ) COMMENT='Saved searches' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__central_columns` -- CREATE TABLE IF NOT EXISTS `pma__central_columns` ( `db_name` varchar(64) NOT NULL, `col_name` varchar(64) NOT NULL, `col_type` varchar(64) NOT NULL, `col_length` text, `col_collation` varchar(64) NOT NULL, `col_isNull` boolean NOT NULL, `col_extra` varchar(255) default '', `col_default` text, PRIMARY KEY (`db_name`,`col_name`) ) COMMENT='Central list of columns' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__designer_settings` -- CREATE TABLE IF NOT EXISTS `pma__designer_settings` ( `username` varchar(64) NOT NULL, `settings_data` text NOT NULL, PRIMARY KEY (`username`) ) COMMENT='Settings related to Designer' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addResult("-- -------------------------------------------------------- -- -- Table structure for table `pma__export_templates` -- CREATE TABLE IF NOT EXISTS `pma__export_templates` ( `id` int(5) unsigned NOT NULL AUTO_INCREMENT, `username` varchar(64) NOT NULL, `export_type` varchar(10) NOT NULL, `template_name` varchar(64) NOT NULL, `template_data` text NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`) ) COMMENT='Saved export templates' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;", true);
        $dbiDummy->addSelectDb('phpmyadmin');
        $dbiDummy->addResult('SHOW TABLES FROM `phpmyadmin`;', [['pma__bookmark'], ['pma__central_columns'], ['pma__column_info'], ['pma__designer_settings'], ['pma__export_templates'], ['pma__favorite'], ['pma__history'], ['pma__navigationhiding'], ['pma__pdf_pages'], ['pma__recent'], ['pma__relation'], ['pma__savedsearches'], ['pma__table_coords'], ['pma__table_info'], ['pma__table_uiprefs'], ['pma__tracking'], ['pma__userconfig'], ['pma__usergroups'], ['pma__users']]);
        $dbiDummy->addResult('SELECT NULL FROM `pma__table_info` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__table_coords` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__pdf_pages` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__column_info` LIMIT 0', []);
        $dbiDummy->addResult("SHOW COLUMNS FROM `phpmyadmin`.`pma__column_info` WHERE Field IN ('input_transformation', 'input_transformation_options')", [['input_transformation'], ['input_transformation_options']]);
        $dbiDummy->addResult('SELECT NULL FROM `pma__users` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__usergroups` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__export_templates` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__designer_settings` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__central_columns` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__savedsearches` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__navigationhiding` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__bookmark` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__userconfig` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__tracking` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__table_uiprefs` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__favorite` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__recent` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__history` LIMIT 0', []);
        $dbiDummy->addResult('SELECT NULL FROM `pma__relation` LIMIT 0', []);

        $controller = new CheckRelationsController(new ResponseRenderer(), new Relation($dbi, $config));
        $response = $controller($request);

        $responseBody = (string) $response->getBody();
        self::assertStringContainsString("General relation features:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Display features:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Designer and creation of PDFs:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Displaying column comments:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Bookmarked SQL query:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("SQL history:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Persistent recently used tables:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Persistent favorite tables:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Persistent tables&#039; UI preferences:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Tracking:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("User preferences:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Configurable menus:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Hide/show navigation items:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Saving Query-By-Example searches:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Managing central list of columns:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Remembering designer settings:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        self::assertStringContainsString("Saving export templates:\n                      <span class=\"text-success\">Enabled</span>", $responseBody);
        // phpcs:enable

        $dbiDummy->assertAllQueriesConsumed();
        $dbiDummy->assertAllSelectsConsumed();
    }
}
