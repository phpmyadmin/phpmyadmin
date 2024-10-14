<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\EventsController;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Database\EventsController
 * @covers \PhpMyAdmin\Database\Events
 */
final class EventsControllerTest extends AbstractTestCase
{
    public function testWithEvents(): void
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
            'SHOW EVENTS FROM `test_db`',
            [['test_db', 'test_event', 'definer@localhost', 'ONE TIME', 'ENABLED']],
            ['Db', 'Name', 'Definer', 'Type', 'Status']
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE']
        );
        $dummyDbi->addResult(
            'SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'',
            [['event_scheduler', 'OFF']],
            ['Variable_name', 'Value']
        );
        // phpcs:enable
        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        (new EventsController($response, $template, 'test_db', new Events($dbi, $template, $response), $dbi))();

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Events" alt="Events" class="icon ic_b_events">&nbsp;Events</span>
    <a href="./url.php?url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fevents.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
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
      <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/events&db=test_db&add_item=1&server=2&lang=en" role="button">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Create new event" alt="Create new event" class="icon ic_b_event_add">&nbsp;Create new event</span>
      </a>
    </div>
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/database/events&server=2&lang=en">
    <input type="hidden" name="db" value="test_db"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">

    <div id="nothing2display" class="hide">
      <div class="alert alert-primary" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> There are no events to display.
</div>

    </div>

    <table id="eventsTable" class="table table-striped table-hover w-auto data">
      <thead>
      <tr>
        <th></th>
        <th>Name</th>
        <th>Status</th>
        <th>Type</th>
        <th colspan="3"></th>
      </tr>
      </thead>
      <tbody>
      <tr class="hide"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>

              <tr>
          <td>
            <input type="checkbox" class="checkall" name="item_name[]" value="test_event">
          </td>
          <td>
            <span class="drop_sql hide">DROP EVENT IF EXISTS `test_event`</span>
            <strong>test_event</strong>
          </td>
          <td>
            ENABLED
          </td>
          <td>
            ONE TIME
          </td>
          <td>
                          <a class="ajax edit_anchor" href="index.php?route=/database/events&db=test_db&edit_item=1&item_name=test_event&server=2&lang=en">
                <span class="text-nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit">&nbsp;Edit</span>
              </a>
                      </td>
          <td>
            <a class="ajax export_anchor" href="index.php?route=/database/events&db=test_db&export_item=1&item_name=test_event&server=2&lang=en">
              <span class="text-nowrap"><img src="themes/dot.gif" title="Export" alt="Export" class="icon ic_b_export">&nbsp;Export</span>
            </a>
          </td>
          <td>
                          <a href="index.php" data-post="route=/sql&server=2&lang=en&db=test_db&sql_query=DROP+EVENT+IF+EXISTS+%60test_event%60&goto=index.php%3Froute%3D%2Fdatabase%2Fevents%26db%3Dtest_db%26server%3D2%26lang%3Den&server=2&lang=en" class="ajax drop_anchor"><span class="text-nowrap"><img src="themes/dot.gif" title="Drop" alt="Drop" class="icon ic_b_drop">&nbsp;Drop</span></a>
                      </td>
        </tr>
            </tbody>
    </table>
  </form>

  <div class="card mt-3">
    <div class="card-header">Event scheduler status</div>
    <div class="card-body">
      <div class="wrap">
        <div class="wrapper toggleAjax hide">
          <div class="toggleButton">
            <div title="Click to toggle" class="toggle-container off">
              <img src="">
              <table>
                <tbody>
                <tr>
                  <td class="toggleOn">
                  <span class="hide">index.php?route=/sql&db=test_db&goto=index.php%3Froute%3D%2Fdatabase%2Fevents%26db%3Dtest_db%26server%3D2%26lang%3Den&sql_query=SET+GLOBAL+event_scheduler%3D%22ON%22&server=2&lang=en</span>
                    <div>ON</div>
                  </td>
                  <td><div>&nbsp;</div></td>
                  <td class="toggleOff">
                  <span class="hide">index.php?route=/sql&db=test_db&goto=index.php%3Froute%3D%2Fdatabase%2Fevents%26db%3Dtest_db%26server%3D2%26lang%3Den&sql_query=SET+GLOBAL+event_scheduler%3D%22OFF%22&server=2&lang=en</span>
                    <div>OFF</div>
                  </td>
                </tr>
                </tbody>
              </table>
              <span class="hide callback">Functions.slidingMessage(data.sql_query);</span>
              <span class="hide text_direction">ltr</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
    }

    public function testWithoutEvents(): void
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
            'SHOW EVENTS FROM `test_db`',
            [],
            ['Db', 'Name', 'Definer', 'Type', 'Status']
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE']
        );
        $dummyDbi->addResult(
            'SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'',
            [['event_scheduler', 'OFF']],
            ['Variable_name', 'Value']
        );
        // phpcs:enable
        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        (new EventsController($response, $template, 'test_db', new Events($dbi, $template, $response), $dbi))();

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Events" alt="Events" class="icon ic_b_events">&nbsp;Events</span>
    <a href="./url.php?url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fevents.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
  </h2>

  <div class="d-flex flex-wrap my-3">
    <div>
      <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/events&db=test_db&add_item=1&server=2&lang=en" role="button">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Create new event" alt="Create new event" class="icon ic_b_event_add">&nbsp;Create new event</span>
      </a>
    </div>
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/database/events&server=2&lang=en">
    <input type="hidden" name="db" value="test_db"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">

    <div id="nothing2display">
      <div class="alert alert-primary" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> There are no events to display.
</div>

    </div>

    <table id="eventsTable" class="table table-striped table-hover hide w-auto data">
      <thead>
      <tr>
        <th></th>
        <th>Name</th>
        <th>Status</th>
        <th>Type</th>
        <th colspan="3"></th>
      </tr>
      </thead>
      <tbody>
      <tr class="hide"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>

            </tbody>
    </table>
  </form>

  <div class="card mt-3">
    <div class="card-header">Event scheduler status</div>
    <div class="card-body">
      <div class="wrap">
        <div class="wrapper toggleAjax hide">
          <div class="toggleButton">
            <div title="Click to toggle" class="toggle-container off">
              <img src="">
              <table>
                <tbody>
                <tr>
                  <td class="toggleOn">
                  <span class="hide">index.php?route=/sql&db=test_db&goto=index.php%3Froute%3D%2Fdatabase%2Fevents%26db%3Dtest_db%26server%3D2%26lang%3Den&sql_query=SET+GLOBAL+event_scheduler%3D%22ON%22&server=2&lang=en</span>
                    <div>ON</div>
                  </td>
                  <td><div>&nbsp;</div></td>
                  <td class="toggleOff">
                  <span class="hide">index.php?route=/sql&db=test_db&goto=index.php%3Froute%3D%2Fdatabase%2Fevents%26db%3Dtest_db%26server%3D2%26lang%3Den&sql_query=SET+GLOBAL+event_scheduler%3D%22OFF%22&server=2&lang=en</span>
                    <div>OFF</div>
                  </td>
                </tr>
                </tbody>
              </table>
              <span class="hide callback">Functions.slidingMessage(data.sql_query);</span>
              <span class="hide text_direction">ltr</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
    }
}
