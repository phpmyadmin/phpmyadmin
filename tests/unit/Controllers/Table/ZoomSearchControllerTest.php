<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ZoomSearchController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ZoomSearchController::class)]
final class ZoomSearchControllerTest extends AbstractTestCase
{
    public function testZoomSearchController(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        UrlParams::$goto = '';

        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $response = new ResponseRenderer();
        $template = new Template();
        $controller = new ZoomSearchController(
            $response,
            $template,
            new Search($dbi),
            new Relation($dbi),
            $dbi,
            new DbTableExists($dbi),
            $config,
        );
        $controller($request);

        $expected = $template->render('table/zoom_search/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'goto' => 'index.php?route=/sql&server=2&lang=en',
            'self' => $controller,
            'geom_column_flag' => false,
            'column_names' => ['id', 'name', 'datetimefield'],
            'data_label' => 'name',
            'keys' => [],
            'criteria_column_names' => null,
            'criteria_column_types' => null,
            'max_plot_limit' => 500,
        ]);

        self::assertSame($expected, $response->getHTMLResult());
    }

    public function testChangeTableInfoAction(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        $_POST['field'] = 'datetimefield';

        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table'])
            ->withParsedBody(['change_tbl_info' => '1']);

        $response = new ResponseRenderer();
        $template = new Template();
        $controller = new ZoomSearchController(
            $response,
            $template,
            new Search($dbi),
            new Relation($dbi),
            $dbi,
            new DbTableExists($dbi),
            $config,
        );
        $controller($request);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $operators = <<<'HTML'
<select class="column-operator" id="ColumnOperator0" name="criteriaColumnOperators[0]">
  <option value="=">=</option><option value="&gt;">&gt;</option><option value="&gt;=">&gt;=</option><option value="&lt;">&lt;</option><option value="&lt;=">&lt;=</option><option value="!=">!=</option><option value="LIKE">LIKE</option><option value="LIKE %...%">LIKE %...%</option><option value="NOT LIKE">NOT LIKE</option><option value="NOT LIKE %...%">NOT LIKE %...%</option><option value="IN (...)">IN (...)</option><option value="NOT IN (...)">NOT IN (...)</option><option value="BETWEEN">BETWEEN</option><option value="NOT BETWEEN">NOT BETWEEN</option>
</select>

HTML;
        // phpcs:enable

        $value = <<<'HTML'
                        <input
                    type="text"
        name="criteriaValues[0]"
        data-type="DATETIME"
         onfocus="return verifyAfterSearchFieldChange(0, '#zoom_search_form')"
        size="40"
        class="textfield datetimefield"
        id="fieldID_0"
        >

HTML;

        $expected = [
            'field_type' => 'datetime',
            'field_collation' => '',
            'field_operators' => $operators,
            'field_value' => $value,
        ];

        self::assertSame($expected, $response->getJSONResult());
    }
}
