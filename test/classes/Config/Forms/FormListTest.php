<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormList classes in config folder
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\Page\PageFormList;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PMA_FormDisplay class
 *
 * @package PhpMyAdmin-test
 */
class FormListTest extends PmaTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
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
     * @return void
     *
     * @dataProvider formObjects
     */
    public function testForms($class, $prefix): void
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

    /**
     * @return array
     */
    public function formObjects()
    {
        return [
            [
                '\\PhpMyAdmin\\Config\\Forms\\User\\UserFormList',
                '\\PhpMyAdmin\\Config\\Forms\\User\\',
            ],
            [
                '\\PhpMyAdmin\\Config\\Forms\\Page\\PageFormList',
                '\\PhpMyAdmin\\Config\\Forms\\Page\\',
            ],
            [
                '\\PhpMyAdmin\\Config\\Forms\\Setup\\SetupFormList',
                '\\PhpMyAdmin\\Config\\Forms\\Setup\\',
            ],
        ];
    }
}
