<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ChangeController::class)]
final class ChangeControllerTest extends AbstractTestCase
{
    public function testChangeController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$whereClause = null;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dummyDbi->addResult(
            'SELECT * FROM `test_db`.`test_table` LIMIT 1;',
            [['1', 'abcd', '2011-01-20 02:00:02']],
            ['id', 'name', 'datetimefield'],
        );
        $dummyDbi->addSelectDb('test_db');
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseRenderer();
        $config = Config::getInstance();
        $template = new Template($config);
        $relation = new Relation($dbi, $config);
        $userPreferences = new UserPreferences($dbi, $relation, $template, $config);
        $pageSettings = new PageSettings($userPreferences);
        $pageSettings->init('Edit');

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table'])
            ->withParsedBody(['insert_rows' => '0']);

        $config->set('InsertRows', 3);

        $insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            $template,
            $config,
        );

        (new ChangeController(
            $response,
            $template,
            $insertEdit,
            $relation,
            $pageSettings,
            new DbTableExists($dbi),
            $config,
        ))($request);
        $actual = $response->getHTMLResult();

        self::assertStringContainsString($pageSettings->getHTML(), $actual);
        // phpcs:disable Generic.Files.LineLength.TooLong
        self::assertStringContainsString(
            <<<'HTML'
              <input type="text" name="fields[multi_edit][0][b80bb7740288fda1f201890375a60c8f]"
                value="NULL" size="4" min="-2147483648" max="2147483647"    data-type="INT"
                class="textfield"
                onchange="return&#x20;verificationsAfterFieldChange&#x28;&quot;b80bb7740288fda1f201890375a60c8f&quot;,&#x20;&quot;0&quot;,&quot;int&#x28;11&#x29;&quot;&#x29;"
                tabindex="1" inputmode="numeric"
                id="field_1_3"><input type="hidden" name="auto_increment[multi_edit][0][b80bb7740288fda1f201890375a60c8f]" value="1">
            HTML,
            $actual,
        );
        self::assertStringContainsString(
            <<<'HTML'
              <input type="text" name="fields[multi_edit][0][b068931cc450442b63f5b3d276ea4297]"
                value="NULL" size="20" data-maxlength="20"    data-type="CHAR"
                class="textfield"
                onchange="return&#x20;verificationsAfterFieldChange&#x28;&quot;b068931cc450442b63f5b3d276ea4297&quot;,&#x20;&quot;0&quot;,&quot;varchar&#x28;20&#x29;&quot;&#x29;"
                tabindex="2"
                id="field_2_3">
            HTML,
            $actual,
        );
        self::assertStringContainsString(
            <<<'HTML'
              <input type="text" name="fields[multi_edit][0][a55dbdcc1a45ed90dbee68864d566b99]"
                value="NULL.000000" size="4"    data-type="DATE"
                class="textfield datetimefield"
                onchange="return&#x20;verificationsAfterFieldChange&#x28;&quot;a55dbdcc1a45ed90dbee68864d566b99&quot;,&#x20;&quot;0&quot;,&quot;datetime&quot;&#x29;"
                tabindex="3"
                id="field_3_3"><input type="hidden" name="fields_type[multi_edit][0][a55dbdcc1a45ed90dbee68864d566b99]" value="datetime">
            HTML,
            $actual,
        );
        // phpcs:enable
        self::assertStringContainsString(
            '<th><a href="index.php?route=/table/change&lang=en" data-post="db=test_db&table=test_table'
            . '&ShowFieldTypesInDataEditView=0&ShowFunctionFields=1'
            . '&goto=index.php%3Froute%3D%2Fsql%26lang%3Den&lang=en" title="Hide">Type</a></th>',
            $actual,
        );
        self::assertStringContainsString(
            '<th><a href="index.php?route=/table/change&lang=en" data-post="db=test_db&table=test_table'
            . '&ShowFunctionFields=0&ShowFieldTypesInDataEditView=1'
            . '&goto=index.php%3Froute%3D%2Fsql%26lang%3Den&lang=en" title="Hide">Function</a></th>',
            $actual,
        );
        self::assertStringContainsString(
            '<input class="form-control" type="number" name="insert_rows" id="insert_rows" value="3" min="1">',
            $actual,
        );

        $dummyDbi->assertAllSelectsConsumed();
        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testChangeController2(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$whereClause = null;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dummyDbi->addResult(
            'SELECT * FROM `test_db`.`test_table` LIMIT 1;',
            [['1', 'abcd', '2011-01-20 02:00:02']],
            ['id', 'name', 'datetimefield'],
        );
        $dummyDbi->addSelectDb('test_db');
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseRenderer();
        $config = Config::getInstance();
        $relation = new Relation($dbi, $config);
        $template = new Template($config);
        $userPreferences = new UserPreferences($dbi, $relation, $template, $config);
        $pageSettings = new PageSettings($userPreferences);
        $pageSettings->init('Edit');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table'])
            ->withParsedBody(['insert_rows' => '1']);

        $config->set('InsertRows', 3);
        $config->set('ShowFunctionFields', false);
        $config->set('ShowFieldTypesInDataEditView', false);

        $insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            $template,
            $config,
        );

        (new ChangeController(
            $response,
            $template,
            $insertEdit,
            $relation,
            $pageSettings,
            new DbTableExists($dbi),
            $config,
        ))($request);
        $actual = $response->getHTMLResult();

        self::assertStringContainsString(
            '<input class="form-control" type="number" name="insert_rows" id="insert_rows" value="1" min="1">',
            $actual,
        );
        self::assertStringContainsString(
            'Show : <a href="index.php?route=/table/change&lang=en" data-post="'
            . 'db=test_db&table=test_table&ShowFunctionFields=1&ShowFieldTypesInDataEditView=0'
            . '&goto=index.php%3Froute%3D%2Fsql%26lang%3Den&lang=en">Function</a> : <a href="'
            . 'index.php?route=/table/change&lang=en" data-post="db=test_db&table=test_table'
            . '&ShowFieldTypesInDataEditView=1&ShowFunctionFields=0'
            . '&goto=index.php%3Froute%3D%2Fsql%26lang%3Den&lang=en">Type</a>',
            $actual,
        );

        $dummyDbi->assertAllSelectsConsumed();
        $dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Test for urlParamsInEditMode
     */
    public function testUrlParamsInEditMode(): void
    {
        $changeController = new ChangeController(
            self::createStub(ResponseRenderer::class),
            self::createStub(Template::class),
            self::createStub(InsertEdit::class),
            self::createStub(Relation::class),
            self::createStub(PageSettings::class),
            new DbTableExists($this->createDatabaseInterface()),
            self::createStub(Config::class),
        );

        $whereClauseArray = ['foo=1', 'bar=2'];
        $_POST['sql_query'] = 'SELECT 1';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['sql_query' => 'SELECT 1']);

        $result = $changeController->urlParamsInEditMode($request, ['temp' => 1], $whereClauseArray);

        self::assertSame(
            ['temp' => 1, 'where_clause' => 'bar=2', 'sql_query' => 'SELECT 1'],
            $result,
        );
    }
}
