<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Table\DropColumnController;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\DropColumnController
 */
class DropColumnControllerTest extends AbstractTestCase
{
    public function testDropColumnController(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'selected' => ['name', 'datetimefield'],
            'mult_btn' => 'Yes',
        ];
        $_SESSION = [' PMA_token ' => 'token'];

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('ALTER TABLE `test_table` DROP `name`, DROP `datetimefield`;', []);
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $this->assertArrayNotHasKey('flashMessages', $_SESSION);

        (new DropColumnController(
            new ResponseRenderer(),
            new Template(),
            $dbi,
            new FlashMessages(),
            new RelationCleanup($dbi, new Relation($dbi))
        ))();

        $this->assertArrayHasKey('flashMessages', $_SESSION);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame(['success' => ['2 columns have been dropped successfully.']], $_SESSION['flashMessages']);
    }
}
