<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Table\ReplaceController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Controllers\Table\ReplaceController
 */
class ReplaceControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'table_coords' => 'table_name',
            'displaywork' => true,
            'db' => 'information_schema',
            'table_info' => 'table_info',
            'relwork' => true,
            'relation' => 'relation',
            'mimework' => true,
            'commwork' => true,
            'column_info' => 'column_info',
            'pdf_pages' => 'pdf_pages',
            'bookmarkwork' => true,
            'bookmark' => 'bookmark',
            'uiprefswork' => true,
            'table_uiprefs' => 'table_uiprefs',
        ])->toArray();
    }

    public function testReplace(): void
    {
        global $containerBuilder;
        $GLOBALS['urlParams'] = [];
        ResponseRenderer::getInstance()->setAjax(true);
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
        $this->dummyDbi->addSelectDb('my_db');
        $this->dummyDbi->addSelectDb('my_db');
        $replaceController();
        $this->assertAllSelectsConsumed();
        self::assertStringContainsString(
            'class="icon ic_s_success"> Showing rows 0 -  1 (2 total, Query took',
            $this->getResponseHtmlResult()
        );
        self::assertStringContainsString('SELECT * FROM `test_tbl`', $this->getResponseHtmlResult());
    }

    public function testIsInsertRow(): void
    {
        global $containerBuilder;
        $GLOBALS['urlParams'] = [];
        $GLOBALS['goto'] = 'index.php?route=/sql';
        $_POST['insert_rows'] = 5;
        $_POST['sql_query'] = 'SELECT 1';
        $GLOBALS['cfg']['InsertRows'] = 2;
        $GLOBALS['cfg']['Server']['host'] = 'host.tld';
        $GLOBALS['cfg']['Server']['verbose'] = '';

        $this->dummyDbi->addResult(
            'SHOW TABLES LIKE \'test_tbl\';',
            [
                ['test_tbl'],
            ]
        );

        $this->dummyDbi->addResult(
            'SELECT * FROM `my_db`.`test_tbl` LIMIT 1;',
            []
        );

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var ReplaceController $replaceController */
        $replaceController = $containerBuilder->get(ReplaceController::class);
        $this->dummyDbi->addSelectDb('my_db');
        $this->dummyDbi->addSelectDb('my_db');
        $this->dummyDbi->addSelectDb('my_db');
        $replaceController();
        $this->assertAllSelectsConsumed();
        self::assertSame(5, $GLOBALS['cfg']['InsertRows']);
        self::assertStringContainsString('<form id="continueForm" method="post" '
        . 'action="index.php?route=/table/replace&lang=en" name="continueForm">', $this->getResponseHtmlResult());
        self::assertStringContainsString('Continue insertion with         <input type="number" '
        . 'name="insert_rows" id="insert_rows" value="5" min="1">', $this->getResponseHtmlResult());
    }
}
