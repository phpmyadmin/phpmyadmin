<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PHPUnit\Framework\TestCase;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Language;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Config;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/**
 * Abstract class to hold some usefull methods used in tests
 * And make tests clean
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * The variables to keep between tests
     *
     * @var string[]
     */
    private $globalsWhiteList = [
        '__composer_autoload_files',
        'GLOBALS',
        '_SERVER',
        '__composer_autoload_files',
        '__PHPUNIT_CONFIGURATION_FILE',
        '__PHPUNIT_BOOTSTRAP',
        'TESTSUITE_SERVER',
        'TESTSUITE_USER',
        'TESTSUITE_PASSWORD',
        'TESTSUITE_DATABASE',
        'TESTSUITE_PORT',
    ];

    /**
     * Prepares environment for the test.
     * Clean all variables
     */
    protected function setUp(): void
    {
        foreach ($GLOBALS as $key => $val) {
            if (in_array($key, $this->globalsWhiteList)) {
                continue;
            }
            unset($GLOBALS[$key]);
        }
        $_GET = [];
        $_POST = [];
        $_SESSION = [
            ' PMA_token ' => 'token',
        ];
        $_COOKIE = [];
        $_FILES = [];
        $_REQUEST = [];
        // Config before DBI
        $this->setGlobalConfig();
        $this->setGlobalDbi();
    }

    protected function loadDefaultConfig(): void
    {
        global $cfg;

        require ROOT_PATH . 'libraries/config.default.php';
    }

    protected function setGlobalDbi(): void
    {
        global $dbi;
        $dbi = DatabaseInterface::load(new DbiDummy());
    }

    protected function setGlobalConfig(): void
    {
        global $PMA_Config;
        $PMA_Config = new Config();
        $PMA_Config->set('environment', 'development');
    }

    protected function setTheme(): void
    {
        global $PMA_Theme;
        $PMA_Theme = Theme::load(ROOT_PATH . 'themes/pmahomme');
    }

    protected function setLanguage(string $code = 'en'): void
    {
        global $lang;

        $lang = $code;
        /* Ensure default language is active */
        /** @var Language $languageEn */
        $languageEn = LanguageManager::getInstance()->getLanguage($code);
        $languageEn->activate();
        Translator::load();
    }

    protected function setProxySettings(): void
    {
        $httpProxy = getenv('http_proxy');
        $urlInfo = parse_url((string) $httpProxy);
        if (PHP_SAPI == 'cli' && is_array($urlInfo)) {
            $proxyUrl = ($urlInfo['host'] ?? '')
                . (isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '');
            $proxyUser = $urlInfo['user'] ?? '';
            $proxyPass = $urlInfo['pass'] ?? '';

            $GLOBALS['cfg']['ProxyUrl'] = $proxyUrl;
            $GLOBALS['cfg']['ProxyUser'] = $proxyUser;
            $GLOBALS['cfg']['ProxyPass'] = $proxyPass;
        }

        // phpcs:disable PSR1.Files.SideEffects
        if (! defined('PROXY_URL')) {
            define('PROXY_URL', $proxyUrl ?? '');
            define('PROXY_USER', $proxyUser ?? '');
            define('PROXY_PASS', $proxyPass ?? '');
        }
        // phpcs:enable
    }

    protected function setUpEnv(): void
    {
        require ROOT_PATH . 'test/bootstrap-dist.php';
    }

    /**
     * Desctroys the environment built for the test.
     * Clean all variables
     */
    protected function tearDown(): void
    {
        foreach ($GLOBALS as $key => $val) {
            if (in_array($key, $this->globalsWhiteList)) {
                continue;
            }
            unset($GLOBALS[$key]);
        }
    }
}
