<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\ReplaceController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Tests\AbstractTestCase;

class ReplaceControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        parent::defineVersionConstants();
        parent::setLanguage();
        parent::setTheme();
        parent::setGlobalDbi();
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        parent::loadResponseIntoContainerBuilder();
        $GLOBALS['db'] = 'my_db';
        $GLOBALS['table'] = 'test_tbl';

        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = [
            'PMA_VERSION' => PMA_VERSION,
            'table_coords' => 'table_name',
            'displaywork' => 'displaywork',
            'db' => 'information_schema',
            'table_info' => 'table_info',
            'relwork' => 'relwork',
            'relation' => 'relation',
            'mimework' => 'mimework',
            'commwork' => 'commwork',
            'column_info' => 'column_info',
            'pdf_pages' => 'pdf_pages',
            'bookmarkwork' => 'bookmarkwork',
            'bookmark' => 'bookmark',
            'uiprefswork' => 'uiprefswork',
            'table_uiprefs' => 'table_uiprefs',
        ];
    }

    public function testReplace(): void
    {
        global $containerBuilder;
        $GLOBALS['url_params'] = [];
        Response::getInstance()->setAjax(true);
        $_POST['db'] = $GLOBALS['db'];
        $_POST['table'] = $GLOBALS['table'];
        $_POST['ajax_request'] = 'true';
        $_POST['sql_query'] = '';
        $_POST['clause_is_unique'] = 1;
        $_POST['where_clause'] = [
            '`test`.`ser` = 2',
            '`test`.`ser` = 1',
        ];
        $_POST['rel_fields_list'] = '';
        $_POST['do_transformations'] = true;
        $_POST['transform_fields_list'] = '0%5Bvc%5D=sss%20s%20s&1%5Bvc%5D=zzff%20s%20sf%0A';
        $_POST['relational_display'] = 'K';
        $_POST['goto'] = 'index.php?route=/sql';
        $_POST['submit_type'] = 'save';
        $_POST['fields'] = [
            'multi_edit' => [
                0 => ['zzff s sf'],
                1 => ['sss s s'],
            ],
        ];
        $_POST['fields_name'] = [
            'multi_edit' => [
                0 => ['vc'],
                1 => ['vc'],
            ],
        ];
        $_POST['fields_null'] = [
            'multi_edit' => [
                0 => [],
                1 => [],
            ],
        ];
        $GLOBALS['goto'] = 'index.php?route=/sql';
        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var ReplaceController $replaceController */
        $replaceController = $containerBuilder->get(ReplaceController::class);
        $replaceController->index();
        $this->assertStringContainsString(
            'class="icon ic_s_success"> Showing rows 0 -  1 (2 total, Query took',
            $this->getResponseHtmlResult()
        );
        $this->assertStringContainsString(
            'SELECT * FROM `test_tbl`',
            $this->getResponseHtmlResult()
        );
    }
}
