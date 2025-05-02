<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Export\Template\UpdateController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateController::class)]
class UpdateControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testUpdate(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'user';

        $response = new ResponseRenderer();
        $request = self::createStub(ServerRequest::class);

        (new UpdateController(
            $response,
            new TemplateModel($this->dbi),
            new Relation($this->dbi),
            $config,
        ))($request);

        self::assertTrue($response->hasSuccessState());
    }
}
