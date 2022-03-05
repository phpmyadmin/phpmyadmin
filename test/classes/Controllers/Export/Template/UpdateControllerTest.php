<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Export\Template\UpdateController;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
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
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $GLOBALS['cfg']['Server']['user'] = 'user';

        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['templateId', null, '1'],
            ['templateData', '', 'data'],
        ]);

        (new UpdateController(
            $response,
            new Template(),
            new TemplateModel($this->dbi),
            new Relation($this->dbi)
        ))($request);

        $this->assertTrue($response->hasSuccessState());
    }
}
