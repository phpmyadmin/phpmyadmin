<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ThemeGenerator class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ThemeGenerator;
use PhpMyAdmin\Tests\Theme\LayoutTest;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PMA_ThemeGenerator class
 *
 * @package PhpMyAdmin-test
 */
class ThemeGeneratorTest extends PmaTestCase
{
    /**
     * Test for ThemeGenerator::colorPicker
     *
     * @return void
     */
    public function testcolorPicker()
    {
        $theme = new ThemeGenerator();
        $output = $theme->colorPicker();
        $this->assertContains('<div id="container">' , $output);
        $this->assertContains('<div id="palette" class="block">' , $output);
        $this->assertContains('<div id="color-palette"></div>' , $output);
        $this->assertContains('<div id="picker" class="block">' , $output);
        $this->assertContains('<div class="ui-color-picker" data-topic="picker" data-mode="HSB"></div>' , $output);
        $this->assertContains('<div id="picker-samples" sample-id="master">' , $output);
    }

    /**
     * Test for ThemeGenerator::form
     *
     * @return void
     */
    public function testform()
    {
        $theme = new ThemeGenerator();
        $output = $theme->form();
        $this->assertContains('<form action="index.php" method="post" id="save">' , $output);
        $this->assertContains('<select name="type" id="theme">' , $output);
        $this->assertContains('<input type="text" name="theme_name"></input>' , $output);
        $this->assertContains('<input type="submit">' , $output);
    }

    /**
     * Test for ThemeGenerator::form
     *
     * @return void
     */
    public function testcreateFileStructure()
    {
        $_POST['theme_name'] = 'phpunit';
        $_POST['Navigation_Panel'] = '#ffffff';
        $_POST['Navigation_Hover'] = '#ffffff';
        $_POST['Text_Color'] = '#ffffff';
        $_POST['Background_Color'] = '#ffffff';
        $_POST['Header'] = '#ffffff';
        $_POST['Table_Header_and_Footer'] = '#ffffff';
        $_POST['Table_Header_and_Footer_Background'] = '#ffffff';
        $_POST['Table_Row_Background'] = '#ffffff';
        $_POST['Table_Row_Alternate_Background'] = '#ffffff';
        $_POST['Hyperlink_Text'] = '#ffffff';
        $_POST['Group_Background'] = '#ffffff';
        $theme = new ThemeGenerator();
        $output = $theme->createFileStructure($_POST);
        $layout = new LayoutTest();
        $layout->createLayoutFile($_POST , $output['layout']);
        $this->createJsonFile($_POST['theme_name'] , $output['json']);
        $this->deleteFiles($_POST['theme_name']);
    }

    /**
     * Test for ThemeGenerator::createJsonFile
     *
     * @param string $name name of new theme
     *
     * @param string $output JSON file data
     *
     * @return void
     */
    public function createJsonFile($name , $output)
    {
        $this->assertFileIsReadable('themes/' . $name . '/theme.json');
        $this->assertContains('"name": "' . $name . '",' , $output);
    }

    /**
     * Deletes all the created files
     *
     * @param string $name name of new theme
     *
     * @return void
     */
    public function deleteFiles($name)
    {
        unlink('themes/' . $name . '/theme.json');
        unlink('themes/' . $name . '/layout.inc.php');
        unlink('themes/' . $name . '/css/common.css.php');
        unlink('themes/' . $name . '/css/navigation.css.php');
        rmdir('themes/' . $name . '/css');
        rmdir('themes/' . $name);
    }
}
