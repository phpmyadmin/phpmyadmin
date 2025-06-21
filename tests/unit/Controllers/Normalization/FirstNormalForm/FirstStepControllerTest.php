<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\FirstNormalForm\FirstStepController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FirstStepController::class)]
class FirstStepControllerTest extends AbstractTestCase
{
    /** @psalm-param '1nf'|'2nf'|'3nf' $expectedNormalizeTo */
    #[DataProvider('providerForTestDefault')]
    public function testDefault(string|null $normalizeTo, string $expectedNormalizeTo): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['normalizeTo' => $normalizeTo]);

        $relation = new Relation($dbi);
        $controller = new FirstStepController(
            $response,
            new Normalization($dbi, $relation, new Transformations($dbi, $relation), $template),
        );
        $controller($request);

        $files = $response->getHeader()->getScripts()->getFiles();
        self::assertContains(
            ['name' => 'normalization.js', 'fire' => 1],
            $files,
            'normalization.js script was not included in the response.',
        );
        self::assertContains(
            ['name' => 'vendor/jquery/jquery.uitablefilter.js', 'fire' => 0],
            $files,
            'vendor/jquery/jquery.uitablefilter.js script was not included in the response.',
        );

        $output = $response->getHTMLResult();
        self::assertStringContainsString('First step of normalization (1NF)', $output);
        self::assertStringContainsString(
            '<div class="card" id="mainContent" data-normalizeto="' . $expectedNormalizeTo . '">',
            $output,
        );
        self::assertStringContainsString('<option value=\'no_such_col\'>No such column</option>', $output);
    }

    /** @return array<int, array{string|null, '1nf'|'2nf'|'3nf'}> */
    public static function providerForTestDefault(): iterable
    {
        return [[null, '1nf'], ['', '1nf'], ['invalid', '1nf'], ['1nf', '1nf'], ['2nf', '2nf'], ['3nf', '3nf']];
    }
}
