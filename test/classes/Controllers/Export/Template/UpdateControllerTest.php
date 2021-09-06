<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use PhpMyAdmin\Controllers\Export\Template\UpdateController;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Export\Template\UpdateController
 */
class UpdateControllerTest extends AbstractTestCase
{
    public function testUpdate(): void
    {
        global $cfg;

        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $cfg['Server']['user'] = 'user';

        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['templateId', null, '1'],
            ['templateData', '', 'data'],
        ]);

        (new UpdateController(
            $response,
            $template,
            new TemplateModel($this->dbi),
            new Relation($this->dbi, $template)
        ))($request);

        $this->assertTrue($response->hasSuccessState());
    }
}
