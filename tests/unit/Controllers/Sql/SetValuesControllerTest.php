<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Sql;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Sql\SetValuesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SetValuesController::class)]
class SetValuesControllerTest extends AbstractTestCase
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

    public function testError(): void
    {
        $this->dummyDbi->addResult('SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'', false);
        $this->dummyDbi->addResult('SHOW INDEXES FROM `cvv`.`enums`', false);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'db' => 'cvv',
                'table' => 'enums',
                'column' => 'set',
                'curr_value' => 'b&c',
            ]);

        Current::$database = 'cvv';
        Current::$table = 'enums';

        $responseRenderer = new ResponseRenderer();
        $template = new Template();
        $relation = new Relation($this->dbi);
        $bookmarkRepository = new BookmarkRepository($this->dbi, $relation);
        $sql = new Sql(
            $this->dbi,
            $relation,
            self::createStub(RelationCleanup::class),
            self::createStub(Transformations::class),
            $template,
            $bookmarkRepository,
            Config::getInstance(),
        );

        $sqlController = new SetValuesController($responseRenderer, $template, $sql);
        $sqlController($request);

        self::assertFalse($responseRenderer->hasSuccessState(), 'expected the request to fail');

        self::assertSame(['message' => 'Error in processing request'], $responseRenderer->getJSONResult());
    }

    public function testSuccess(): void
    {
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'',
            [
                [
                    'set',
                    'set(\'<script>alert("ok")</script>\',\'a&b\',\'b&c\',\'vrai&amp\',\'\')',
                    'No',
                    '',
                    'NULL',
                    '',
                ],
            ],
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
        );
        $this->dummyDbi->addResult('SHOW INDEXES FROM `cvv`.`enums`', []);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'db' => 'cvv',
                'table' => 'enums',
                'column' => 'set',
                'curr_value' => 'b&c',
            ]);

        Current::$database = 'cvv';
        Current::$table = 'enums';

        $responseRenderer = new ResponseRenderer();
        $template = new Template();
        $relation = new Relation($this->dbi);
        $bookmarkRepository = new BookmarkRepository($this->dbi, $relation);
        $sql = new Sql(
            $this->dbi,
            $relation,
            self::createStub(RelationCleanup::class),
            self::createStub(Transformations::class),
            $template,
            $bookmarkRepository,
            Config::getInstance(),
        );

        $sqlController = new SetValuesController($responseRenderer, $template, $sql);
        $sqlController($request);

        self::assertTrue($responseRenderer->hasSuccessState(), 'expected the request not to fail');

        self::assertSame(
            [
                'select' => '<select class="resize-vertical" size="5" multiple>' . "\n"
                    . '      <option value="&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;">'
                    . '&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;</option>' . "\n"
                    . '      <option value="a&amp;b">a&amp;b</option>' . "\n"
                    . '      <option value="b&amp;c" selected>b&amp;c</option>' . "\n"
                    . '      <option value="vrai&amp;amp">vrai&amp;amp</option>' . "\n"
                    . '      <option value=""></option>' . "\n"
                    . '  </select>' . "\n",
            ],
            $responseRenderer->getJSONResult(),
        );
    }
}
