<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\TriggersController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Database\TriggersController
 * @covers \PhpMyAdmin\Database\Triggers
 */
final class TriggersControllerTest extends AbstractTestCase
{
    public function testWithTriggers(): void
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
        $dummyDbi->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dummyDbi->addResult(
            'SHOW TABLE STATUS FROM `test_db` WHERE `Name` IN (\'test_table\')',
            [['test_table', 'InnoDB', '10', 'Dynamic', '3', '10922', '32768', '0', '32768', '0', '7', '2023-05-29 14:53:55', '2023-05-29 14:53:55', null, 'utf8mb4_general_ci', null, '', '', '0', 'N']],
            ['Name', 'Engine', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment', 'Max_index_length', 'Temporary']
        );
        $dummyDbi->addResult(
            'SHOW TRIGGERS FROM `test_db`',
            [['test_trigger', 'INSERT', 'test_table', 'BEGIN END', 'AFTER', 'definer@localhost']],
            ['Trigger', 'Event', 'Table', 'Statement', 'Timing', 'Definer']
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='TRIGGER'",
            [['TRIGGER']],
            ['PRIVILEGE_TYPE']
        );
        // phpcs:enable
        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        (new TriggersController(new ResponseRenderer(), new Template(), 'test_db', $dbi))();

        $actual = $this->getActualOutputForAssertion();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Triggers" alt="Triggers" class="icon ic_b_triggers">&nbsp;Triggers</span>
    <a href="./url.php?url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Ftriggers.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
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
      <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/triggers&db=test_db&table=&add_item=1&server=2&lang=en" role="button">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Create new trigger" alt="Create new trigger" class="icon ic_b_trigger_add">&nbsp;Create new trigger</span>
      </a>
    </div>
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/database/triggers&server=2&lang=en">
    <input type="hidden" name="db" value="test_db"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">

    <div id="nothing2display" class="hide">
      <div class="alert alert-primary" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> There are no triggers to display.
</div>

    </div>

    <table id="triggersTable" class="table table-striped table-hover w-auto data">
      <thead>
        <tr>
          <th></th>
          <th>Name</th>
                      <th>Table</th>
                    <th>Time</th>
          <th>Event</th>
          <th colspan="3"></th>
        </tr>
      </thead>
      <tbody>
        <tr class="hide"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr>
  <td>
    <input type="checkbox" class="checkall" name="item_name[]" value="test_trigger">
  </td>
  <td>
    <span class='drop_sql hide'>DROP TRIGGER IF EXISTS `test_trigger`</span>
    <strong>test_trigger</strong>
  </td>
      <td>
      <a href="index.php?route=/table/triggers&db=test_db&table=test_table&server=2&lang=en">test_table</a>
    </td>
    <td>
    AFTER
  </td>
  <td>
    INSERT
  </td>
  <td>
          <a class="ajax edit_anchor" href="index.php?route=/database/triggers&db=test_db&table=&edit_item=1&item_name=test_trigger&server=2&lang=en">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit">&nbsp;Edit</span>
      </a>
      </td>
  <td>
    <a class="ajax export_anchor" href="index.php?route=/database/triggers&db=test_db&table=&export_item=1&item_name=test_trigger&server=2&lang=en">
      <span class="text-nowrap"><img src="themes/dot.gif" title="Export" alt="Export" class="icon ic_b_export">&nbsp;Export</span>
    </a>
  </td>
  <td>
          <a href="index.php" data-post="route=/sql&server=2&lang=en&db=test_db&table=&sql_query=DROP+TRIGGER+IF+EXISTS+%60test_trigger%60&goto=index.php%3Froute%3D%2Fdatabase%2Ftriggers%26db%3Dtest_db%26server%3D2%26lang%3Den&server=2&lang=en" class="ajax drop_anchor"><span class="text-nowrap"><img src="themes/dot.gif" title="Drop" alt="Drop" class="icon ic_b_drop">&nbsp;Drop</span></a>
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

    public function testWithoutTriggers(): void
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
        $dummyDbi->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dummyDbi->addResult(
            'SHOW TABLE STATUS FROM `test_db` WHERE `Name` IN (\'test_table\')',
            [['test_table', 'InnoDB', '10', 'Dynamic', '3', '10922', '32768', '0', '32768', '0', '7', '2023-05-29 14:53:55', '2023-05-29 14:53:55', null, 'utf8mb4_general_ci', null, '', '', '0', 'N']],
            ['Name', 'Engine', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment', 'Max_index_length', 'Temporary']
        );
        $dummyDbi->addResult(
            'SHOW TRIGGERS FROM `test_db`',
            [],
            ['Trigger', 'Event', 'Table', 'Statement', 'Timing', 'Definer']
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='TRIGGER'",
            [['TRIGGER']],
            ['PRIVILEGE_TYPE']
        );
        // phpcs:enable
        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        (new TriggersController(new ResponseRenderer(), new Template(), 'test_db', $dbi))();

        $actual = $this->getActualOutputForAssertion();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Triggers" alt="Triggers" class="icon ic_b_triggers">&nbsp;Triggers</span>
    <a href="./url.php?url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Ftriggers.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
  </h2>

  <div class="d-flex flex-wrap my-3">
    <div>
      <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/triggers&db=test_db&table=&add_item=1&server=2&lang=en" role="button">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Create new trigger" alt="Create new trigger" class="icon ic_b_trigger_add">&nbsp;Create new trigger</span>
      </a>
    </div>
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/database/triggers&server=2&lang=en">
    <input type="hidden" name="db" value="test_db"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">

    <div id="nothing2display">
      <div class="alert alert-primary" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> There are no triggers to display.
</div>

    </div>

    <table id="triggersTable" class="table table-striped table-hover hide w-auto data">
      <thead>
        <tr>
          <th></th>
          <th>Name</th>
                      <th>Table</th>
                    <th>Time</th>
          <th>Event</th>
          <th colspan="3"></th>
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
