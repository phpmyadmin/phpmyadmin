<?php
/**
 * Holds DatabasesControllerTest class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PhpMyAdmin\Controllers\Server\DatabasesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Transformations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use function sprintf;

/**
 * Tests for DatabasesController class
 */
class DatabasesControllerTest extends AbstractTestCase
{
    /** @var ServerRequestInterface */
    private $request;

    /** @var ResponseInterface */
    private $response;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['text_dir'] = 'text_dir';

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $this->request = $creator->fromGlobals();
        $this->response = $psr17Factory->createResponse();
    }

    public function testIndexAction(): void
    {
        global $cfg, $dblist, $is_create_db_priv;

        $dblist = new stdClass();
        $dblist->databases = [
            'sakila',
            'employees',
        ];

        $template = new Template();
        $transformations = new Transformations();
        $relationCleanup = new RelationCleanup(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi'], $template)
        );

        $response = new Response();

        $controller = new DatabasesController(
            $response,
            $GLOBALS['dbi'],
            $template,
            $transformations,
            $relationCleanup
        );

        $controller->index($this->request, $this->response);
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString('data-filter-row="SAKILA"', $actual);
        $this->assertStringContainsString('sakila', $actual);
        $this->assertStringContainsString('utf8_general_ci', $actual);
        $this->assertStringContainsString('title="Unicode, case-insensitive"', $actual);
        $this->assertStringContainsString('data-filter-row="SAKILA"', $actual);
        $this->assertStringContainsString('employees', $actual);
        $this->assertStringContainsString('latin1_swedish_ci', $actual);
        $this->assertStringContainsString('title="Swedish, case-insensitive"', $actual);
        $this->assertStringContainsString('<span id="filter-rows-count">2</span>', $actual);
        $this->assertStringContainsString('name="pos" value="0"', $actual);
        $this->assertStringContainsString('name="sort_by" value="SCHEMA_NAME"', $actual);
        $this->assertStringContainsString('name="sort_order" value="asc"', $actual);
        $this->assertStringContainsString(__('Enable statistics'), $actual);
        $this->assertStringContainsString(__('No privileges to create databases'), $actual);
        $this->assertStringNotContainsString(__('Indexes'), $actual);

        $response = new Response();

        $controller = new DatabasesController(
            $response,
            $GLOBALS['dbi'],
            $template,
            $transformations,
            $relationCleanup
        );

        $cfg['ShowCreateDb'] = true;
        $is_create_db_priv = true;
        $_REQUEST['statistics'] = '1';
        $_REQUEST['sort_by'] = 'SCHEMA_TABLES';
        $_REQUEST['sort_order'] = 'desc';

        $controller->index($this->request, $this->response);
        $actual = $response->getHTMLResult();

        $this->assertStringNotContainsString(__('Enable statistics'), $actual);
        $this->assertStringContainsString(__('Indexes'), $actual);
        $this->assertStringContainsString('name="sort_by" value="SCHEMA_TABLES"', $actual);
        $this->assertStringContainsString('name="sort_order" value="desc"', $actual);
        $this->assertStringContainsString('name="statistics" value="1"', $actual);
        $this->assertStringContainsString('title="3912174"', $actual);
        $this->assertStringContainsString('3,912,174', $actual);
        $this->assertStringContainsString('title="4358144"', $actual);
        $this->assertStringContainsString('4.2', $actual);
        $this->assertStringContainsString('MiB', $actual);
        $this->assertStringContainsString('name="db_collation"', $actual);
    }

    public function testCreateDatabaseAction(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->method('getError')
            ->willReturn('CreateDatabaseError');

        $response = new Response();
        $response->setAjax(true);

        $template = new Template();
        $transformations = new Transformations();
        $controller = new DatabasesController(
            $response,
            $dbi,
            $template,
            $transformations,
            new RelationCleanup($dbi, new Relation($dbi, $template))
        );

        $_POST['new_db'] = 'pma_test';

        $controller->create($this->request, $this->response);
        $actual = $response->getJSONResult();

        $this->assertArrayHasKey('message', $actual);
        $this->assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual['message']);
        $this->assertStringContainsString('CreateDatabaseError', $actual['message']);

        $dbi->method('tryQuery')
            ->willReturn(true);

        $response = new Response();
        $response->setAjax(true);

        $controller = new DatabasesController(
            $response,
            $dbi,
            $template,
            $transformations,
            new RelationCleanup($dbi, new Relation($dbi, $template))
        );

        $_POST['db_collation'] = 'utf8_general_ci';

        $controller->create($this->request, $this->response);
        $actual = $response->getJSONResult();

        $this->assertArrayHasKey('message', $actual);
        $this->assertStringContainsString('<div class="alert alert-success" role="alert">', $actual['message']);
        $this->assertStringContainsString(
            sprintf(__('Database %1$s has been created.'), 'pma_test'),
            $actual['message']
        );
    }

    public function testDropDatabasesAction(): void
    {
        global $cfg;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = new Response();
        $response->setAjax(true);

        $cfg['AllowUserDropDatabase'] = true;

        $template = new Template();
        $controller = new DatabasesController(
            $response,
            $dbi,
            $template,
            new Transformations(),
            new RelationCleanup($dbi, new Relation($dbi, $template))
        );

        $_POST['drop_selected_dbs'] = '1';

        $controller->destroy($this->request, $this->response);
        $actual = $response->getJSONResult();

        $this->assertArrayHasKey('message', $actual);
        $this->assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual['message']);
        $this->assertStringContainsString(__('No databases selected.'), $actual['message']);
    }
}
