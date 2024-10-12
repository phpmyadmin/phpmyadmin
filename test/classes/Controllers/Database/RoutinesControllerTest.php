<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\Database\RoutinesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Database\RoutinesController
 * @covers \PhpMyAdmin\Database\Routines
 */
final class RoutinesControllerTest extends AbstractTestCase
{
    public function testWithRoutines(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $dummyDbi = new DbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            'SHOW GRANTS',
            [['GRANT ALL PRIVILEGES ON *.* TO `definer`@`localhost`']],
            ['Grants for definer@localhost']
        );
        $dummyDbi->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dummyDbi->addResult(
            'SHOW TABLE STATUS FROM `test_db` WHERE `Name` IN (\'test_table\')',
            [['test_table', 'InnoDB', '10', 'Dynamic', '3', '10922', '32768', '0', '32768', '0', '7', '2023-05-29 14:53:55', '2023-05-29 14:53:55', null, 'utf8mb4_general_ci', null, '', '', '0', 'N']],
            ['Name', 'Engine', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment', 'Max_index_length', 'Temporary']
        );
        $dummyDbi->addResult(
            'SHOW FUNCTION STATUS WHERE `Db` = \'test_db\'',
            [['test_db', 'test_func', 'FUNCTION', 'definer@localhost']],
            ['Db', 'Name', 'Type', 'Definer']
        );
        $dummyDbi->addResult(
            'SHOW PROCEDURE STATUS WHERE `Db` = \'test_db\'',
            [['test_db', 'test_proc', 'PROCEDURE', 'definer@localhost']],
            ['Db', 'Name', 'Type', 'Definer']
        );
        $dummyDbi->addResult('SELECT @@lower_case_table_names', []);
        $dummyDbi->addResult(
            "SELECT `DEFINER` FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA ='test_db' AND SPECIFIC_NAME='test_func'AND ROUTINE_TYPE='FUNCTION';",
            [['definer@localhost']],
            ['DEFINER']
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE']
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EXECUTE'",
            [['EXECUTE']],
            ['PRIVILEGE_TYPE']
        );
        $dummyDbi->addResult(
            'SHOW CREATE FUNCTION `test_db`.`test_func`',
            [['test_func', 'CREATE FUNCTION `test_func` (p INT) RETURNS int(11) BEGIN END']],
            ['Function', 'Create Function']
        );
        $dummyDbi->addResult(
            "SELECT `DEFINER` FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA ='test_db' AND SPECIFIC_NAME='test_proc'AND ROUTINE_TYPE='PROCEDURE';",
            [['definer@localhost']],
            ['DEFINER']
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE']
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EXECUTE'",
            [['EXECUTE']],
            ['PRIVILEGE_TYPE']
        );
        $dummyDbi->addResult(
            'SHOW CREATE PROCEDURE `test_db`.`test_proc`',
            [['test_proc2', 'CREATE PROCEDURE `test_proc2` (p INT) BEGIN END']],
            ['Procedure', 'Create Procedure']
        );
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE']
        );
        // phpcs:enable

        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();

        (new RoutinesController($response, new Template(), 'test_db', new CheckUserPrivileges($dbi), $dbi))();

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Routines" alt="Routines" class="icon ic_b_routines">&nbsp;Routines</span>
    <a href="./url.php?url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fstored-routines.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
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
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/database/routines&server=2&lang=en">
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
  </form>
</div>

HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
    }

    public function testWithoutRoutines(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $dummyDbi = new DbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            'SHOW GRANTS',
            [['GRANT ALL PRIVILEGES ON *.* TO `definer`@`localhost`']],
            ['Grants for definer@localhost']
        );
        $dummyDbi->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dummyDbi->addResult(
            'SHOW TABLE STATUS FROM `test_db` WHERE `Name` IN (\'test_table\')',
            [['test_table', 'InnoDB', '10', 'Dynamic', '3', '10922', '32768', '0', '32768', '0', '7', '2023-05-29 14:53:55', '2023-05-29 14:53:55', null, 'utf8mb4_general_ci', null, '', '', '0', 'N']],
            ['Name', 'Engine', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment', 'Max_index_length', 'Temporary']
        );
        $dummyDbi->addResult('SHOW FUNCTION STATUS WHERE `Db` = \'test_db\'', [], ['Db', 'Name', 'Type', 'Definer']);
        $dummyDbi->addResult('SHOW PROCEDURE STATUS WHERE `Db` = \'test_db\'', [], ['Db', 'Name', 'Type', 'Definer']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='CREATE ROUTINE'",
            [['CREATE ROUTINE']],
            ['PRIVILEGE_TYPE']
        );
        // phpcs:enable

        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();

        (new RoutinesController($response, new Template(), 'test_db', new CheckUserPrivileges($dbi), $dbi))();

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Routines" alt="Routines" class="icon ic_b_routines">&nbsp;Routines</span>
    <a href="./url.php?url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fstored-routines.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
  </h2>

  <div class="d-flex flex-wrap my-3">
    <div>
      <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/routines&db=test_db&table=&add_item=1&server=2&lang=en" role="button">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Create new routine" alt="Create new routine" class="icon ic_b_routine_add">&nbsp;Create new routine</span>
      </a>
    </div>
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/database/routines&server=2&lang=en">
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
  </form>
</div>

HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
    }
}
