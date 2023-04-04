<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Export\Template\CreateController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use ReflectionClass;

/** @covers \PhpMyAdmin\Controllers\Export\Template\CreateController */
class CreateControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testCreate(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';

        $relationParameters = RelationParameters::fromArray([
            'exporttemplateswork' => true,
            'db' => 'db',
            'export_templates' => 'table',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $GLOBALS['cfg']['Server']['user'] = 'user';

        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['exportType', '', 'type'],
            ['templateName', '', 'name'],
            ['templateData', '', 'data'],
            ['template_id', null, null],
        ]);

        (new CreateController(
            $response,
            $template,
            new TemplateModel($this->dbi),
            new Relation($this->dbi),
        ))($request);

        $templates = [
            ExportTemplate::fromArray([
                'id' => 1,
                'username' => 'user1',
                'exportType' => 'type1',
                'name' => 'name1',
                'data' => 'data1',
            ]),
            ExportTemplate::fromArray([
                'id' => 2,
                'username' => 'user2',
                'exportType' => 'type2',
                'name' => 'name2',
                'data' => 'data2',
            ]),
        ];

        $options = $template->render('export/template_options', [
            'templates' => $templates,
            'selected_template' => null,
        ]);

        $this->assertTrue($response->hasSuccessState());
        $this->assertEquals(['data' => $options], $response->getJSONResult());
    }
}
