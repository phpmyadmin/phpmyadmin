<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for build_html_for_db.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\Theme;
use PMA\libraries\TypesMySQL;

$GLOBALS['server'] = 0;

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/build_html_for_db.lib.php';
require_once 'libraries/js_escape.lib.php';

require_once 'libraries/database_interface.inc.php';


require_once 'libraries/mysql_charsets.inc.php';

/**
 * tests for build_html_for_db.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_BuildHtmlForDb_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        global $cfg;

        $cfg['ShowFunctionFields'] = false;
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $cfg['ServerDefault'] = 1;

        $GLOBALS['PMA_Types'] = new TypesMySQL();
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';

        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';

        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
    }

    /**
     * Test for PMA_getColumnOrder
     *
     * @return void
     */
    public function testGetColumnOrder()
    {
        $this->assertEquals(
            array(
                'DEFAULT_COLLATION_NAME' => array(
                    'disp_name' => __('Collation'),
                    'description_function' => 'PMA_getCollationDescr',
                    'format'    => 'string',
                    'footer'    => 'utf8_general_ci'
                ),
                'SCHEMA_TABLES' => array(
                    'disp_name' => __('Tables'),
                    'format'    => 'number',
                    'footer'    => 0
                ),
                'SCHEMA_TABLE_ROWS' => array(
                    'disp_name' => __('Rows'),
                    'format'    => 'number',
                    'footer'    => 0
                ),
                'SCHEMA_DATA_LENGTH' => array(
                    'disp_name' => __('Data'),
                    'format'    => 'byte',
                    'footer'    => 0
                ),
                'SCHEMA_INDEX_LENGTH' => array(
                    'disp_name' => __('Indexes'),
                    'format'    => 'byte',
                    'footer'    => 0
                ),
                'SCHEMA_LENGTH' => array(
                    'disp_name' => __('Total'),
                    'format'    => 'byte',
                    'footer'    => 0
                )
            ),
            PMA_getColumnOrder()
        );
    }
}
