<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Database\RoutinesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RoutinesController::class)]
#[CoversClass(Routines::class)]
final class RoutinesControllerTest extends AbstractTestCase
{
    public function testWithRoutines(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            'SHOW GRANTS',
            [['GRANT ALL PRIVILEGES ON *.* TO `definer`@`localhost`']],
            ['Grants for definer@localhost'],
        );
        $dummyDbi->addResult(
            "SELECT `SPECIFIC_NAME` AS `Name`, `ROUTINE_TYPE` AS `Type`, `DEFINER` AS `Definer`, `DTD_IDENTIFIER` FROM `information_schema`.`ROUTINES` WHERE `ROUTINE_SCHEMA` COLLATE utf8_bin = 'test_db' ORDER BY `SPECIFIC_NAME` LIMIT 250",
            [['test_db', 'test_func', 'FUNCTION', 'definer@localhost', null], ['test_db', 'test_proc', 'PROCEDURE', 'definer@localhost', null]],
            ['Db', 'Name', 'Type', 'Definer', 'DTD_IDENTIFIER'],
        );
        $dummyDbi->addResult('SELECT @@lower_case_table_names', []);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EXECUTE'",
            [['EXECUTE']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EXECUTE'",
            [['EXECUTE']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            "SELECT COUNT(*) AS `count` FROM `information_schema`.`ROUTINES` WHERE `ROUTINE_SCHEMA` COLLATE utf8_bin = 'test_db'",
            [[2]],
            ['count'],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        $template = new Template();
        $response = new ResponseRenderer();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        (new RoutinesController(
            $response,
            $template,
            new UserPrivilegesFactory($dbi),
            $dbi,
            new Routines($dbi),
            new DbTableExists($dbi),
            $config,
        ))($request);

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="container-fluid my-3">
              <h2>
                <span class="text-nowrap"><img src="themes/dot.gif" title="Routines" alt="Routines" class="icon ic_b_routines">&nbsp;Routines</span>
                <a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fstored-routines.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
              </h2>

              <div class="d-flex flex-wrap my-3">    <div>
                  <div class="input-group">
                    <div class="input-group-text">
                      <div class="form-check mb-0">
                        <input class="form-check-input checkall_box" type="checkbox" value="" id="checkAllCheckbox" form="rteListForm">
                        <label class="form-check-label" for="checkAllCheckbox">Check all</label>
                      </div>
                    </div>
                    <button class="btn btn-outline-secondary" id="bulkActionExportButton" type="submit" name="submit_mult" value="export" form="rteListForm" title="Export">
                      <span class="text-nowrap"><img src="themes/dot.gif" title="Export" alt="Export" class="icon ic_b_export">&nbsp;Export</span>
                    </button>
                    <button class="btn btn-outline-secondary" id="bulkActionDropButton" type="submit" name="submit_mult" value="drop" form="rteListForm" title="Drop">
                      <span class="text-nowrap"><img src="themes/dot.gif" title="Drop" alt="Drop" class="icon ic_b_drop">&nbsp;Drop</span>
                    </button>
                  </div>
                </div>

                <div class="ms-auto">
                  <div class="input-group">
                    <span class="input-group-text"><img src="themes/dot.gif" title="Search" alt="Search" class="icon ic_b_search"></span>
                    <input class="form-control" name="filterText" type="text" id="filterText" value="" placeholder="Search" aria-label="Search">
                  </div>
                </div>
                <div class="ms-2">
                  <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/routines&db=test_db&table=&add_item=1&server=2&lang=en" role="button">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Create new routine" alt="Create new routine" class="icon ic_b_routine_add">&nbsp;Create new routine</span>
                  </a>
                </div>
              </div><form id="rteListForm" class="disableAjax" action="index.php?route=/database/routines&server=2&lang=en">
                <input type="hidden" name="db" value="test_db"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">

                <div id="nothing2display" class="hide">
                  <div class="alert alert-primary" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> There are no routines to display.
            </div>

                </div>

                <table id="routinesTable" class="table table-striped table-hover data w-auto">
                  <thead>
                  <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Returns</th>
                    <th colspan="4"></th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr class="hide"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr data-filter-row="TEST_FUNC">
              <td>
                <input type="checkbox" class="checkall" name="item_name[]" value="test_func">
              </td>
              <td>
                <span class="drop_sql hide">DROP FUNCTION IF EXISTS `test_func`</span>
                <strong>test_func</strong>
              </td>
              <td>FUNCTION</td>
              <td dir="ltr"></td>
              <td>
                      <a class="ajax edit_anchor" href="index.php?route=/database/routines&db=test_db&table=&edit_item=1&item_name=test_func&item_type=FUNCTION&server=2&lang=en">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit">&nbsp;Edit</span>
                  </a>
                  </td>
              <td>
                      <a class="ajax exec_anchor" href="index.php?route=/database/routines&db=test_db&table=&execute_dialog=1&item_name=test_func&item_type=FUNCTION&server=2&lang=en">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Execute" alt="Execute" class="icon ic_b_nextpage">&nbsp;Execute</span>
                  </a>
                  </td>
              <td>
                      <a class="ajax export_anchor" href="index.php?route=/database/routines&db=test_db&table=&export_item=1&item_name=test_func&item_type=FUNCTION&server=2&lang=en">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Export" alt="Export" class="icon ic_b_export">&nbsp;Export</span>
                  </a>
                  </td>
              <td>
                <a href="index.php" data-post="route=/sql&server=2&lang=en&db=test_db&table=&sql_query=DROP+FUNCTION+IF+EXISTS+%60test_func%60&goto=index.php%3Froute%3D%2Fdatabase%2Froutines%26db%3Dtest_db%26server%3D2%26lang%3Den&server=2&lang=en" class="ajax drop_anchor"><span class="text-nowrap"><img src="themes/dot.gif" title="Drop" alt="Drop" class="icon ic_b_drop">&nbsp;Drop</span></a>
              </td>
            </tr>
            <tr data-filter-row="TEST_PROC">
              <td>
                <input type="checkbox" class="checkall" name="item_name[]" value="test_proc">
              </td>
              <td>
                <span class="drop_sql hide">DROP PROCEDURE IF EXISTS `test_proc`</span>
                <strong>test_proc</strong>
              </td>
              <td>PROCEDURE</td>
              <td dir="ltr"></td>
              <td>
                      <a class="ajax edit_anchor" href="index.php?route=/database/routines&db=test_db&table=&edit_item=1&item_name=test_proc&item_type=PROCEDURE&server=2&lang=en">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit">&nbsp;Edit</span>
                  </a>
                  </td>
              <td>
                      <a class="ajax exec_anchor" href="index.php?route=/database/routines&db=test_db&table=&execute_dialog=1&item_name=test_proc&item_type=PROCEDURE&server=2&lang=en">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Execute" alt="Execute" class="icon ic_b_nextpage">&nbsp;Execute</span>
                  </a>
                  </td>
              <td>
                      <a class="ajax export_anchor" href="index.php?route=/database/routines&db=test_db&table=&export_item=1&item_name=test_proc&item_type=PROCEDURE&server=2&lang=en">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Export" alt="Export" class="icon ic_b_export">&nbsp;Export</span>
                  </a>
                  </td>
              <td>
                <a href="index.php" data-post="route=/sql&server=2&lang=en&db=test_db&table=&sql_query=DROP+PROCEDURE+IF+EXISTS+%60test_proc%60&goto=index.php%3Froute%3D%2Fdatabase%2Froutines%26db%3Dtest_db%26server%3D2%26lang%3Den&server=2&lang=en" class="ajax drop_anchor"><span class="text-nowrap"><img src="themes/dot.gif" title="Drop" alt="Drop" class="icon ic_b_drop">&nbsp;Drop</span></a>
              </td>
            </tr>

                  </tbody>
                </table>
              </form><div class="modal fade" id="routinesEditorModal" tabindex="-1" aria-labelledby="routinesEditorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="routinesEditorModalLabel">Routine editor</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading…</span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary" id="routinesEditorModalSaveButton">Save changes</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="routinesExportModal" tabindex="-1" aria-labelledby="routinesExportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="routinesExportModalLabel">Export routine</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="routinesExecuteModal" tabindex="-1" aria-labelledby="routinesExecuteModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="routinesExecuteModalLabel">Execute routine</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading…</span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary" id="routinesExecuteModalExecuteButton">Execute</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();
    }

    public function testWithoutRoutines(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            'SHOW GRANTS',
            [['GRANT ALL PRIVILEGES ON *.* TO `definer`@`localhost`']],
            ['Grants for definer@localhost'],
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult('SELECT @@lower_case_table_names', []);
        $dummyDbi->addResult(
            "SELECT COUNT(*) AS `count` FROM `information_schema`.`ROUTINES` WHERE `ROUTINE_SCHEMA` COLLATE utf8_bin = 'test_db'",
            [[1]],
            ['count'],
        );
        $dummyDbi->addResult(
            "SELECT `SPECIFIC_NAME` AS `Name`, `ROUTINE_TYPE` AS `Type`, `DEFINER` AS `Definer`, `DTD_IDENTIFIER` FROM `information_schema`.`ROUTINES` WHERE `ROUTINE_SCHEMA` COLLATE utf8_bin = 'test_db' ORDER BY `SPECIFIC_NAME` LIMIT 250",
            [],
            ['Db', 'Name', 'Type', 'Definer', 'DTD_IDENTIFIER'],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        $template = new Template();
        $response = new ResponseRenderer();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        (new RoutinesController(
            $response,
            $template,
            new UserPrivilegesFactory($dbi),
            $dbi,
            new Routines($dbi),
            new DbTableExists($dbi),
            $config,
        ))($request);

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="container-fluid my-3">
              <h2>
                <span class="text-nowrap"><img src="themes/dot.gif" title="Routines" alt="Routines" class="icon ic_b_routines">&nbsp;Routines</span>
                <a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fstored-routines.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
              </h2>

              <div class="d-flex flex-wrap my-3">
                <div>
                  <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/routines&db=test_db&table=&add_item=1&server=2&lang=en" role="button">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Create new routine" alt="Create new routine" class="icon ic_b_routine_add">&nbsp;Create new routine</span>
                  </a>
                </div>
              </div><form id="rteListForm" class="disableAjax" action="index.php?route=/database/routines&server=2&lang=en">
                <input type="hidden" name="db" value="test_db"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">

                <div id="nothing2display">
                  <div class="alert alert-primary" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> There are no routines to display.
            </div>

                </div>

                <table id="routinesTable" class="table table-striped table-hover hide data w-auto">
                  <thead>
                  <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Returns</th>
                    <th colspan="4"></th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr class="hide"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                  </tbody>
                </table>
              </form><div class="modal fade" id="routinesEditorModal" tabindex="-1" aria-labelledby="routinesEditorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="routinesEditorModalLabel">Routine editor</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading…</span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary" id="routinesEditorModalSaveButton">Save changes</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="routinesExportModal" tabindex="-1" aria-labelledby="routinesExportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="routinesExportModalLabel">Export routine</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="routinesExecuteModal" tabindex="-1" aria-labelledby="routinesExecuteModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="routinesExecuteModalLabel">Execute routine</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading…</span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary" id="routinesExecuteModalExecuteButton">Execute</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
        $dummyDbi->assertAllQueriesConsumed();
        $dummyDbi->assertAllSelectsConsumed();
    }
}
