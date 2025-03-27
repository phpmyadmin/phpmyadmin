<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export\Template;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Export\Template\DeleteController;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Export\Template\DeleteController
 */
class DeleteControllerTest extends AbstractTestCase
{
    public function testDelete(): void
    {
        global $cfg;

        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $cfg['Server']['user'] = 'user';

        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturn('1');

        (new DeleteController(
            $response,
            new Template(),
            new TemplateModel($this->dbi),
            new Relation($this->dbi)
        ))($request);

        self::assertTrue($response->hasSuccessState());
    }
}
