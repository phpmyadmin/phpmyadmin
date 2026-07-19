<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Clock\Clock;
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
use PhpMyAdmin\Tests\Clock\MockClock;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function sprintf;

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
        $userPreferences = new UserPreferences($dbi, $relation, $template, $config, new Clock());
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            $userPreferences,
            new LanguageManager($config),
            new ThemeManager(),
        );

        return new Header(
            $template,
            new Console($relation, $template, new BookmarkRepository($dbi, $relation, $config), $history),
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
        $console = new Console($relation, $template, new BookmarkRepository($dbi, $relation, $config), $history);
        $userPreferences = new UserPreferences($dbi, $relation, $template, $config, new Clock());
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            $userPreferences,
            new LanguageManager($config),
            new ThemeManager(),
        );
        $header = new Header($template, $console, $config, $dbi, $relation, $userPreferences, $userPreferencesHandler);

        $header->setBodyId('PMA_header_id');
        $actual = $header->getDisplay(new ResponseRenderer());
        $expected = [
            'lang' => 'en',
            'allow_third_party_framing' => false,
            'codemirror_enable' => true,
            'lint_enable' => true,
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
     * @param array<mixed>|string|null $getParam
     * @param array<mixed>|string|null $postParam
     */
    #[DataProvider('getMessageProvider')]
    public function testGetMessage(
        string $expected,
        string|null $current,
        array|string|null $getParam,
        array|string|null $postParam,
        bool $hasSqlQuery,
    ): void {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$sqlQuery = $hasSqlQuery ? 'SELECT 1' : '';
        Current::$message = $current === null ? null : Message::success($current);
        $_GET['message'] = $getParam;
        $_POST['message'] = $postParam;
        self::assertSame($expected, $this->getNewHeaderInstance()->getMessage());
        self::assertNull(Current::$message);
    }

    /** @return iterable<array-key, array{string, string|null, array<mixed>|string|null, array<mixed>|string|null, bool}> */
    public static function getMessageProvider(): iterable
    {
        $message = <<<'HTML'
            <div class="alert %s" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon %s
            </div>

            HTML;

        // phpcs:disable Generic.Files.LineLength.TooLong
        $messageWithSqlQuery = <<<'HTML'
            <div class="card mb-3 result_query">
            <div class="alert %s border-top-0 border-start-0 border-end-0 rounded-bottom-0 mb-0" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon %s
            </div>
            <div class="card-body sqlOuter"><pre><code class="sql" dir="ltr">SELECT 1</code></pre></div>
            <div class="card-footer tools d-print-none">
            <div class="row align-items-center">
            <div class="col-auto">
            <form action="index.php?route=/sql&db=test_db&table=test_table&lang=en" method="post" class="disableAjax">
            <input type="hidden" name="db" value="test_db"><input type="hidden" name="table" value="test_table"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">
            <input type="hidden" name="sql_query" value="SELECT 1">
            </form></div>
            <div class="col-auto"><a href="#" class="btn btn-link inline_edit_sql">Edit inline</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/table/sql&db=test_db&table=test_table&sql_query=SELECT+1&show_query=1&lang=en" class="btn btn-link">Edit</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=EXPLAIN+SELECT+1&lang=en" class="btn btn-link">Explain SQL</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=SELECT+1&show_query=1&show_as_php=1&lang=en" class="btn btn-link">Create PHP code</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/sql&db=test_db&table=test_table&sql_query=SELECT+1&show_query=1&lang=en" class="btn btn-link">Refresh</a></div>
            </div></div></div>
            HTML;
        // phpcs:enable

        yield ['', null, null, null, false];
        yield ['', null, null, null, true];
        yield ['', null, '', null, false];
        yield ['', null, null, '', false];
        yield ['', null, [], null, false];
        yield ['', null, null, [], false];
        yield ['', null, ['message'], null, false];
        yield ['', null, null, ['message'], false];

        yield [sprintf($message, 'alert-primary', 'ic_s_notice"> Get'), null, 'Get', null, false];
        yield [sprintf($message, 'alert-primary', 'ic_s_notice"> Post'), null, null, 'Post', false];
        yield [sprintf($message, 'alert-primary', 'ic_s_notice"> Post'), null, 'Get', 'Post', false];
        yield [sprintf($message, 'alert-success', 'ic_s_success"> Current'), 'Current', null, null, false];
        yield [sprintf($message, 'alert-success', 'ic_s_success"> Current'), 'Current', 'Get', null, false];
        yield [sprintf($message, 'alert-success', 'ic_s_success"> Current'), 'Current', null, 'Post', false];
        yield [sprintf($message, 'alert-success', 'ic_s_success"> Current'), 'Current', 'Get', 'Post', false];

        yield [sprintf($messageWithSqlQuery, 'alert-success', 'ic_s_success"> Current'), 'Current', null, null, true];
        yield [
            sprintf($messageWithSqlQuery, 'alert-primary', 'ic_s_notice"> A &lt;em&gt;B&lt;/em&gt; <em>C</em> D'),
            null,
            'A <em>B</em> [em]C[/em] D',
            null,
            true,
        ];
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
        string $expectedCsp,
    ): void {
        $header = $this->getNewHeaderInstance();

        $config = Config::getInstance();
        $config->set('AllowThirdPartyFraming', $frameOptions);
        $config->set('CSPAllow', $cspAllow);
        $config->set('CaptchaLoginPrivateKey', $privateKey);
        $config->set('CaptchaLoginPublicKey', $publicKey);
        $config->set('CaptchaCsp', $captchaCsp);

        $expected = [
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' => $expectedCsp,
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Permissions-Policy' => 'fullscreen=(self), interest-cohort=()',
            'Expires' => 'Wed, 21 Oct 2015 07:28:00 GMT',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Pragma' => 'no-cache',
            'Last-Modified' => 'Wed, 21 Oct 2015 07:28:00 GMT',
            'Content-Type' => 'text/html; charset=utf-8',
        ];

        self::assertSame($expected, $header->getHttpHeaders(MockClock::from('2015-10-21T05:28:00-02:00')));
    }

    /** @psalm-return list<array{string|bool, string, string, string, string, string}> */
    public static function providerForTestGetHttpHeaders(): array
    {
        return [
            [
                false,
                '',
                '',
                '',
                '',
                "default-src 'self';"
                    . " img-src 'self' data: https://tile.openstreetmap.org;"
                    . " object-src 'none';"
                    . " script-src 'self' 'unsafe-inline' 'unsafe-eval';"
                    . " style-src 'self' 'unsafe-inline';"
                    . " frame-ancestors 'none';",
            ],
            [
                'sameorigin',
                'example.com example.net',
                'PrivateKey',
                'PublicKey',
                'captcha.tld csp.tld',
                "default-src 'self' captcha.tld csp.tld example.com example.net;"
                    . " img-src 'self' data: captcha.tld csp.tld example.com example.net"
                    . ' https://tile.openstreetmap.org;'
                    . " object-src 'none';"
                    . " script-src 'self' 'unsafe-inline' 'unsafe-eval' captcha.tld csp.tld example.com example.net;"
                    . " style-src 'self' 'unsafe-inline' captcha.tld csp.tld example.com example.net;"
                    . " frame-ancestors 'self';",
            ],
            [
                true,
                '',
                'PrivateKey',
                'PublicKey',
                'captcha.tld csp.tld',
                "default-src 'self' captcha.tld csp.tld;"
                    . " img-src 'self' data: captcha.tld csp.tld https://tile.openstreetmap.org;"
                    . " object-src 'none';"
                    . " script-src 'self' 'unsafe-inline' 'unsafe-eval' captcha.tld csp.tld;"
                    . " style-src 'self' 'unsafe-inline' captcha.tld csp.tld;",
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
            ['name' => 'jquery-ui-timepicker-addon.js', 'fire' => 0],
            ['name' => 'index.php', 'fire' => 0],
            ['name' => 'shared.js', 'fire' => 0],
            ['name' => 'menu_resizer.js', 'fire' => 1],
            ['name' => 'main.js', 'fire' => 1],
        ];
        self::assertSame($expected, $scripts->getFiles());
    }
}
