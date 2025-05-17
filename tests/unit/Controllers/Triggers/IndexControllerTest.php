<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Triggers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Triggers\IndexController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Triggers\Triggers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

#[CoversClass(IndexController::class)]
#[CoversClass(Triggers::class)]
final class IndexControllerTest extends AbstractTestCase
{
    public function testWithTriggers(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SHOW TRIGGERS FROM `test_db`',
            [['test_trigger', 'INSERT', 'test_table', 'BEGIN END', 'AFTER', 'definer@localhost']],
            ['Trigger', 'Event', 'Table', 'Statement', 'Timing', 'Definer'],
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='TRIGGER'",
            [['TRIGGER']],
            ['PRIVILEGE_TYPE'],
        );
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        $template = new Template();
        $response = new ResponseRenderer();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        (new IndexController(
            $response,
            $template,
            $dbi,
            new Triggers($dbi),
            new DbTableExists($dbi),
        ))($request);

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Triggers" alt="Triggers" class="icon ic_b_triggers">&nbsp;Triggers</span>
    <a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Ftriggers.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
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
      <a class="ajax add_anchor btn btn-primary" href="index.php?route=/triggers&db=test_db&table=&add_item=1&server=2&lang=en" role="button">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Create new trigger" alt="Create new trigger" class="icon ic_b_trigger_add">&nbsp;Create new trigger</span>
      </a>
    </div>
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/triggers&server=2&lang=en">
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
      <a href="index.php?route=/triggers&db=test_db&table=test_table&server=2&lang=en">test_table</a>
    </td>
    <td>
    AFTER
  </td>
  <td>
    INSERT
  </td>
  <td>
          <a class="ajax edit_anchor" href="index.php?route=/triggers&db=test_db&table=&edit_item=1&item_name=test_trigger&server=2&lang=en">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit">&nbsp;Edit</span>
      </a>
      </td>
  <td>
    <a class="ajax export_anchor" href="index.php?route=/triggers&db=test_db&table=&export_item=1&item_name=test_trigger&server=2&lang=en">
      <span class="text-nowrap"><img src="themes/dot.gif" title="Export" alt="Export" class="icon ic_b_export">&nbsp;Export</span>
    </a>
  </td>
  <td>
          <a href="index.php" data-post="route=/sql&server=2&lang=en&db=test_db&table=&sql_query=DROP+TRIGGER+IF+EXISTS+%60test_trigger%60&goto=index.php%3Froute%3D%2Ftriggers%26db%3Dtest_db%26server%3D2%26lang%3Den&server=2&lang=en" class="ajax drop_anchor"><span class="text-nowrap"><img src="themes/dot.gif" title="Drop" alt="Drop" class="icon ic_b_drop">&nbsp;Drop</span></a>
      </td>
</tr>
      </tbody>
    </table>
  </form>

  <div class="modal fade" id="triggersEditorModal" tabindex="-1" aria-labelledby="triggersEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="triggersEditorModalLabel">Trigger editor</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading…</span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="triggersEditorModalSaveButton">Save changes</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="triggersExportModal" tabindex="-1" aria-labelledby="triggersExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="triggersExportModalLabel">Export trigger</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
    }

    public function testWithoutTriggers(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SHOW TRIGGERS FROM `test_db`',
            [],
            ['Trigger', 'Event', 'Table', 'Statement', 'Timing', 'Definer'],
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='TRIGGER'",
            [['TRIGGER']],
            ['PRIVILEGE_TYPE'],
        );
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        $template = new Template();
        $response = new ResponseRenderer();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        (new IndexController(
            $response,
            $template,
            $dbi,
            new Triggers($dbi),
            new DbTableExists($dbi),
        ))($request);

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="container-fluid my-3">
  <h2>
    <span class="text-nowrap"><img src="themes/dot.gif" title="Triggers" alt="Triggers" class="icon ic_b_triggers">&nbsp;Triggers</span>
    <a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Ftriggers.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
  </h2>

  <div class="d-flex flex-wrap my-3">
    <div>
      <a class="ajax add_anchor btn btn-primary" href="index.php?route=/triggers&db=test_db&table=&add_item=1&server=2&lang=en" role="button">
        <span class="text-nowrap"><img src="themes/dot.gif" title="Create new trigger" alt="Create new trigger" class="icon ic_b_trigger_add">&nbsp;Create new trigger</span>
      </a>
    </div>
  </div>

  <form id="rteListForm" class="ajax" action="index.php?route=/triggers&server=2&lang=en">
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
        <tr class="hide"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>      </tbody>
    </table>
  </form>

  <div class="modal fade" id="triggersEditorModal" tabindex="-1" aria-labelledby="triggersEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="triggersEditorModalLabel">Trigger editor</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading…</span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="triggersEditorModalSaveButton">Save changes</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="triggersExportModal" tabindex="-1" aria-labelledby="triggersExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="triggersExportModalLabel">Export trigger</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

HTML;
        // phpcs:enable

        self::assertSame($expected, $actual);
    }

    /**
     * Test for getDataFromRequest
     *
     * @param array<string, string> $in  Input
     * @param array<string, string> $out Expected output
     */
    #[DataProvider('providerGetDataFromRequest')]
    public function testGetDataFromRequest(array $in, array $out): void
    {
        unset($_POST);
        foreach ($in as $key => $value) {
            if ($value === '') {
                continue;
            }

            $_POST[$key] = $value;
        }

        $method = (new ReflectionClass(IndexController::class))->getMethod('getDataFromRequest');

        $dbi = self::createStub(DatabaseInterface::class);
        DatabaseInterface::$instance = $dbi;
        $template = new Template();
        $response = new ResponseRenderer();
        $indexController = new IndexController(
            $response,
            $template,
            $dbi,
            new Triggers($dbi),
            new DbTableExists($dbi),
        );

        $request = $this->createMock(ServerRequest::class);
        $request->expects(self::any())
            ->method('getParsedBodyParam')
            ->willReturn('foo');

        $output = $method->invoke($indexController, $request);
        self::assertSame($out, $output);
    }

    /**
     * Data provider for testGetDataFromRequest
     *
     * @return array<array{array<string, string>, array<string, string>}>
     */
    public static function providerGetDataFromRequest(): array
    {
        return [
            [
                [
                    'item_name' => 'foo',
                    'item_table' => 'foo',
                    'item_original_name' => 'foo',
                    'item_action_timing' => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition' => 'foo',
                    'item_definer' => 'foo',
                ],
                [
                    'item_name' => 'foo',
                    'item_table' => 'foo',
                    'item_original_name' => 'foo',
                    'item_action_timing' => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition' => 'foo',
                    'item_definer' => 'foo',
                ],
            ],
        ];
    }
}
