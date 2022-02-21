<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ZoomSearchController;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\ZoomSearchController
 */
class ZoomSearchControllerTest extends AbstractTestCase
{
    public function testZoomSearchController(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);

        $response = new ResponseRenderer();
        $template = new Template();
        $controller = new ZoomSearchController(
            $response,
            $template,
            new Search($this->dbi),
            new Relation($this->dbi),
            $this->dbi
        );
        $controller();

        $expected = $template->render('table/zoom_search/index', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'goto' => 'index.php?route=/sql&server=2&lang=en',
            'self' => $controller,
            'geom_column_flag' => false,
            'column_names' => ['id', 'name', 'datetimefield'],
            'data_label' => 'name',
            'keys' => [],
            'criteria_column_names' => null,
            'criteria_column_types' => null,
            'max_plot_limit' => 500,
        ]);

        $this->assertSame($expected, $response->getHTMLResult());
    }
}
