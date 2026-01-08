<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console\Console;
use PhpMyAdmin\Console\History;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Header;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function gmdate;

use const DATE_RFC1123;

#[CoversClass(Header::class)]
#[Medium]
class HeaderTest extends AbstractTestCase
{
    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        Current::$message = Message::success('phpmyadminmessage');
        Current::$database = 'db';
        Current::$table = '';

        $this->setGlobalConfig();

        $config = Config::getInstance();
        $config->settings['Servers'] = [];
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['verbose'] = 'verbose host';
        $config->selectedServer['pmadb'] = '';
        $config->selectedServer['user'] = '';
        $config->selectedServer['auth_type'] = 'cookie';
    }

    private function getNewHeaderInstance(): Header
    {
        $dbi = DatabaseInterface::getInstance();
        $config = Config::getInstance();
        $relation = new Relation($dbi, $config);
        $template = new Template($config);
        $history = new History($dbi, $relation, $config);
        $userPreferences = new UserPreferences($dbi, $relation, $template, $config);
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            $userPreferences,
            new LanguageManager($config),
            new ThemeManager(),
        );

        return new Header(
            $template,
            new Console($relation, $template, new BookmarkRepository($dbi, $relation), $history),
            $config,
            $dbi,
            $relation,
            $userPreferences,
            $userPreferencesHandler,
        );
    }

    public function testEnable(): void
    {
        Current::$server = 0;
        Current::$message = null;
        $config = Config::getInstance();
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi, $config);
        $template = new Template($config);
        $history = new History($dbi, $relation, $config);
        $console = new Console($relation, $template, new BookmarkRepository($dbi, $relation), $history);
        $userPreferences = new UserPreferences($dbi, $relation, $template, $config);
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            $userPreferences,
            new LanguageManager($config),
            new ThemeManager(),
        );
        $header = new Header($template, $console, $config, $dbi, $relation, $userPreferences, $userPreferencesHandler);

        $header->setBodyId('PMA_header_id');
        $actual = $header->getDisplay();
        $expected = [
            'lang' => 'en',
            'allow_third_party_framing' => false,
            'theme_path' => '',
            'server' => 0,
            'title' => 'phpMyAdmin',
            'scripts' => $header->getScripts()->getDisplay(),
            'body_id' => 'PMA_header_id',
            'navigation' => '',
            'custom_header' => '',
            'load_user_preferences' => '',
            'show_hint' => true,
            'is_warnings_enabled' => true,
            'is_menu_enabled' => true,
            'is_logged_in' => true,
            'menu' => '',
            'console' => $console->getDisplay(),
            'messages' => '',
            'theme_color_mode' => 'light',
            'theme_color_modes' => ['light'],
            'theme_id' => '',
            'current_user' => ['pma_test', 'localhost'],
            'is_mariadb' => false,
        ];
        self::assertSame($expected, $actual);
    }

    /**
     * Test for Get JsParams
     */
    public function testGetJsParams(): void
    {
        $header = $this->getNewHeaderInstance();
        self::assertArrayHasKey(
            'common_query',
            $header->getJsParams(),
        );
    }

    public function testGetJsParamsCode(): void
    {
        $header = $this->getNewHeaderInstance();
        self::assertStringContainsString(
            'window.Navigation.update(window.CommonParams.setAll(',
            $header->getJsParamsCode(),
        );
    }

    /**
     * Test for Get Message
     */
    public function testGetMessage(): void
    {
        $header = $this->getNewHeaderInstance();
        self::assertStringContainsString(
            'phpmyadminmessage',
            $header->getMessage(),
        );
    }

    /**
     * Test for Disable Warnings
     */
    public function testDisableWarnings(): void
    {
        $reflection = new ReflectionProperty(Header::class, 'warningsEnabled');

        $header = $this->getNewHeaderInstance();
        $header->disableWarnings();

        self::assertFalse($reflection->getValue($header));
    }

    #[DataProvider('providerForTestGetHttpHeaders')]
    public function testGetHttpHeaders(
        string|bool $frameOptions,
        string $cspAllow,
        string $privateKey,
        string $publicKey,
        string $captchaCsp,
        string|null $expectedFrameOptions,
        string $expectedCsp,
        string $expectedXCsp,
        string $expectedWebKitCsp,
    ): void {
        $header = $this->getNewHeaderInstance();
        $date = gmdate(DATE_RFC1123);

        $config = Config::getInstance();
        $config->set('AllowThirdPartyFraming', $frameOptions);
        $config->set('CSPAllow', $cspAllow);
        $config->set('CaptchaLoginPrivateKey', $privateKey);
        $config->set('CaptchaLoginPublicKey', $publicKey);
        $config->set('CaptchaCsp', $captchaCsp);

        $expected = [
            'X-Frame-Options' => $expectedFrameOptions,
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' => $expectedCsp,
            'X-Content-Security-Policy' => $expectedXCsp,
            'X-WebKit-CSP' => $expectedWebKitCsp,
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Permissions-Policy' => 'fullscreen=(self), interest-cohort=()',
            'Expires' => $date,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Pragma' => 'no-cache',
            'Last-Modified' => $date,
            'Content-Type' => 'text/html; charset=utf-8',
        ];
        if ($expectedFrameOptions === null) {
            unset($expected['X-Frame-Options']);
        }

        $headers = $this->callFunction($header, Header::class, 'getHttpHeaders', []);
        self::assertSame($expected, $headers);
    }

    /** @return mixed[][] */
    public static function providerForTestGetHttpHeaders(): array
    {
        return [
            [
                false,
                '',
                '',
                '',
                '',
                'DENY',
                'default-src \'self\' ;script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' ;'
                    . 'style-src \'self\' \'unsafe-inline\' ;img-src \'self\' data:  tile.openstreetmap.org;'
                    . 'object-src \'none\';',
                'default-src \'self\' ;options inline-script eval-script;referrer no-referrer;'
                    . 'img-src \'self\' data:  tile.openstreetmap.org;object-src \'none\';',
                'default-src \'self\' ;script-src \'self\'  \'unsafe-inline\' \'unsafe-eval\';'
                    . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\' ;'
                    . 'img-src \'self\' data:  tile.openstreetmap.org;object-src \'none\';',
            ],
            [
                'sameorigin',
                'example.com example.net',
                'PrivateKey',
                'PublicKey',
                'captcha.tld csp.tld',
                'SAMEORIGIN',
                'default-src \'self\'  captcha.tld csp.tld example.com example.net;'
                    . 'script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'  '
                    . 'captcha.tld csp.tld example.com example.net;'
                    . 'style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld example.com example.net;'
                    . 'img-src \'self\' data: example.com example.net tile.openstreetmap.org captcha.tld csp.tld ;'
                    . 'object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld example.com example.net;'
                    . 'options inline-script eval-script;referrer no-referrer;img-src \'self\' data: example.com '
                    . 'example.net tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld example.com example.net;script-src \'self\'  '
                    . 'captcha.tld csp.tld example.com example.net \'unsafe-inline\' \'unsafe-eval\';'
                    . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld ;'
                    . 'img-src \'self\' data: example.com example.net tile.openstreetmap.org captcha.tld csp.tld ;'
                    . 'object-src \'none\';',
            ],
            [
                true,
                '',
                'PrivateKey',
                'PublicKey',
                'captcha.tld csp.tld',
                null,
                'default-src \'self\'  captcha.tld csp.tld ;'
                    . 'script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'  captcha.tld csp.tld ;'
                    . 'style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld ;'
                    . 'img-src \'self\' data:  tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld ;'
                    . 'options inline-script eval-script;referrer no-referrer;'
                    . 'img-src \'self\' data:  tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld ;'
                    . 'script-src \'self\'  captcha.tld csp.tld  \'unsafe-inline\' \'unsafe-eval\';'
                    . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld ;'
                    . 'img-src \'self\' data:  tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
            ],
        ];
    }

    public function testAddedDefaultScripts(): void
    {
        $header = $this->getNewHeaderInstance();
        $scripts = $header->getScripts();
        $expected = [
            ['name' => 'runtime.js', 'fire' => 0],
            ['name' => 'vendor/jquery/jquery.min.js', 'fire' => 0],
            ['name' => 'vendor/jquery/jquery-migrate.min.js', 'fire' => 0],
            ['name' => 'vendor/sprintf.js', 'fire' => 0],
            ['name' => 'vendor/jquery/jquery-ui.min.js', 'fire' => 0],
            ['name' => 'vendor/bootstrap/bootstrap.js', 'fire' => 0],
            ['name' => 'vendor/js.cookie.min.js', 'fire' => 0],
            ['name' => 'vendor/jquery/jquery.validate.min.js', 'fire' => 0],
            ['name' => 'vendor/jquery/jquery-ui-timepicker-addon.min.js', 'fire' => 0],
            ['name' => 'index.php', 'fire' => 0],
            ['name' => 'shared.js', 'fire' => 0],
            ['name' => 'menu_resizer.js', 'fire' => 1],
            ['name' => 'main.js', 'fire' => 1],
        ];
        self::assertSame($expected, $scripts->getFiles());
    }
}
