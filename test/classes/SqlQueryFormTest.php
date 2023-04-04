<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Url;
use ReflectionClass;

use function __;
use function htmlspecialchars;

/** @covers \PhpMyAdmin\SqlQueryForm */
class SqlQueryFormTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private SqlQueryForm $sqlQueryForm;

    /**
     * Test for setUp
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dummyDbi = $this->createDbiDummy();
        $this->dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `PMA_db`.`PMA_table`',
            [['field1', 'Comment1']],
            ['Field', 'Comment'],
        );

        $this->dummyDbi->addResult(
            'SHOW INDEXES FROM `PMA_db`.`PMA_table`',
            [],
        );
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
        $this->sqlQueryForm = new SqlQueryForm(new Template(), $this->dbi);

        //$GLOBALS
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

        $relationParameters = RelationParameters::fromArray([
            'table_coords' => 'table_name',
            'displaywork' => true,
            'db' => 'information_schema',
            'table_info' => 'table_info',
            'relwork' => true,
            'relation' => 'relation',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([$relationParameters]);

        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'bookmarktable';
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
        $this->assertStringContainsString(
            htmlspecialchars($query),
            $html,
        );

        //validate 2: enable auto select text in textarea
        $autoSel = ' data-textarea-auto-select="true"';
        $this->assertStringContainsString($autoSel, $html);

        //validate 3: MySQLDocumentation::show
        $this->assertStringContainsString(
            MySQLDocumentation::show('SELECT'),
            $html,
        );

        //validate 4: $fields_list
        $this->assertStringContainsString('<input type="button" value="DELETE" id="delete"', $html);
        $this->assertStringContainsString('<input type="button" value="UPDATE" id="update"', $html);
        $this->assertStringContainsString('<input type="button" value="INSERT" id="insert"', $html);
        $this->assertStringContainsString('<input type="button" value="SELECT" id="select"', $html);
        $this->assertStringContainsString('<input type="button" value="SELECT *" id="selectall"', $html);

        //validate 5: Clear button
        $this->assertStringContainsString('<input type="button" value="DELETE" id="delete"', $html);
        $this->assertStringContainsString(
            __('Clear'),
            $html,
        );
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
        $this->assertStringContainsString(
            htmlspecialchars($query),
            $html,
        );

        //validate 2: $enctype
        $enctype = ' enctype="multipart/form-data">';
        $this->assertStringContainsString($enctype, $html);

        //validate 3: sqlqueryform
        $this->assertStringContainsString('id="sqlqueryform" name="sqlform"', $html);

        //validate 4: $db, $table
        $table = $GLOBALS['table'];
        $db = $GLOBALS['db'];
        $this->assertStringContainsString(
            Url::getHiddenInputs($db, $table),
            $html,
        );

        //validate 5: $goto
        $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/table/sql') : $GLOBALS['goto'];
        $this->assertStringContainsString(
            htmlspecialchars($goto),
            $html,
        );

        //validate 6: Kanji encoding form
        $this->assertStringContainsString(
            Encoding::kanjiEncodingForm(),
            $html,
        );
        $GLOBALS['lang'] = 'en';
    }
}
