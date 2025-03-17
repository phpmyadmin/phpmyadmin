<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Sql;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Sql\EnumValuesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EnumValuesController::class)]
class EnumValuesControllerTest extends AbstractTestCase
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

    public function testGetEnumValuesError(): void
    {
        $this->dummyDbi->addResult('SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`,'
            . ' `COLLATION_NAME` AS `Collation`,'
            . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
            . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
            . ' `COLUMN_COMMENT` AS `Comment`'
            . ' FROM `information_schema`.`COLUMNS`'
            . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'cvv\' AND `TABLE_NAME`'
            . ' COLLATE utf8_bin = \'enums\' AND `COLUMN_NAME` = \'set\' ORDER BY `ORDINAL_POSITION`', false);
        $this->dummyDbi->addResult('SHOW INDEXES FROM `cvv`.`enums`', false);

        Current::$database = 'cvv';
        Current::$table = 'enums';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
        ->withParsedBody([
            'db' => 'cvv',
            'table' => 'enums',
            'column' => 'set',
            'curr_value' => 'b&c',
        ]);

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

        $sqlController = new EnumValuesController($responseRenderer, $template, $sql);
        $sqlController($request);

        self::assertFalse($responseRenderer->hasSuccessState(), 'expected the request to fail');

        self::assertSame(['message' => 'Error in processing request'], $responseRenderer->getJSONResult());
    }

    public function testGetEnumValuesSuccess(): void
    {
        $this->dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'cvv\' AND `TABLE_NAME` COLLATE utf8_bin = \'enums\''
                . ' AND `COLUMN_NAME` = \'set\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [
                [
                    'set',
                    "set('<script>alert(\"ok\")</script>','a&b','b&c','vrai&amp','','漢字','''','\\\\','\"\\\\''')",
                    null,
                    'No',
                    '',
                    'NULL',
                    '',
                    '',
                    '',
                ],
            ],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );
        $this->dummyDbi->addResult('SHOW INDEXES FROM `cvv`.`enums`', []);

        Current::$database = 'cvv';
        Current::$table = 'enums';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'db' => 'cvv',
                'table' => 'enums',
                'column' => 'set',
                'curr_value' => 'b&c',
            ]);

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

        $sqlController = new EnumValuesController($responseRenderer, $template, $sql);
        $sqlController($request);

        self::assertTrue($responseRenderer->hasSuccessState(), 'expected the request not to fail');

        self::assertSame(
            [
                'dropdown' => '<select>' . "\n"
                    . '      <option value="&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;">'
                    . '&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;</option>' . "\n"
                    . '      <option value="a&amp;b">a&amp;b</option>' . "\n"
                    . '      <option value="b&amp;c" selected>b&amp;c</option>' . "\n"
                    . '      <option value="vrai&amp;amp">vrai&amp;amp</option>' . "\n"
                    . '      <option value=""></option>' . "\n"
                    . '      <option value="漢字">漢字</option>' . "\n"
                    . '      <option value="&#039;">&#039;</option>' . "\n"
                    . '      <option value="\">\</option>' . "\n"
                    . '      <option value="&quot;\&#039;">&quot;\&#039;</option>' . "\n"
                    . '  </select>' . "\n",
            ],
            $responseRenderer->getJSONResult(),
        );
    }
}
