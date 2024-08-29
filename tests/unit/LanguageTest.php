<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Language;
use PhpMyAdmin\LanguageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Large;

use function _ngettext;
use function count;
use function file_exists;
use function is_readable;
use function strtolower;

#[CoversClass(Language::class)]
#[CoversClass(LanguageManager::class)]
#[Large]
class LanguageTest extends AbstractTestCase
{
    private LanguageManager $manager;

    /**
     * Setup for Language tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $loc = LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo';
        if (! is_readable($loc)) {
            self::markTestSkipped('Missing compiled locales.');
        }

        $this->manager = new LanguageManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Ensure we have English locale after tests
        $lang = $this->manager->getLanguage('en');
        if ($lang === false) {
            return;
        }

        $lang->activate();
    }

    /**
     * Test language filtering
     */
    public function testAvailable(): void
    {
        $config = Config::getInstance();
        $config->set('FilterLanguages', 'cs|en$');

        $langs = $this->manager->availableLocales();

        self::assertCount(2, $langs);
        self::assertContains('cs', $langs);
        $config->set('FilterLanguages', '');
    }

    /**
     * Test no language filtering
     */
    public function testAllAvailable(): void
    {
        Config::getInstance()->set('FilterLanguages', '');

        $langs = $this->manager->availableLocales();

        self::assertContains('cs', $langs);
        self::assertContains('en', $langs);
    }

    /**
     * Test whether listing locales works
     */
    public function testList(): void
    {
        $langs = $this->manager->listLocaleDir();
        self::assertContains('cs', $langs);
        self::assertContains('en', $langs);
    }

    /**
     * Test for getting available languages
     */
    public function testLanguages(): void
    {
        $langs = $this->manager->availableLanguages();
        self::assertGreaterThan(1, count($langs));

        /* Ensure we have name for every language */
        foreach ($langs as $lang) {
            self::assertNotEquals(
                $lang->getCode(),
                strtolower($lang->getEnglishName()),
                'Maybe this language does not exist in LanguageManager class'
                . ', see: https://github.com/phpmyadmin/phpmyadmin/issues/16300.',
            );
        }
    }

    /**
     * Test for MySQL locales
     */
    public function testMySQLLocale(): void
    {
        Config::getInstance()->set('FilterLanguages', '');
        $czech = $this->manager->getLanguage('cs');
        self::assertNotFalse($czech);
        self::assertSame('cs_CZ', $czech->getMySQLLocale());

        $azerbaijani = $this->manager->getLanguage('az');
        self::assertNotFalse($azerbaijani);
        self::assertSame('', $azerbaijani->getMySQLLocale());
    }

    /**
     * Test for getting available sorted languages
     */
    public function testSortedLanguages(): void
    {
        $langs = $this->manager->sortedLanguages();
        self::assertGreaterThan(1, count($langs));
    }

    /**
     * Test getting language by code
     */
    public function testGet(): void
    {
        Config::getInstance()->set('FilterLanguages', '');
        $lang = $this->manager->getLanguage('cs');
        self::assertNotFalse($lang);
        self::assertSame('Czech', $lang->getEnglishName());
        self::assertSame('Čeština', $lang->getNativeName());
        $lang = $this->manager->getLanguage('nonexisting');
        self::assertFalse($lang);
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
     * @param string $expect  Expected language code
     */
    #[DataProvider('selectDataProvider')]
    public function testSelect(
        string $lang,
        string $post,
        string $get,
        string $cookie,
        string $accept,
        string $agent,
        string $default,
        string $expect,
    ): void {
        if ($expect !== 'en' && ! file_exists(LOCALE_PATH . '/' . $expect . '/LC_MESSAGES/phpmyadmin.mo')) {
            // This could happen after removing incomplete .mo files.
            self::markTestSkipped('Locale file does not exists: ' . $expect);
        }

        $config = Config::getInstance();
        $config->set('FilterLanguages', '');
        $config->set('Lang', $lang);
        $config->set('is_https', false);
        $_POST['lang'] = $post;
        $_GET['lang'] = $get;
        $_COOKIE['pma_lang'] = $cookie;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept;
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $config->set('DefaultLang', $default);

        $lang = $this->manager->selectLanguage();

        self::assertSame($expect, $lang->getCode());

        $config->set('Lang', '');
        $_POST['lang'] = '';
        $_GET['lang'] = '';
        $_COOKIE['pma_lang'] = '';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
        $_SERVER['HTTP_USER_AGENT'] = '';
        $config->set('DefaultLang', 'en');
    }

    /**
     * Data provider for language selection test.
     *
     * @return string[][]
     */
    public static function selectDataProvider(): array
    {
        return [
            ['cs', 'en', '', '', '', '', '', 'cs'],
            ['', 'cs', '', '', '', '', '', 'cs'],
            ['', 'cs', 'en', '', '', '', '', 'cs'],
            ['', '', 'cs', '', '', '', '', 'cs'],
            ['', '', '', 'cs', '', '', '', 'cs'],
            ['', '', '', '', 'cs,en-US;q=0.7,en;q=0.3', '', '', 'cs'],
            ['', '', '', '', '', 'Mozilla/5.0 (Linux; U; Android 2.2.2; tr-tr; GM FOX)', '', 'tr'],
            ['', '', '', '', '', '', 'cs', 'cs'],
            ['', '', '', '', '', '', '', 'en'],
            ['', '', '', '', 'pt;q=0.8,en-US;q=0.5,en;q=0.3', '', 'en', 'pt'],
            ['', '', '', '', 'pt-PT,pt;q=0.8,en-US;q=0.5,en;q=0.3', '', 'en', 'pt'],
            ['', '', '', '', 'pt-BR,pt;q=0.8,en-US;q=0.5,en;q=0.3', '', 'en', 'pt_BR'],
            ['', '', '', '', 'ar;q=0.8,en-US;q=0.5,en;q=0.3', '', 'en', 'ar'],
            ['', '', '', '', 'ar-AE,ar;q=0.8,en-US;q=0.5,en;q=0.3', '', 'en', 'ar'],
            ['', '', '', '', 'ar-LY,ar;q=0.8,en-US;q=0.5,en;q=0.3', '', 'en', 'ar_LY'],
            ['', '', '', '', 'en,pt;q=0.5', '', 'pt', 'en'],
            ['', '', '', '', 'en-GB,en;q=0.7,pt;q=0.3', '', 'pt', 'en_GB'],
            ['', '', '', '', 'en-US,en;q=0.7,pt;q=0.3', '', 'pt', 'en'],
            ['', '', '', '', 'zh,en;q=0.5', '', 'en', 'zh_CN'],
            ['', '', '', '', 'zh-CN,zh;q=0.7,en;q=0.3', '', 'en', 'zh_CN'],
            ['', '', '', '', 'zh-HK,zh;q=0.7,en;q=0.3', '', 'en', 'zh_TW'],
            ['', '', '', '', 'zh-TW,zh;q=0.7,en;q=0.3', '', 'en', 'zh_TW'],
        ];
    }

    /**
     * Test for setting and parsing locales
     *
     * @param string $locale locale name
     */
    #[DataProvider('listLocales')]
    public function testGettext(string $locale): void
    {
        Config::getInstance()->set('FilterLanguages', '');
        /* We should be able to set the language */
        $lang = $this->manager->getLanguage($locale);
        self::assertNotFalse($lang);
        $lang->activate();

        /* Grab some texts */
        self::assertStringContainsString('%s', _ngettext('%s table', '%s tables', 10));
        self::assertStringContainsString('%s', _ngettext('%s table', '%s tables', 1));

        self::assertSame(
            $locale,
            $this->manager->getCurrentLanguage()->getCode(),
        );
    }

    /**
     * Data provider to generate list of available locales.
     *
     * @return mixed[] with arrays of available locales
     */
    public static function listLocales(): array
    {
        $ret = [];
        foreach (LanguageManager::getInstance()->availableLanguages() as $language) {
            $ret[] = [$language->getCode()];
        }

        return $ret;
    }
}
