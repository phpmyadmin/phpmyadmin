<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormList classes in config folder
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Config\Forms;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Config\Forms\Page\PageFormList;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PMA_FormDisplay class
 *
 * @package PhpMyAdmin-test
 */
class FormListTest extends PmaTestCase
{
    public function setUp()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['server'] = 1;
    }

    /**
     * Tests for preferences forms.
     *
     * @param string $class  Class to test
     * @param string $prefix Reuturned class prefix
     *
     * @dataProvider formObjects
     */
    public function testForms($class, $prefix)
    {
        $cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);

        /* Static API */
        $this->assertTrue($class::isValid('Export'));
        $this->assertEquals(
            $prefix . 'ExportForm',
            $class::get('Export')
        );
        foreach ($class::getAll() as $form) {
            $form_class = $class::get($form);
            $this->assertNotNull($form_class::getName());
        }

        $this->assertContains(
            'Export/texytext_columns',
            $class::getFields()
        );

        /* Instance handling */
        $forms = new $class($cf);
        $this->assertFalse($forms->process());
        $forms->fixErrors();
        $this->assertFalse($forms->hasErrors());
        $this->assertEquals('', $forms->displayErrors());
    }

    public function formObjects()
    {
        return array(
            array(
                '\\PhpMyAdmin\\Config\\Forms\\User\\UserFormList',
                '\\PhpMyAdmin\\Config\\Forms\\User\\',
            ),
            array(
                '\\PhpMyAdmin\\Config\\Forms\\Page\\PageFormList',
                '\\PhpMyAdmin\\Config\\Forms\\Page\\',
            ),
            array(
                '\\PhpMyAdmin\\Config\\Forms\\Setup\\SetupFormList',
                '\\PhpMyAdmin\\Config\\Forms\\Setup\\',
            ),
        );
    }
}
