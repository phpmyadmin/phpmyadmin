<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for gettext locales.
 *
 * @package PhpMyAdmin-test
 */
require_once 'libraries/php-gettext/gettext.inc';

class LocaleTest extends PHPUnit_Framework_TestCase
{

    public function test_setlocale_system()
    {
        putenv("LC_ALL=");
        // For an existing locale, it never needs emulation.
        putenv("LANG=C");
        _setlocale(LC_MESSAGES, "");
        $this->assertEquals(0, locale_emulation());
    }

    public function test_setlocale_emulation()
    {
        putenv("LC_ALL=");
        // If we set it to a non-existent locale, it still works, but uses
        // emulation.
        _setlocale(LC_MESSAGES, "xxx_XXX");
        $this->assertEquals('xxx_XXX', _setlocale(LC_MESSAGES, 0));
        $this->assertEquals(1, locale_emulation());
    }

    public function test_get_list_of_locales()
    {
        // For a locale containing country code, we prefer
        // full locale name, but if that's not found, fall back
        // to the language only locale name.
        $this->assertEquals(
            array("sr_RS", "sr"),
            get_list_of_locales("sr_RS")
        );

        // If language code is used, it's the only thing returned.
        $this->assertEquals(
            array("sr"),
            get_list_of_locales("sr")
        );

        // There is support for language and charset only.
        $this->assertEquals(
            array("sr.UTF-8", "sr"),
            get_list_of_locales("sr.UTF-8")
        );

        // It can also split out character set from the full locale name.
        $this->assertEquals(
            array("sr_RS.UTF-8", "sr_RS", "sr"),
            get_list_of_locales("sr_RS.UTF-8")
        );

        // There is support for @modifier in locale names as well.
        $this->assertEquals(
            array("sr_RS.UTF-8@latin", "sr_RS@latin", "sr@latin",
                "sr_RS.UTF-8", "sr_RS", "sr"),
            get_list_of_locales("sr_RS.UTF-8@latin")
        );

        // We can pass in only language and modifier.
        $this->assertEquals(
            array("sr@latin", "sr"),
            get_list_of_locales("sr@latin")
        );

        // If locale name is not following the regular POSIX pattern,
        // it's used verbatim.
        $this->assertEquals(
            array("something"),
            get_list_of_locales("something")
        );

        // Passing in an empty string returns an empty array.
        $this->assertEquals(
            array(),
            get_list_of_locales("")
        );
    }
}

