<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Export\Template\UpdateController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(UpdateController::class)]
final class UpdateControllerTest extends AbstractTestCase
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

    public function testWithoutTemplatesFeature(): void
    {
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, RelationParameters::fromArray([]));

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

    public function testWithTemplatesFeature(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::USER => 'test_user',
            RelationParameters::DATABASE => 'pma_db',
            RelationParameters::EXPORT_TEMPLATES_WORK => true,
            RelationParameters::EXPORT_TEMPLATES => 'export_templates',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $config = new Config();
        $config->selectedServer['user'] = 'test_user';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult('UPDATE `pma_db`.`export_templates` SET `template_data` = \'{\"quick_or_custom\":\"quick\"}\' WHERE `id` = 1 AND `username` = \'test_user\';', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $responseRenderer = new ResponseRenderer();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')->withParsedBody([
            'exportType' => 'server',
            'templateId' => '1',
            'templateData' => '{"quick_or_custom":"quick"}',
        ]);

        $response = (new UpdateController(
            $responseRenderer,
            new TemplateModel($dbi),
            new Relation($dbi, $config),
            $config,
        ))($request);

        $dbiDummy->assertAllQueriesConsumed();
        self::assertTrue($responseRenderer->hasSuccessState());
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
    }
}
