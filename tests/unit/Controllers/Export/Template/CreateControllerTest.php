<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Export\Template\CreateController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(CreateController::class)]
class CreateControllerTest extends AbstractTestCase
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

    public function testCreate(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::EXPORT_TEMPLATES_WORK => true,
            RelationParameters::DATABASE => 'db',
            RelationParameters::EXPORT_TEMPLATES => 'table',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $config = Config::getInstance();
        $config->selectedServer['user'] = 'user';

        $response = new ResponseRenderer();
        $template = new Template();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'exportType' => 'raw',
                'templateName' => 'name',
                'templateData' => 'data',
                'template_id' => null,
            ]);

        (new CreateController(
            $response,
            $template,
            new TemplateModel($this->dbi),
            new Relation($this->dbi),
            $config,
        ))($request);

        $templates = [
            ExportTemplate::fromArray([
                'id' => 1,
                'username' => 'user1',
                'exportType' => 'raw',
                'name' => 'name1',
                'data' => 'data1',
            ]),
            ExportTemplate::fromArray([
                'id' => 2,
                'username' => 'user2',
                'exportType' => 'raw',
                'name' => 'name2',
                'data' => 'data2',
            ]),
        ];

        $options = $template->render('export/template_options', [
            'templates' => $templates,
            'selected_template' => null,
        ]);

        self::assertTrue($response->hasSuccessState());
        self::assertSame(['data' => $options], $response->getJSONResult());
    }
}
