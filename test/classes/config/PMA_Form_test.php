<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Form class in config folder
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/config/Form.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';


class PMA_Form_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
    }

    public function testContructor()
    {
        $form = new Form('pma_form_name', array('pma_form1','pma_form2'), 1);
        $this->assertEquals(
            1,
            $form->index
        );
        $this->assertEquals(
            'pma_form_name',
            $form->name
        );
        $this->assertArrayHasKey(
            'pma_form1',
            $form->fields
        );
    }
}
?>
