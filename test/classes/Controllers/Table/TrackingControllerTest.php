<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\TrackingController;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Tracking;

/**
 * @covers \PhpMyAdmin\Controllers\Table\TrackingController
 */
class TrackingControllerTest extends AbstractTestCase
{
    public function testTrackingController(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['cfg']['Server']['tracking_default_statements'] = 'CREATE TABLE,ALTER TABLE,DROP TABLE';

        $this->dummyDbi->addSelectDb('test_db');

        $response = new ResponseRenderer();
        $template = new Template();
        (new TrackingController(
            $response,
            $template,
            new Tracking(new SqlQueryForm($template), $template, new Relation($this->dbi), $this->dbi)
        ))();

        $main = $template->render('table/tracking/main', [
            'url_params' => [
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
                'goto' => 'index.php?route=/table/tracking&server=2&lang=en',
                'back' => 'index.php?route=/table/tracking&server=2&lang=en',
            ],
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'selectable_tables_num_rows' => 0,
            'selectable_tables_entries' => [],
            'selected_table' => null,
            'last_version' => 0,
            'versions' => [],
            'type' => 'table',
            'default_statements' => $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'text_dir' => 'ltr',
        ]);
        $expected = $template->render('table/tracking/index', [
            'active_message' => '',
            'action_message' => '',
            'delete_version' => '',
            'create_version' => '',
            'deactivate_tracking' => '',
            'activate_tracking' => '',
            'message' => '',
            'sql_dump' => '',
            'schema_snapshot' => '',
            'tracking_report_rows' => '',
            'tracking_report' => '',
            'main' => $main,
        ]);

        $this->assertSame($expected, $response->getHTMLResult());
    }
}
