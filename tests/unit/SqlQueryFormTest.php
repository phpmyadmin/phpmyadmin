<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function __;
use function htmlspecialchars;

#[CoversClass(SqlQueryForm::class)]
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

        $this->setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `PMA_db`.`PMA_table`',
            [['field1', '', null, 'NO', '', null, '', '', 'Comment1']],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );

        $this->dummyDbi->addResult(
            'SHOW INDEXES FROM `PMA_db`.`PMA_table`',
            [],
        );
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $relation = new Relation($this->dbi);
        $bookmarkRepository = new BookmarkRepository($this->dbi, $relation);
        $this->sqlQueryForm = new SqlQueryForm(new Template(), $this->dbi, $bookmarkRepository);

        Current::$database = 'PMA_db';
        Current::$table = 'PMA_table';

        $config = Config::getInstance();
        $config->settings['TextareaAutoSelect'] = true;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::TABLE_COORDS => 'table_name',
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::DATABASE => 'information_schema',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::REL_WORK => true,
            RelationParameters::RELATION => 'relation',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $config->selectedServer['user'] = 'user';
        $config->selectedServer['pmadb'] = 'pmadb';
        $config->selectedServer['bookmarktable'] = 'bookmarktable';
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
        self::assertStringContainsString(
            htmlspecialchars($query),
            $html,
        );

        //validate 2: enable auto select text in textarea
        $autoSel = ' data-textarea-auto-select="true"';
        self::assertStringContainsString($autoSel, $html);

        //validate 3: MySQLDocumentation::show
        self::assertStringContainsString(
            MySQLDocumentation::show('SELECT'),
            $html,
        );

        //validate 4: $fields_list
        self::assertStringContainsString('<input type="button" value="DELETE" id="delete"', $html);
        self::assertStringContainsString('<input type="button" value="UPDATE" id="update"', $html);
        self::assertStringContainsString('<input type="button" value="INSERT" id="insert"', $html);
        self::assertStringContainsString('<input type="button" value="SELECT" id="select"', $html);
        self::assertStringContainsString('<input type="button" value="SELECT *" id="selectall"', $html);

        //validate 5: Clear button
        self::assertStringContainsString('<input type="button" value="DELETE" id="delete"', $html);
        self::assertStringContainsString(
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
        Current::$lang = 'ja';
        $query = 'select * from PMA';
        $html = $this->sqlQueryForm->getHtml('PMA_db', 'PMA_table', $query);

        //validate 1: query
        self::assertStringContainsString(
            htmlspecialchars($query),
            $html,
        );

        //validate 2: $enctype
        $enctype = ' enctype="multipart/form-data">';
        self::assertStringContainsString($enctype, $html);

        //validate 3: sqlqueryform
        self::assertStringContainsString('id="sqlqueryform" name="sqlform"', $html);

        //validate 4: $db, $table
        $table = Current::$table;
        $db = Current::$database;
        self::assertStringContainsString(
            Url::getHiddenInputs($db, $table),
            $html,
        );

        //validate 5: $goto
        $goto = UrlParams::$goto === '' ? Url::getFromRoute('/table/sql') : UrlParams::$goto;
        self::assertStringContainsString(
            htmlspecialchars($goto),
            $html,
        );

        //validate 6: Kanji encoding form
        self::assertStringContainsString(
            Encoding::kanjiEncodingForm(),
            $html,
        );
        Current::$lang = 'en';
    }
}
