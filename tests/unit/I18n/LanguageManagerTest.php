<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\I18n;

use PhpMyAdmin\Config;
use PhpMyAdmin\I18n\Language;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Large;

use function _ngettext;
use function array_map;
use function count;
use function file_exists;
use function is_readable;
use function strtolower;

#[CoversClass(Language::class)]
#[CoversClass(LanguageManager::class)]
#[Large]
final class LanguageManagerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (is_readable(LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo')) {
            return;
        }

        self::markTestSkipped('Missing compiled locales.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Ensure we have English locale after tests
        $languageManager = new LanguageManager(new Config());
        $lang = $languageManager->getLanguage('en');
        self::assertNotFalse($lang);
        $languageManager->activate($lang);
    }

    public function testUniqueness(): void
    {
        LanguageManager::$instance = null;
        $instanceOne = LanguageManager::getInstance();
        $instanceTwo = LanguageManager::getInstance();
        self::assertSame($instanceOne, $instanceTwo);
    }

    /**
     * Test language filtering
     */
    public function testAvailable(): void
    {
        $config = new Config();
        $config->set('FilterLanguages', 'cs|en$');

        $langs = (new LanguageManager($config))->availableLocales();

        self::assertCount(2, $langs);
        self::assertContains('cs', $langs);
        $config->set('FilterLanguages', '');
    }

    /**
     * Test no language filtering
     */
    public function testAllAvailable(): void
    {
        $config = new Config();
        $config->set('FilterLanguages', '');

        $langs = (new LanguageManager($config))->availableLocales();

        self::assertContains('cs', $langs);
        self::assertContains('en', $langs);
    }

    /**
     * Test whether listing locales works
     */
    public function testList(): void
    {
        $langs = (new LanguageManager(new Config()))->listLocaleDir();
        self::assertContains('cs', $langs);
        self::assertContains('en', $langs);
    }

    /**
     * Test for getting available languages
     */
    public function testLanguages(): void
    {
        $langs = (new LanguageManager(new Config()))->availableLanguages();
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
        $config = new Config();
        $config->set('FilterLanguages', '');
        $languageManager = new LanguageManager($config);
        $czech = $languageManager->getLanguage('cs');
        self::assertNotFalse($czech);
        self::assertSame('cs_CZ', $czech->getMySQLLocale());

        $azerbaijani = $languageManager->getLanguage('az');
        self::assertNotFalse($azerbaijani);
        self::assertSame('', $azerbaijani->getMySQLLocale());
    }

    /**
     * Test for getting available sorted languages
     */
    public function testSortedLanguages(): void
    {
        $langs = (new LanguageManager(new Config()))->sortedLanguages();
        self::assertGreaterThan(1, count($langs));
    }

    /**
     * Test getting language by code
     */
    public function testGet(): void
    {
        $config = new Config();
        $config->set('FilterLanguages', '');
        $languageManager = new LanguageManager($config);
        $lang = $languageManager->getLanguage('cs');
        self::assertNotFalse($lang);
        self::assertSame('Czech', $lang->getEnglishName());
        self::assertSame('Čeština', $lang->getNativeName());
        $lang = $languageManager->getLanguage('nonexisting');
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

        $config = new Config();
        $config->set('FilterLanguages', '');
        $config->set('Lang', $lang);
        $config->set('is_https', false);
        $_POST['lang'] = $post;
        $_GET['lang'] = $get;
        $_COOKIE['pma_lang'] = $cookie;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $accept;
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $config->set('DefaultLang', $default);

        $lang = (new LanguageManager($config))->selectLanguage();

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

    #[DataProvider('availableLocalesProvider')]
    public function testSettingAndParsingLocales(string $localeCode): void
    {
        $config = new Config();
        $config->set('FilterLanguages', '');
        /* We should be able to set the language */
        $languageManager = new LanguageManager($config);
        $lang = $languageManager->getLanguage($localeCode);
        self::assertNotFalse($lang);
        $languageManager->activate($lang);

        /* Grab some texts */
        self::assertStringContainsString('%s', _ngettext('%s table', '%s tables', 10));
        self::assertStringContainsString('%s', _ngettext('%s table', '%s tables', 1));

        self::assertSame($localeCode, $languageManager->getCurrentLanguage()->getCode());
    }

    /**
     * Data provider to generate list of available locales.
     *
     * @return array<string, array{string}>
     */
    public static function availableLocalesProvider(): array
    {
        $availableLanguages = (new LanguageManager(new Config()))->availableLanguages();

        return array_map(static fn ($language) => [$language->getCode()], $availableLanguages);
    }
}
