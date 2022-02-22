<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\Table\SqlController;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\SqlController
 * @covers \PhpMyAdmin\SqlQueryForm
 */
class SqlControllerTest extends AbstractTestCase
{
    public function testSqlController(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server'] = $GLOBALS['config']->defaultServer;

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);

        $pageSettings = new PageSettings('Sql');
        $fields = $this->dbi->getColumns('test_db', 'test_table', true);
        $template = new Template();

        $expected = $pageSettings->getHTML();
        $expected .= $template->render('sql/query', [
            'legend' => 'Run SQL query/queries on table <a href="'
                . 'index.php?route=/sql&server=2&lang=en&db=test_db&table=test_table&server=2&lang=en'
                . '">test_db.test_table</a>: ' . MySQLDocumentation::show('SELECT'),
            'textarea_cols' => 40,
            'textarea_rows' => 15,
            'textarea_auto_select' => false,
            'columns_list' => [
                'id' => $fields['id'],
                'name' => $fields['name'],
                'datetimefield' => $fields['datetimefield'],
            ],
            'codemirror_enable' => true,
            'has_bookmark' => false,
            'delimiter' => ';',
            'retain_query_box' => false,
            'is_upload' => true,
            'db' => 'test_db',
            'table' => 'test_table',
            'goto' => 'index.php?route=/table/sql&server=2&lang=en',
            'query' => 'SELECT * FROM `test_table` WHERE 1',
            'display_tab' => 'full',
            'bookmarks' => [],
            'can_convert_kanji' => false,
            'is_foreign_key_check' => true,
        ]);

        $response = new ResponseRenderer();
        (new SqlController($response, $template, new SqlQueryForm($template)))();
        $this->assertSame($expected, $response->getHTMLResult());
    }
}
