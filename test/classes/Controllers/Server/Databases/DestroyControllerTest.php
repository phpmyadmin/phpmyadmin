<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Databases;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Server\Databases\DestroyController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function __;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Databases\DestroyController
 */
class DestroyControllerTest extends AbstractTestCase
{
    public function testDropDatabases(): void
    {
        global $cfg;

        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $cfg['AllowUserDropDatabase'] = true;

        $controller = new DestroyController(
            $response,
            new Template(),
            $dbi,
            new Transformations(),
            new RelationCleanup($dbi, new Relation($dbi))
        );

        $controller();
        $actual = $response->getJSONResult();

        self::assertArrayHasKey('message', $actual);
        self::assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual['message']);
        self::assertStringContainsString(__('No databases selected.'), $actual['message']);
    }
}
