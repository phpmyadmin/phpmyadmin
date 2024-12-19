<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Console\Bookmark;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console;
use PhpMyAdmin\Controllers\Console\Bookmark\RefreshController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RefreshController::class)]
class RefreshControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $response = new ResponseRenderer();
        $dbi = DatabaseInterface::getInstance();
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $template = new Template();
        $controller = new RefreshController(
            $response,
            new Console($relation, $template, $bookmarkRepository),
        );
        $controller(self::createStub(ServerRequest::class));
        self::assertSame(['console_message_bookmark' => ''], $response->getJSONResult());
    }
}
