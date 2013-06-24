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

$GLOBALS['server'] = 0;
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/build_html_for_db.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/Types.class.php';
require_once 'libraries/mysql_charsets.lib.php';

class PMA_build_html_for_db_test extends PHPUnit_Framework_TestCase
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
        $cfg['ServerDefault'] = 1;
        $GLOBALS['lang'] = 'en';
        $_SESSION[' PMA_token '] = 'token';
        $cfg['MySQLManualType'] = 'viewable';
        $cfg['MySQLManualBase'] = 'http://dev.mysql.com/doc/refman';

        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';

        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';

        $_SESSION[' PMA_token '] = 'token';

        $GLOBALS['cfg']['DefaultTabDatabase'] = 'db_structure.php';
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
                ),
                'SCHEMA_DATA_FREE' => array(
                    'disp_name' => __('Overhead'),
                    'format'    => 'byte',
                    'footer'    => 0
                )
            ),
            PMA_getColumnOrder()
        );
    }

    /**
     * Test for PMA_buildHtmlForDb
     *
     * @param array   $current
     * @param boolean $is_superuser
     * @param string  $checkall
     * @param string  $url_query
     * @param array   $column_order
     * @param array   $replication_types
     * @param array   $replication_info
     * @param string  $output
     *
     * @return void
     * @dataProvider providerForTestBuildHtmlForDb
     *
     * @group medium
     */
    public function testBuildHtmlForDb($current, $is_superuser,
        $url_query, $column_order, $replication_types,
        $replication_info, $tags
    ) {
        $result = PMA_buildHtmlForDb(
            $current, $is_superuser, $url_query,
            $column_order, $replication_types, $replication_info
        );
        $this->assertEquals(
            $column_order,
            $result[0]
        );
        foreach ($tags as $value) {
            $this->assertTag(
                $value,
                $result[1]
            );
        }
    }

    /**
     * Data for testBuildHtmlForDb
     *
     * @return array data for testBuildHtmlForDb test case
     */
    public function providerForTestBuildHtmlForDb()
    {
        return array(
            array(
                array('SCHEMA_NAME' => 'pma'),
                true,
                'target=main.php',
                PMA_getColumnOrder(),
                array(
                    'SCHEMA_NAME' => 'pma',
                ),
                array(
                    'pma' => array(
                        'status' => 'true',
                        'Ignore_DB' => array(
                                        'pma' => 'pma'
                                       ),
                    )
                ),
                array(
                    array(
                        'tag' => 'td',
                        'attributes' => array(
                            'class' => 'tool'
                        ),
                        'child' => array(
                            'tag' => 'input',
                            'attributes' => array(
                                'type' => 'checkbox',
                                'name' => 'selected_dbs[]',
                                'value' => 'pma'
                            )
                        )
                    ),
                    array(
                        'tag' => 'td',
                        'attributes' => array(
                            'class' => 'name'
                        ),
                        'child' => array(
                            'tag' => 'a',
                            'attributes' => array(
                                'title' => 'Jump to database'
                            ),
                            'content' => 'pma'
                        )
                    ),
                    array(
                        'tag' => 'td',
                        'attributes' => array(
                            'class' => 'tool'
                        ),
                        'descendant' => array(
                            'tag' => 'img',
                            'attributes' => array(
                                'title' => 'Not replicated'
                            )
                        )
                    ),
                    array(
                        'tag' => 'td',
                        'attributes' => array(
                            'class' => 'tool'
                        ),
                        'child' => array(
                            'tag' => 'a',
                            'child' => array(
                                'tag' => 'span',
                                'child' => array(
                                    'tag' => 'img',
                                    'attributes' => array(
                                        'title' => 'Check Privileges'
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            array(
                array('SCHEMA_NAME' => 'sakila'),
                true,
                'target=main.php',
                PMA_getColumnOrder(),
                array(
                    'SCHEMA_NAME' => 'sakila',
                ),
                array(
                    'sakila' => array(
                        'status' => 'true',
                        'Ignore_DB' => array(
                            'pma' => 'pma'
                        ),
                        'Do_DB' => array(
                            'sakila' => 'sakila'
                        )
                    )
                ),
                array(
                    array(
                        'tag' => 'td',
                        'attributes' => array(
                            'class' => 'tool'
                        ),
                        'child' => array(
                            'tag' => 'input',
                            'attributes' => array(
                                'type' => 'checkbox',
                                'name' => 'selected_dbs[]',
                                'value' => 'sakila'
                            )
                        )
                    ),
                    array(
                        'tag' => 'td',
                        'attributes' => array(
                            'class' => 'tool'
                        ),
                        'descendant' => array(
                            'tag' => 'img',
                            'attributes' => array(
                                'title' => 'Replicated'
                            )
                        )
                    ),
                )
            ),
            array(
                array('SCHEMA_NAME' => 'INFORMATION_SCHEMA'),
                true,
                'target=main.php',
                PMA_getColumnOrder(),
                array(
                    'SCHEMA_NAME' => 'INFORMATION_SCHEMA',
                ),
                array(
                    'INFORMATION_SCHEMA' => array(
                        'status' => 'false',
                        'Ignore_DB' => array(
                            'INFORMATION_SCHEMA' => 'INFORMATION_SCHEMA'
                        )
                    )
                ),
                array(
                    array(
                        'tag' => 'td',
                        'attributes' => array(
                            'class' => 'tool'
                        ),
                        'child' => array(
                            'tag' => 'input',
                            'attributes' => array(
                                'type' => 'checkbox',
                                'name' => 'selected_dbs[]',
                                'value' => 'INFORMATION_SCHEMA',
                                'disabled' => 'disabled'
                            )
                        )
                    )
                )
            )
        );
    }
}
