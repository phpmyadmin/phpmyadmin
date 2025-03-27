<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Core;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function htmlspecialchars;

/**
 * @covers \PhpMyAdmin\SqlQueryForm
 */
class SqlQueryFormTest extends AbstractTestCase
{
    /** @var SqlQueryForm */
    private $sqlQueryForm;

    /**
     * Test for setUp
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        $this->sqlQueryForm = new SqlQueryForm(new Template());

        //$GLOBALS
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['db'] = 'PMA_db';
        $GLOBALS['table'] = 'PMA_table';
        $GLOBALS['text_dir'] = 'text_dir';
        $GLOBALS['server'] = 0;

        $GLOBALS['cfg']['GZipDump'] = false;
        $GLOBALS['cfg']['BZipDump'] = false;
        $GLOBALS['cfg']['ZipDump'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 'default';
        $GLOBALS['cfg']['TextareaAutoSelect'] = true;
        $GLOBALS['cfg']['TextareaRows'] = 100;
        $GLOBALS['cfg']['TextareaCols'] = 11;
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['RetainQueryBox'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['DefaultTabTable'] = 'browse';
        $GLOBALS['cfg']['CodemirrorEnable'] = true;
        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';

        $_SESSION['relation'] = [];
        $_SESSION['relation'][0] = RelationParameters::fromArray([
            'table_coords' => 'table_name',
            'displaywork' => true,
            'db' => 'information_schema',
            'table_info' => 'table_info',
            'relwork' => true,
            'relation' => 'relation',
        ])->toArray();

        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'bookmarktable';

        parent::setGlobalDbi();
        $this->dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `PMA_db`.`PMA_table`',
            [
                [
                    'field1',
                    'Comment1',
                ],
            ],
            [
                'Field',
                'Comment',
            ]
        );

        $this->dummyDbi->addResult(
            'SHOW INDEXES FROM `PMA_db`.`PMA_table`',
            []
        );
    }

    /**
     * Test for getHtmlForInsert
     */
    public function testPMAGetHtmlForSqlQueryFormInsert(): void
    {
        //Call the test function
        $query = 'select * from PMA';
        $html = $this->sqlQueryForm->getHtml('PMA_db', 'PMA_table', $query);

        //validate 1: query
        self::assertStringContainsString(htmlspecialchars($query), $html);

        //validate 2: enable auto select text in textarea
        $auto_sel = ' data-textarea-auto-select="true"';
        self::assertStringContainsString($auto_sel, $html);

        //validate 3: MySQLDocumentation::show
        self::assertStringContainsString(MySQLDocumentation::show('SELECT'), $html);

        //validate 4: $fields_list
        self::assertStringContainsString('<input type="button" value="DELETE" id="delete"', $html);
        self::assertStringContainsString('<input type="button" value="UPDATE" id="update"', $html);
        self::assertStringContainsString('<input type="button" value="INSERT" id="insert"', $html);
        self::assertStringContainsString('<input type="button" value="SELECT" id="select"', $html);
        self::assertStringContainsString('<input type="button" value="SELECT *" id="selectall"', $html);

        //validate 5: Clear button
        self::assertStringContainsString('<input type="button" value="DELETE" id="delete"', $html);
        self::assertStringContainsString(__('Clear'), $html);
    }

    /**
     * Test for getHtml
     */
    public function testPMAGetHtmlForSqlQueryForm(): void
    {
        //Call the test function
        $GLOBALS['lang'] = 'ja';
        $query = 'select * from PMA';
        $html = $this->sqlQueryForm->getHtml('PMA_db', 'PMA_table', $query);

        //validate 1: query
        self::assertStringContainsString(htmlspecialchars($query), $html);

        //validate 2: $enctype
        $enctype = ' enctype="multipart/form-data">';
        self::assertStringContainsString($enctype, $html);

        //validate 3: sqlqueryform
        self::assertStringContainsString('id="sqlqueryform" name="sqlform"', $html);

        //validate 4: $db, $table
        $table = $GLOBALS['table'];
        $db = $GLOBALS['db'];
        self::assertStringContainsString(Url::getHiddenInputs($db, $table), $html);

        //validate 5: $goto
        $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/table/sql') : $GLOBALS['goto'];
        self::assertStringContainsString(htmlspecialchars($goto), $html);

        //validate 6: Kanji encoding form
        self::assertStringContainsString(Encoding::kanjiEncodingForm(), $html);
        $GLOBALS['lang'] = 'en';
    }
}
