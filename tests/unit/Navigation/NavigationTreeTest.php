<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\NavigationTree;
use PhpMyAdmin\Navigation\Nodes\NodeIndex;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UserPrivileges;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NavigationTree::class)]
class NavigationTreeTest extends AbstractTestCase
{
    protected NavigationTree $object;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $config = Config::getInstance();

        $this->object = new NavigationTree(new Template($config), $dbi, new Relation($dbi), $config);
    }

    /**
     * Very basic rendering test.
     */
    public function testRenderState(): void
    {
        $result = $this->object->renderState(new ResponseRenderer(), new UserPrivileges());
        self::assertStringContainsString('recentFavoriteTablesWrapper', $result);
    }

    /**
     * Very basic path rendering test.
     */
    public function testRenderPath(): void
    {
        $result = $this->object->renderPath(new ResponseRenderer(), new UserPrivileges());
        self::assertIsString($result);
        self::assertStringContainsString('list_container', $result);
    }

    /**
     * Very basic select rendering test.
     */
    public function testRenderDbSelect(): void
    {
        $result = $this->object->renderDbSelect(new ResponseRenderer(), new UserPrivileges());
        self::assertStringContainsString('pma_navigation_select_database', $result);
    }

    public function testDatabaseGrouping(): void
    {
        Current::$database = '';
        $config = Config::getInstance();
        $config->set('NavigationTreeDbSeparator', '__');

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, (SELECT DB_first_level FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'__\', 1) DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE(CONCAT(DB_first_level, \'__\'), CONCAT(SCHEMA_NAME, \'__\')) ORDER BY SCHEMA_NAME ASC',
            [['functions__a'], ['functions__b']],
            ['SCHEMA_NAME'],
        );
        $dummyDbi->addResult(
            'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'__\', 1) DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t',
            [['2']],
        );
        $dummyDbi->addResult(
            'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'__\', 1) DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t',
            [['2']],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $object = new NavigationTree(new Template($config), $dbi, new Relation($dbi), $config);
        $result = $object->renderState(new ResponseRenderer(), new UserPrivileges());
        self::assertStringContainsString('<li class="first navGroup">', $result);
        self::assertStringContainsString('functions' . "\n", $result);
        self::assertStringContainsString('<div class="list_container" style="display: none;">', $result);
        self::assertStringContainsString('functions__a', $result);
        self::assertStringContainsString('functions__b', $result);
    }

    /**
     * Regression test for https://github.com/phpmyadmin/phpmyadmin/issues/20253:
     * The link to a specific index in the navigation tree must NOT have the
     * `disableAjax` class. The link relies on `data-post` for `db`/`table`/
     * `index`, which is consumed by the AJAX request handler — with
     * `disableAjax` the browser navigates natively to the bare href and the
     * controller fails with "Missing parameter: db".
     */
    public function testIndexNodeLinkUsesAjaxFlow(): void
    {
        $config = new Config();
        $template = new Template($config);
        $node = new NodeIndex($this->createDatabaseInterface(), $config, 'idx_mail');

        $output = $template->render('navigation/tree/node', [
            'node' => $node,
            'displayName' => 'idx_mail',
            'class' => '',
            'show_node' => true,
            'has_siblings' => false,
            'li_classes' => '',
            'control_buttons' => '',
            'node_is_container' => false,
            'has_second_icon' => false,
            'recursive' => ['html' => '', 'has_wrapper' => false, 'is_hidden' => false],
            'icon_links' => [],
            'text_link' => [
                'route' => '/table/indexes',
                'params' => ['db' => 'testdb', 'table' => 'users', 'index' => 'idx_mail'],
                'title' => 'Edit',
                'is_ajax' => false,
            ],
            'pagination_params' => [],
            'node_is_group' => false,
            'link_classes' => '',
            'paths' => ['a_path' => '', 'v_path' => '', 'pos' => 0],
            'node_icon' => '',
        ]);

        self::assertStringContainsString('data-post=', $output);
        self::assertStringNotContainsString('class="hover_show_full disableAjax"', $output);
    }
}
