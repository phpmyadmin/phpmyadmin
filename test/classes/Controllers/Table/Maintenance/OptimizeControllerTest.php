<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Maintenance;

use PhpMyAdmin\Controllers\Table\Maintenance\OptimizeController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Table\Maintenance;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Table\Maintenance\OptimizeController */
class OptimizeControllerTest extends AbstractTestCase
{
    /**
     * @param string[][]|string[]|string|null $tables
     *
     * @dataProvider providerForTestNoTableSelected
     */
    public function testNoTableSelected(array|string|null $tables): void
    {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_tbl', null, $tables]]);
        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $controller = new OptimizeController($response, new Template(), new Maintenance($dbi), $this->createConfig());
        $controller($request);
        $this->assertFalse($response->hasSuccessState());
        $this->assertSame(['message' => 'No table selected.'], $response->getJSONResult());
        $this->assertSame('', $response->getHTMLResult());
    }

    /** @return array<int, array{string[][]|string[]|string|null}> */
    public static function providerForTestNoTableSelected(): array
    {
        return [[null], [''], ['table'], [[]], [['']], [['table', '']], [[['table']]]];
    }
}
