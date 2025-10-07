<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\Structure\FavoriteTableController;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use ReflectionClass;

use function json_encode;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Controllers\Database\Structure\FavoriteTableController
 */
class FavoriteTableControllerTest extends AbstractTestCase
{
    public function testSynchronizeFavoriteTables(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $favoriteInstance = $this->getMockBuilder(RecentFavoriteTable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $favoriteInstance->expects($this->exactly(2))
            ->method('getTables')
            ->will($this->onConsecutiveCalls([[]], [['db' => 'db', 'table' => 'table']]));

        $class = new ReflectionClass(FavoriteTableController::class);
        $method = $class->getMethod('synchronizeFavoriteTables');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $controller = new FavoriteTableController(
            new ResponseStub(),
            new Template(),
            'db',
            new Relation($this->dbi)
        );

        // The user hash for test
        $user = 'abcdefg';
        $favoriteTable = [
            $user => [
                [
                    'db' => 'db',
                    'table' => 'table',
                ],
            ],
        ];

        $json = $method->invokeArgs($controller, [$favoriteInstance, $user, $favoriteTable]);

        self::assertSame(json_encode($favoriteTable), $json['favoriteTables'] ?? '');
        self::assertArrayHasKey('list', $json);
    }
}
