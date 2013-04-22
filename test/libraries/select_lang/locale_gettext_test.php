<?php
/**
 * Tests for working locales
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/select_lang.lib.php';

/**
 * Class for testing correctness of locales.
 *
 * @package PhpMyAdmin-test
 */
class PMA_Languages_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setting and parsing locales
     *
     * @param string $locale locale name
     *
     * @return void
     *
     * @group large
     * @dataProvider listLocales
     */
    public function testGettext($locale)
    {
        /* We should be able to set the language */
        $this->assertTrue(PMA_langSet($locale));

        /* Bind locales */
        _setlocale(LC_MESSAGES, $GLOBALS['lang']);
        _bind_textdomain_codeset('phpmyadmin', 'UTF-8');
        _textdomain('phpmyadmin');

        /* Grab some texts */
        $this->assertContains('%s', _ngettext('%s table', '%s tables', 10));
        $this->assertContains('%s', _ngettext('%s table', '%s tables', 1));
    }

    /**
     * Data provider to generate list of available locales.
     *
     * @return array with arrays of available locales
     */
    public function listLocales()
    {
        $ret = array();
        foreach ($GLOBALS['available_languages'] as $key => $val) {
            $ret[] = array($key);
        }
        return $ret;
    }
}
?>
