<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\FirstNormalForm\FirstStepController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function in_array;

/** @covers \PhpMyAdmin\Controllers\Normalization\FirstNormalForm\FirstStepController */
class FirstStepControllerTest extends AbstractTestCase
{
    /**
     * @psalm-param '1nf'|'2nf'|'3nf' $expectedNormalizeTo
     *
     * @dataProvider providerForTestDefault
     */
    public function testDefault(string|null $normalizeTo, string $expectedNormalizeTo): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['normalizeTo', null, $normalizeTo]]);

        $controller = new FirstStepController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($request);

        $files = $response->getHeader()->getScripts()->getFiles();
        $this->assertTrue(
            in_array(['name' => 'normalization.js', 'fire' => 1], $files, true),
            'normalization.js script was not included in the response.',
        );
        $this->assertTrue(
            in_array(['name' => 'vendor/jquery/jquery.uitablefilter.js', 'fire' => 0], $files, true),
            'vendor/jquery/jquery.uitablefilter.js script was not included in the response.',
        );

        $output = $response->getHTMLResult();
        $this->assertStringContainsString('First step of normalization (1NF)', $output);
        $this->assertStringContainsString(
            '<div id=\'mainContent\' data-normalizeto=\'' . $expectedNormalizeTo . '\'>',
            $output,
        );
        $this->assertStringContainsString('<option value=\'no_such_col\'>No such column</option>', $output);
    }

    /** @return array<int, array{string|null, '1nf'|'2nf'|'3nf'}> */
    public static function providerForTestDefault(): iterable
    {
        return [[null, '1nf'], ['', '1nf'], ['invalid', '1nf'], ['1nf', '1nf'], ['2nf', '2nf'], ['3nf', '3nf']];
    }
}
