<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BrowseForeignersController::class)]
#[CoversClass(BrowseForeigners::class)]
final class BrowseForeignersControllerTest extends AbstractTestCase
{
    public function testBrowseForeignValues(): void
    {
        Current::$server = 2;
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'db' => 'sakila',
                'table' => 'film_actor',
                'field' => 'actor_id',
                'fieldkey' => '',
                'data' => '',
                'foreign_showAll' => null,
                'foreign_filter' => '',
            ]);

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult('SELECT @@lower_case_table_names', [['0']]);
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `sakila`.`film_actor`',
            [
                [
                    'film_actor',
                    'CREATE TABLE `film_actor` (
  `actor_id` smallint(5) unsigned NOT NULL,
  `film_id` smallint(5) unsigned NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`actor_id`,`film_id`),
  KEY `idx_fk_film_id` (`film_id`),
  CONSTRAINT `fk_film_actor_actor` FOREIGN KEY (`actor_id`) REFERENCES `actor` (`actor_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_film_actor_film` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
                ],
            ],
            ['Table', 'Create Table'],
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            'SHOW INDEXES FROM .`actor` WHERE (Non_unique = 0)',
            [['actor', '0', 'PRIMARY', '1', 'actor_id', 'A', '2', 'NULL', 'NULL', '', 'BTREE', '', '', 'NO']],
            ['Table', 'Non_unique', 'Key_name', 'Seq_in_index', 'Column_name', 'Collation', 'Cardinality', 'Sub_part', 'Packed', 'Null', 'Index_type', 'Comment', 'Index_comment', 'Ignored'],
        );
        $dbiDummy->addResult('SELECT `actor_id` FROM .`actor` LIMIT 100', [['71'], ['173'], ['125']], ['actor_id']);
        $dbiDummy->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`,'
                . ' `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'\' AND `TABLE_NAME`'
                . ' COLLATE utf8_bin = \'actor\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [
                ['actor_id', 'smallint(5) unsigned', null, 'NO', 'PRI', null, 'auto_increment', '', ''],
                ['first_name', 'varchar(45)', null, 'NO', '', null, '', '', ''],
                ['last_name', 'varchar(45)', null, 'NO', 'MUL', null, '', '', ''],
                ['last_update', 'timestamp', null, 'NO', '', 'current_timestamp()', 'on update current_timestamp()', '', ''],
            ],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );
        $dbiDummy->addResult('SHOW INDEXES FROM .`actor`', []);
        // phpcs:enable
        $dbiDummy->addResult(
            'SELECT `actor_id`, `first_name` FROM .`actor` ORDER BY `actor`.`first_name`LIMIT 0, 25',
            [['71', 'ADAM'], ['173', 'ALAN'], ['125', 'ALBERT']],
            ['actor_id', 'first_name'],
        );
        $dbiDummy->addResult('SELECT COUNT(*) FROM .`actor`', [['3']], ['COUNT(*)']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $template = new Template();
        $response = new ResponseRenderer();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <form class="ajax" id="browse_foreign_form" name="browse_foreign_from" action="index.php?route=/browse-foreigners&server=2&lang=en" method="post"><fieldset class="row g-3 align-items-center mb-3">
            <input type="hidden" name="db" value="sakila"><input type="hidden" name="table" value="film_actor"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">
            <input type="hidden" name="field" value="actor_id">
            <input type="hidden" name="fieldkey" value="">
            <div class="col-auto"><label class="form-label" for="input_foreign_filter">Search:</label></div>
            <div class="col-auto"><input class="form-control" type="text" name="foreign_filter" id="input_foreign_filter" value="" data-old="">
            </div><div class="col-auto"><input class="btn btn-primary" type="submit" name="submit_foreign_filter" value="Go"></div>
            <div class="col-auto"></div><div class="col-auto"></div></fieldset></form>
            <table class="table table-striped table-hover" id="browse_foreign_table">
            <thead><tr>
                        <th>Keyname</th>
                        <th>Description</th>
                        <td width="20%"></td>
                        <th>Description</th>
                        <th>Keyname</th>
                    </tr>
            </thead>
            <tfoot><tr>
                        <th>Keyname</th>
                        <th>Description</th>
                        <td width="20%"></td>
                        <th>Description</th>
                        <th>Keyname</th>
                    </tr>
            </tfoot>
            <tbody>
            <tr class="noclick"><td class="text-nowrap">
                <a class="foreign_value" data-key="71" href="#" title="Use this value">71</a>
            </td>
            <td>
                <a class="foreign_value" data-key="71" href="#" title="Use this value">ADAM</a>
            </td>
            <td width="20%"><img src="./themes/pmahomme/img/spacer.png" alt="" width="1" height="1"></td><td>
                <a class="foreign_value" data-key="71" href="#" title="Use this value">ADAM</a>
            </td>
            <td class="text-nowrap">
                <a class="foreign_value" data-key="71" href="#" title="Use this value">71</a>
            </td>
            </tr>
            <tr class="noclick"><td class="text-nowrap">
                <a class="foreign_value" data-key="125" href="#" title="Use this value">125</a>
            </td>
            <td>
                <a class="foreign_value" data-key="125" href="#" title="Use this value">ALBERT</a>
            </td>
            <td width="20%"><img src="./themes/pmahomme/img/spacer.png" alt="" width="1" height="1"></td><td>
                <a class="foreign_value" data-key="173" href="#" title="Use this value">ALAN</a>
            </td>
            <td class="text-nowrap">
                <a class="foreign_value" data-key="173" href="#" title="Use this value">173</a>
            </td>
            </tr>
            <tr class="noclick"><td class="text-nowrap">
                <a class="foreign_value" data-key="173" href="#" title="Use this value">173</a>
            </td>
            <td>
                <a class="foreign_value" data-key="173" href="#" title="Use this value">ALAN</a>
            </td>
            <td width="20%"><img src="./themes/pmahomme/img/spacer.png" alt="" width="1" height="1"></td><td>
                <a class="foreign_value" data-key="125" href="#" title="Use this value">ALBERT</a>
            </td>
            <td class="text-nowrap">
                <a class="foreign_value" data-key="125" href="#" title="Use this value">125</a>
            </td>
            </tr>
            </tbody></table>
            HTML;
        // phpcs:enable

        (new BrowseForeignersController(
            $response,
            new BrowseForeigners($template, $config, new ThemeManager()),
            new Relation($dbi),
        ))($request);

        self::assertSame($expected, $response->getHTMLResult());
    }
}
