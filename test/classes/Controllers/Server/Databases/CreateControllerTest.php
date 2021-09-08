<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Databases;

use PhpMyAdmin\Controllers\Server\Databases\CreateController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function __;
use function sprintf;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Databases\CreateController
 */
final class CreateControllerTest extends AbstractTestCase
{
    public function testCreateDatabase(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = '';

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $template = new Template();
        $controller = new CreateController($response, $template, $this->dbi);

        $_POST['new_db'] = 'test_db_error';

        $controller();
        $actual = $response->getJSONResult();

        $this->assertArrayHasKey('message', $actual);
        $this->assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual['message']);

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new CreateController($response, $template, $this->dbi);

        $_POST['new_db'] = 'test_db';
        $_POST['db_collation'] = 'utf8_general_ci';

        $controller();
        $actual = $response->getJSONResult();

        $this->assertArrayHasKey('message', $actual);
        $this->assertStringContainsString('<div class="alert alert-success" role="alert">', $actual['message']);
        $this->assertStringContainsString(
            sprintf(__('Database %1$s has been created.'), 'test_db'),
            $actual['message']
        );
    }
}
