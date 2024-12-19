<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\Controllers\Normalization\MainController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MainController::class)]
class MainControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'my_db';
        Current::$table = 'test_tbl';
    }

    public function testNormalization(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $response = new ResponseRenderer();

        $controller = new MainController($response);
        $controller(self::createStub(ServerRequest::class));

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
        self::assertStringContainsString(
            '<form method="post" action="index.php?route=/normalization/1nf/step1&lang=en"'
            . ' name="normalize" id="normalizeTable"',
            $output,
        );
        self::assertStringContainsString('<input type="hidden" name="db" value="test_db">', $output);
        self::assertStringContainsString('<input type="hidden" name="table" value="test_table">', $output);
        self::assertStringContainsString('type="radio" name="normalizeTo"', $output);
        self::assertStringContainsString('id="normalizeToRadio1" value="1nf" checked>', $output);
        self::assertStringContainsString('id="normalizeToRadio2" value="2nf">', $output);
        self::assertStringContainsString('id="normalizeToRadio3" value="3nf">', $output);
    }
}
