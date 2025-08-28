<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Database\EventsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EventsController::class)]
#[CoversClass(Events::class)]
final class EventsControllerTest extends AbstractTestCase
{
    public function testWithEvents(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SHOW EVENTS FROM `test_db`',
            [['test_db', 'test_event', 'definer@localhost', 'ONE TIME', 'ENABLED']],
            ['Db', 'Name', 'Definer', 'Type', 'Status'],
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            'SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'',
            [['event_scheduler', 'OFF']],
            ['Variable_name', 'Value'],
        );
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        (new EventsController(
            $response,
            $template,
            new Events($dbi),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="container-fluid my-3">
              <h2>
                <span class="text-nowrap"><img src="themes/dot.gif" title="Events" alt="Events" class="icon ic_b_events">&nbsp;Events</span>
                <a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fevents.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
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

              <form id="rteListForm" class="disableAjax" action="index.php?route=/database/events&server=2&lang=en">
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
                          <img src="./themes/pmahomme/img/toggle-ltr.png">
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
                          <span class="hide callback">window.pmaSlidingMessage(data.sql_query);</span>
                          <span class="hide text_direction">ltr</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="eventsEditorModal" tabindex="-1" aria-labelledby="eventsEditorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="eventsEditorModalLabel">Event editor</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading…</span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary" id="eventsEditorModalSaveButton">Save changes</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="eventsExportModal" tabindex="-1" aria-labelledby="eventsExportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="eventsExportModalLabel">Export event</h1>
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

    public function testWithoutEvents(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SHOW EVENTS FROM `test_db`',
            [],
            ['Db', 'Name', 'Definer', 'Type', 'Status'],
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            'SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'',
            [['event_scheduler', 'OFF']],
            ['Variable_name', 'Value'],
        );
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        (new EventsController(
            $response,
            $template,
            new Events($dbi),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $actual = $response->getHTMLResult();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="container-fluid my-3">
              <h2>
                <span class="text-nowrap"><img src="themes/dot.gif" title="Events" alt="Events" class="icon ic_b_events">&nbsp;Events</span>
                <a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fevents.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
              </h2>

              <div class="d-flex flex-wrap my-3">
                <div>
                  <a class="ajax add_anchor btn btn-primary" href="index.php?route=/database/events&db=test_db&add_item=1&server=2&lang=en" role="button">
                    <span class="text-nowrap"><img src="themes/dot.gif" title="Create new event" alt="Create new event" class="icon ic_b_event_add">&nbsp;Create new event</span>
                  </a>
                </div>
              </div>

              <form id="rteListForm" class="disableAjax" action="index.php?route=/database/events&server=2&lang=en">
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
                          <img src="./themes/pmahomme/img/toggle-ltr.png">
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
                          <span class="hide callback">window.pmaSlidingMessage(data.sql_query);</span>
                          <span class="hide text_direction">ltr</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="eventsEditorModal" tabindex="-1" aria-labelledby="eventsEditorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="eventsEditorModalLabel">Event editor</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading…</span>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-primary" id="eventsEditorModalSaveButton">Save changes</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal fade" id="eventsExportModal" tabindex="-1" aria-labelledby="eventsExportModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="eventsExportModalLabel">Export event</h1>
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
}
