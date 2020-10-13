<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\LanguageManager;
use function count;
use function is_readable;
use function strtolower;

class LanguageTest extends AbstractTestCase
{
    /** @var LanguageManager */
    private $manager;

    /**
     * Setup for Language tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $loc = LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo';
        if (! is_readable($loc)) {
            $this->markTestSkipped('Missing compiled locales.');
        }
        $this->manager = new LanguageManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Ensure we have English locale after tests
        $this->manager->getLanguage('en')->activate();
    }

    /**
     * Test language filtering
     */
    public function testAvailable(): void
    {
        $GLOBALS['PMA_Config']->set('FilterLanguages', 'cs|en$');

        $langs = $this->manager->availableLocales();

        $this->assertCount(2, $langs);
        $this->assertContains('cs', $langs);
        $GLOBALS['PMA_Config']->set('FilterLanguages', '');
    }

    /**
     * Test no language filtering
     */
    public function testAllAvailable(): void
    {
        $GLOBALS['PMA_Config']->set('FilterLanguages', '');

        $langs = $this->manager->availableLocales();

        $this->assertContains('cs', $langs);
        $this->assertContains('en', $langs);
    }

    /**
     * Test whether listing locales works
     */
    public function testList(): void
    {
        $langs = $this->manager->listLocaleDir();
        $this->assertContains('cs', $langs);
        $this->assertContains('en', $langs);
    }

    /**
     * Test for getting available languages
     */
    public function testLanguages(): void
    {
        $langs = $this->manager->availableLanguages();
        $this->assertGreaterThan(1, count($langs));

        /* Ensure we have name for every language */
        foreach ($langs as $lang) {
            $this->assertNotEquals(
                $lang->getCode(),
                strtolower($lang->getEnglishName()),
                'Maybe this language does not exist in LanguageManager class'
                . ', see: https://github.com/phpmyadmin/phpmyadmin/issues/16300.'
            );
        }
    }

    /**
     * Test for MySQL locales
     */
    public function testMySQLLocale(): void
    {
        $GLOBALS['PMA_Config']->set('FilterLanguages', '');
        $czech = $this->manager->getLanguage('cs');
        $this->assertNotFalse($czech);
        $this->assertEquals('cs_CZ', $czech->getMySQLLocale());

        $azerbaijani = $this->manager->getLanguage('az');
        $this->assertNotFalse($azerbaijani);
        $this->assertEquals('', $azerbaijani->getMySQLLocale());
    }

    /**
     * Test for getting available sorted languages
     */
    public function testSortedLanguages(): void
    {
        $langs = $this->manager->sortedLanguages();
        $this->assertGreaterThan(1, count($langs));
    }

    /**
     * Test getting language by code
     */
    public function testGet(): void
    {
        $GLOBALS['PMA_Config']->set('FilterLanguages', '');
        $lang = $this->manager->getLanguage('cs');
        $this->assertNotEquals(false, $lang);
        $this->assertEquals('Czech', $lang->getEnglishName());
        $this->assertEquals('Čeština', $lang->getNativeName());
        $lang = $this->manager->getLanguage('nonexisting');
        $this->assertFalse($lang);
    }

    /**
     * Test language selection
     *
     * @param string $lang    Value for forced language
     * @param string $post    Value for language in POST
     * @param string $get     Value for language in GET
     * @param string $cookie  Value for language in COOKIE
     * @param string $accept  Value for HTTP Accept-Language header
     * @param string $agent   Value for HTTP User-Agent header
     * @param string $default Value for default language
     * @param string $expect  Expected language name
     *
     * @dataProvider selectDataProvider
     */
    public function testSelect(
        string $lang,
        string $post,
        string $get,
        string $cookie,
        string $accept,
        string $agent,
        string $default,
        string $expect
    ): void {
        $GLOBALS['PMA_Config']->set('FilterLanguages', '');
        $GLOBALS['PMA_Config']->set('Lang', $lang);
        $GLOBALS['PMA_Config']->set('is_https', false);
        $_POST['lang'] = $post;
        $_GET['lang'] = $get;
        $_COOKIE['pma_lang'] = $cookie;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept;
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $GLOBALS['PMA_Config']->set('DefaultLang', $default);

        $lang = $this->manager->selectLanguage();

        $this->assertEquals($expect, $lang->getEnglishName());

        $GLOBALS['PMA_Config']->set('Lang', '');
        $_POST['lang'] = '';
        $_GET['lang'] = '';
        $_COOKIE['pma_lang'] = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
        $_SERVER['HTTP_USER_AGENT'] = '';
        $GLOBALS['PMA_Config']->set('DefaultLang', 'en');
    }

    /**
     * Data provider for language selection test.
     *
     * @return array Test parameters.
     */
    public function selectDataProvider(): array
    {
        return [
            [
                'cs',
                'en',
                '',
                '',
                '',
                '',
                '',
                'Czech',
            ],
            [
                '',
                'cs',
                '',
                '',
                '',
                '',
                '',
                'Czech',
            ],
            [
                '',
                'cs',
                'en',
                '',
                '',
                '',
                '',
                'Czech',
            ],
            [
                '',
                '',
                'cs',
                '',
                '',
                '',
                '',
                'Czech',
            ],
            [
                '',
                '',
                '',
                'cs',
                '',
                '',
                '',
                'Czech',
            ],
            [
                '',
                '',
                '',
                '',
                'cs,en-US;q=0.7,en;q=0.3',
                '',
                '',
                'Czech',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                'Mozilla/5.0 (Linux; U; Android 2.2.2; tr-tr; GM FOX)',
                '',
                'Turkish',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                'cs',
                'Czech',
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'English',
            ],
        ];
    }

    /**
     * Test for setting and parsing locales
     *
     * @param string $locale locale name
     *
     * @group large
     * @dataProvider listLocales
     */
    public function testGettext(string $locale): void
    {
        $GLOBALS['PMA_Config']->set('FilterLanguages', '');
        /* We should be able to set the language */
        $this->manager->getLanguage($locale)->activate();

        /* Grab some texts */
        $this->assertStringContainsString('%s', _ngettext('%s table', '%s tables', 10));
        $this->assertStringContainsString('%s', _ngettext('%s table', '%s tables', 1));

        $this->assertEquals(
            $locale,
            $this->manager->getCurrentLanguage()->getCode()
        );
    }

    /**
     * Data provider to generate list of available locales.
     *
     * @return array with arrays of available locales
     */
    public function listLocales(): array
    {
        $ret = [];
        foreach (LanguageManager::getInstance()->availableLanguages() as $language) {
            $ret[] = [$language->getCode()];
        }

        return $ret;
    }
}
