<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\AddNewPrimaryController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AddNewPrimaryController::class)]
class AddNewPrimaryControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbiDummy = $this->createDbiDummy();

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $relation = new Relation($dbi);
        $controller = new AddNewPrimaryController(
            $response,
            new Normalization($dbi, $relation, new Transformations($dbi, $relation), $template),
            new UserPrivilegesFactory($dbi),
        );
        $controller(self::createStub(ServerRequest::class));

        self::assertStringContainsString('<table id="table_columns"', $response->getHTMLResult());
    }
}
