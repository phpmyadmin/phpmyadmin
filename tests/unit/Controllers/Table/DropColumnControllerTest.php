<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Table\DropColumnController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DropColumnController::class)]
class DropColumnControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testDropColumnController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'selected' => ['name', 'datetimefield'],
            'mult_btn' => 'Yes',
        ];
        $_SESSION = [' PMA_token ' => 'token'];

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('ALTER TABLE `test_table` DROP `name`, DROP `datetimefield`;', true);
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $flashMessenger = new FlashMessenger();
        (new DropColumnController(
            new ResponseRenderer(),
            $dbi,
            $flashMessenger,
            new RelationCleanup($dbi, new Relation($dbi)),
        ))(self::createStub(ServerRequest::class));

        self::assertSame(
            [['context' => 'success', 'message' => '2 columns have been dropped successfully.', 'statement' => '']],
            $flashMessenger->getCurrentMessages(),
        );
    }
}
