<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Display\Export
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Core;
use PhpMyAdmin\Display\Export;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * class PhpMyAdmin\Tests\Display\ExportTest
 *
 * this class is for testing PhpMyAdmin\Display\Export methods
 *
 * @package PhpMyAdmin-test
 * @group large
 */
class ExportTest extends TestCase
{
    private $export;

    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ExecTimeLimit'] = 300;
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['Server']['user'] = "pma_user";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['ZipDump'] = true;
        $GLOBALS['cfg']['GZipDump'] = false;
        $GLOBALS['cfg']['BZipDump'] = false;
        $GLOBALS['cfg']['Export']['asfile'] = true;
        $GLOBALS['cfg']['Export']['file_template_server'] = "file_template_server";
        $GLOBALS['cfg']['AvailableCharsets'] = [];
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['PMA_recoding_engine'] = "InnerDB";
        $GLOBALS['server'] = 0;

        $GLOBALS['table'] = "table";
        $GLOBALS['db'] = "PMA";

        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = "";

        $pmaconfig = $this->getMockBuilder('PhpMyAdmin\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $pmaconfig->expects($this->any())
            ->method('getUserValue')
            ->will($this->returnValue('user value for test'));

        $GLOBALS['PMA_Config'] = $pmaconfig;

        $this->export = new Export();
    }

    /**
     * Test for Export::getHtmlForHiddenInputs
     *
     * @return void
     */
    public function testGetHtmlForHiddenInputs()
    {
        $export_type = "server";
        $db = "PMA";
        $table = "PMA_test";
        $single_table_str = "PMA_single_str";
        $sql_query_str = "sql_query_str";

        //Call the test function
        $html = $this->export->getHtmlForHiddenInputs(
            $export_type,
            $db,
            $table,
            $single_table_str,
            $sql_query_str
        );

        //validate 1: Url::getHiddenInputs
        //$single_table
        $this->assertContains(
            '<input type="hidden" name="single_table" value="TRUE"',
            $html
        );
        //$export_type
        $this->assertContains(
            '<input type="hidden" name="export_type" value="server"',
            $html
        );
        $this->assertContains(
            '<input type="hidden" name="export_method" value="quick"',
            $html
        );
    }

    /**
     * Test for Export::getHtmlForOptions
     *
     * @return void
     */
    public function testGetHtmlForOptions()
    {
        global $cfg;
        $cfg['Export']['method'] = "XML";
        $cfg['SaveDir'] = "/tmp";

        $export_type = "server";
        $db = "PMA";
        $table = "PMA_test";
        $multi_values_str = "multi_values_str";
        $num_tables_str = "10";
        $unlim_num_rows_str = "unlim_num_rows_str";
        $single_table = "single_table";
        $GLOBALS['dbi']->cacheTableContent(array($db, $table, 'ENGINE'), 'MERGE');

        $columns_info = array(
            'test_column1' => array(
                'COLUMN_NAME' => 'test_column1'
            ),
            'test_column2' => array(
                'COLUMN_NAME' => 'test_column2'
            )
        );
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->will($this->returnValue($columns_info));
        $dbi->expects($this->any())->method('getCompatibilities')
            ->will($this->returnValue(array()));

        $GLOBALS['dbi'] = $dbi;

        /* Scan for plugins */
        $export_list = Plugins::getPlugins(
            "export",
            'libraries/classes/Plugins/Export/',
            array(
                'export_type' => $export_type,
                'single_table' => isset($single_table)
            )
        );

        //Call the test function
        $html = $this->export->getHtmlForOptions(
            $export_type,
            $db,
            $table,
            $multi_values_str,
            $num_tables_str,
            $export_list,
            $unlim_num_rows_str
        );

        //validate 2: Export::getHtmlForOptionsMethod
        $this->assertContains(
            $cfg['Export']['method'],
            $html
        );
        $this->assertContains(
            '<div class="exportoptions" id="quick_or_custom">',
            $html
        );
        $this->assertContains(
            __('Export method:'),
            $html
        );
        $this->assertContains(
            __('Custom - display all possible options'),
            $html
        );

        //validate 3: Export::getHtmlForOptionsSelection
        $this->assertContains(
            '<div class="exportoptions" id="databases_and_tables">',
            $html
        );
        $this->assertContains(
            '<h3>' . __('Databases:') . '</h3>',
            $html
        );
        $this->assertContains(
            $multi_values_str,
            $html
        );

        //validate 4: Export::getHtmlForOptionsQuickExport
        $this->assertContains(
            '<input type="checkbox" name="onserver" value="saveit"',
            $html
        );
        $dir = htmlspecialchars(Util::userDir($cfg['SaveDir']));
        $this->assertContains(
            'Save on server in the directory <strong>' . $dir . '</strong>',
            $html
        );

        //validate 5: Export::getHtmlForAliasModalDialog
        $this->assertContains(
            '<div id="alias_modal" class="hide" title="'
            . 'Rename exported databases/tables/columns">',
            $html
        );
        $this->assertContains(
            'Select database',
            $html
        );
        $this->assertContains(
            'Select table',
            $html
        );
        $this->assertContains(
            'New database name',
            $html
        );
        $this->assertContains(
            'New table name',
            $html
        );

        //validate 6: Export::getHtmlForOptionsOutput
        $this->assertContains(
            '<div class="exportoptions" id="output">',
            $html
        );
        $this->assertContains(
            'user value for test',
            $html
        );

        //validate 7: Export::getHtmlForOptionsFormat
        $this->assertContains(
            '<div class="exportoptions" id="format">',
            $html
        );
        $this->assertContains(
            '<h3>' . __('Format:') . '</h3>',
            $html
        );
    }

    /**
     * Test for Export::getHtmlForAliasModalDialog
     *
     * @return void
     */
    public function testGetHtmlForAliasModalDialog()
    {
        $columns_info = array(
            'test\'_db' => array(
                'test_<b>table' => array(
                    'co"l1' => array(
                        'COLUMN_NAME' => 'co"l1'
                    ),
                    'col<2' => array(
                        'COLUMN_NAME' => 'col<2'
                    )
                )
            )
        );

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->will($this->returnValue($columns_info));

        $GLOBALS['dbi'] = $dbi;

        $html = $this->export->getHtmlForAliasModalDialog();

        $this->assertContains(
            '<div id="alias_modal" class="hide" title="'
            . 'Rename exported databases/tables/columns">',
            $html
        );

        $this->assertContains('<button class="alias_remove', $html);
    }
}
