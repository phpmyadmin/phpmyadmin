<?php
/**
 * tests for PhpMyAdmin\Display\Export
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Export;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Util;
use function htmlspecialchars;

/**
 * this class is for testing PhpMyAdmin\Display\Export methods
 *
 * @group large
 */
class ExportTest extends AbstractTestCase
{
    /** @var Export */
    private $export;

    /**
     * Test for setUp
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['server'] = 0;

        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'PMA';

        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = '';

        $pmaconfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pmaconfig->expects($this->any())
            ->method('getUserValue')
            ->will($this->returnValue('user value for test'));

        $GLOBALS['PMA_Config'] = $pmaconfig;

        $this->export = new Export();
    }

    /**
     * Test for Export::getHtmlForOptions
     */
    public function testGetHtmlForOptions(): void
    {
        global $cfg;
        $cfg['Export']['method'] = 'XML';
        $cfg['SaveDir'] = '/tmp';

        $export_type = 'server';
        $db = 'PMA';
        $table = 'PMA_test';
        $multi_values_str = 'multi_values_str';
        $num_tables_str = '10';
        $unlim_num_rows_str = 'unlim_num_rows_str';
        //$single_table = "single_table";
        $GLOBALS['dbi']->getCache()->cacheTableContent([$db, $table, 'ENGINE'], 'MERGE');

        $columns_info = [
            'test_column1' => ['COLUMN_NAME' => 'test_column1'],
            'test_column2' => ['COLUMN_NAME' => 'test_column2'],
        ];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('getColumnsFull')
            ->will($this->returnValue($columns_info));
        $dbi->expects($this->any())->method('getCompatibilities')
            ->will($this->returnValue([]));

        $GLOBALS['dbi'] = $dbi;

        /* Scan for plugins */
        $export_list = Plugins::getPlugins(
            'export',
            'libraries/classes/Plugins/Export/',
            [
                'export_type' => $export_type,
                'single_table' => true,// isset($single_table)
            ]
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
        $this->assertStringContainsString(
            $cfg['Export']['method'],
            $html
        );
        $this->assertStringContainsString(
            '<div class="exportoptions" id="quick_or_custom">',
            $html
        );
        $this->assertStringContainsString(
            __('Export method:'),
            $html
        );
        $this->assertStringContainsString(
            __('Custom - display all possible options'),
            $html
        );

        //validate 3: Export::getHtmlForOptionsSelection
        $this->assertStringContainsString(
            '<div class="exportoptions" id="databases_and_tables">',
            $html
        );
        $this->assertStringContainsString(
            '<h3>' . __('Databases:') . '</h3>',
            $html
        );
        $this->assertStringContainsString(
            $multi_values_str,
            $html
        );

        //validate 4: Export::getHtmlForOptionsQuickExport
        $this->assertStringContainsString(
            '<input type="checkbox" name="onserver" value="saveit"',
            $html
        );
        $dir = htmlspecialchars(Util::userDir($cfg['SaveDir']));
        $this->assertStringContainsString(
            'Save on server in the directory <strong>' . $dir . '</strong>',
            $html
        );

        //validate 5: Export::getHtmlForAliasModalDialog
        $this->assertStringContainsString(
            '<div id="alias_modal" class="hide" title="'
            . 'Rename exported databases/tables/columns">',
            $html
        );
        $this->assertStringContainsString(
            'Select database',
            $html
        );
        $this->assertStringContainsString(
            'Select table',
            $html
        );
        $this->assertStringContainsString(
            'New database name',
            $html
        );
        $this->assertStringContainsString(
            'New table name',
            $html
        );

        $this->assertStringContainsString(
            '<div id="alias_modal" class="hide" title="'
            . 'Rename exported databases/tables/columns">',
            $html
        );

        $this->assertStringContainsString('<button class="alias_remove', $html);

        //validate 6: Export::getHtmlForOptionsOutput
        $this->assertStringContainsString(
            '<div class="exportoptions" id="output">',
            $html
        );
        $this->assertStringContainsString(
            'user value for test',
            $html
        );

        //validate 7: Export::getHtmlForOptionsFormat
        $this->assertStringContainsString(
            '<div class="exportoptions" id="format">',
            $html
        );
        $this->assertStringContainsString(
            '<h3>' . __('Format:') . '</h3>',
            $html
        );
    }
}
