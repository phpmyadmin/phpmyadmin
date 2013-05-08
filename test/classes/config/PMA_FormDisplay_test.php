<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Form class in config folder
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/config/Form.class.php';
require_once 'libraries/config/FormDisplay.class.php';
require_once 'libraries/Util.class.php';


class PMA_FormDisplay_Test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $_POST['submit_save'] = true;
    }

    public function testContructor()
    {
        $form_display = new FormDisplay();
        $this->assertFalse($form_display->hasErrors());
    }
}
?>
