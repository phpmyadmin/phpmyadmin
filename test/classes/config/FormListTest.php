<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormList classes in config folder
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Config\Forms\Page\PageFormList;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;

require_once 'test/PMATestCase.php';

/**
 * Tests for PMA_FormDisplay class
 *
 * @package PhpMyAdmin-test
 */
class FormListTest extends PMATestCase
{
    public function setUp()
    {
        $GLOBALS['server'] = 1;
    }

    /**
     * Tests for user preferences forms.
     */
    public function testUserForms()
    {
        $cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);

        /* Static API */
        $this->assertTrue(UserFormList::isValid('Export'));
        $this->assertEquals(
            'PhpMyAdmin\\Config\\Forms\\User\\ExportForm',
            UserFormList::get('Export')
        );
        $this->assertContains(
            'Export/texytext_columns',
            UserFormList::getFields()
        );

        /* Instance handling */
        $forms = new UserFormList($cf);
        $this->assertFalse($forms->process());
        $forms->fixErrors();
        $this->assertFalse($forms->hasErrors());
        $this->assertEquals('', $forms->displayErrors());
    }

    /**
     * Tests for page preferences forms.
     */
    public function testPageForms()
    {
        $cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);

        /* Static API */
        $this->assertTrue(PageFormList::isValid('Export'));
        $this->assertEquals(
            'PhpMyAdmin\\Config\\Forms\\Page\\ExportForm',
            PageFormList::get('Export')
        );
        $this->assertContains(
            'Export/texytext_columns',
            PageFormList::getFields()
        );

        /* Instance handling */
        $forms = new PageFormList($cf);
        $this->assertFalse($forms->process());
        $forms->fixErrors();
        $this->assertFalse($forms->hasErrors());
        $this->assertEquals('', $forms->displayErrors());
    }

    /**
     * Tests for setup preferences forms.
     */
    public function testSetupForms()
    {
        $cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);

        /* Static API */
        $this->assertTrue(SetupFormList::isValid('Export'));
        $this->assertEquals(
            'PhpMyAdmin\\Config\\Forms\\Setup\\ExportForm',
            SetupFormList::get('Export')
        );
        $this->assertContains(
            'Export/texytext_columns',
            SetupFormList::getFields()
        );

        /* Instance handling */
        $forms = new SetupFormList($cf);
        $this->assertFalse($forms->process());
        $forms->fixErrors();
        $this->assertFalse($forms->hasErrors());
        $this->assertEquals('', $forms->displayErrors());
    }
}
