<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use CodeLts\U2F\U2FServer\RegistrationRequest;
use CodeLts\U2F\U2FServer\SignRequest;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\TwoFactor\Application;
use PhpMyAdmin\Plugins\TwoFactor\Invalid;
use PhpMyAdmin\Plugins\TwoFactor\Key;
use PhpMyAdmin\Plugins\TwoFactor\Simple;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\TwoFactor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;

use function count;
use function in_array;
use function json_encode;
use function str_replace;

use const JSON_UNESCAPED_SLASHES;

#[CoversClass(TwoFactor::class)]
#[CoversClass(Application::class)]
#[CoversClass(Invalid::class)]
#[CoversClass(Key::class)]
#[CoversClass(Simple::class)]
class TwoFactorTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        Current::$database = '';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NaturalOrder'] = true;
        $this->initStorageConfigAndData();
    }

    protected function tearDown(): void
    {
        $this->dummyDbi->assertAllSelectsConsumed();
    }

    private function initStorageConfigAndData(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'groot';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = '';
        $config->selectedServer['pdf_pages'] = '';
        $config->selectedServer['history'] = '';
        $config->selectedServer['recent'] = '';
        $config->selectedServer['favorite'] = '';
        $config->selectedServer['table_uiprefs'] = '';
        $config->selectedServer['tracking'] = '';
        $config->selectedServer['userconfig'] = '';
        $config->selectedServer['users'] = '';
        $config->selectedServer['usergroups'] = '';
        $config->selectedServer['navigationhiding'] = '';
        $config->selectedServer['savedsearches'] = '';
        $config->selectedServer['central_columns'] = '';
        $config->selectedServer['designer_settings'] = '';
        $config->selectedServer['export_templates'] = '';

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::USER => 'groot',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    /**
     * Creates TwoFactor mock with custom configuration
     *
     * @param string  $user   Username
     * @param mixed[] $config Two factor authentication configuration
     */
    private function getTwoFactorAndLoadConfig(string $user, array|null $config): TwoFactor
    {
        if ($config !== null && ! isset($config['backend'])) {
            $config['backend'] = '';
        }

        if ($config !== null && ! isset($config['settings'])) {
            $config['settings'] = [];
        }

        $this->loadResultForConfig($config);

        return new TwoFactor($user);
    }

    /** @param mixed[] $backendSettings */
    private function loadQueriesForConfigure(string $backend, array $backendSettings = []): void
    {
        $this->dummyDbi->addResult(
            'SELECT `username` FROM `phpmyadmin`.`pma__userconfig` WHERE `username` = \'groot\'',
            [['groot']],
            ['username'],
        );

        $jsonData = (string) json_encode([
            'Console\\\\\\/Mode' => 'collapse',
            'lang' => 'fr',
            '2fa' => ['backend' => $backend, 'settings' => $backendSettings],
        ], JSON_UNESCAPED_SLASHES);
        $jsonData = str_replace('"', '\"', $jsonData);

        $this->dummyDbi->addResult(
            'UPDATE `phpmyadmin`.`pma__userconfig` SET `timevalue` = NOW(),'
            . ' `config_data` = \'' . $jsonData . '\' WHERE `username` = \'groot\'',
            true,
        );
    }

    private function loadResultForConfig(array|null $config): void
    {
        $this->dummyDbi->addResult(
            'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts'
            . ' FROM `phpmyadmin`.`pma__userconfig` WHERE `username` = \'groot\'',
            $config === null ? [] : [
                [(string) json_encode(['Console\/Mode' => 'collapse', 'lang' => 'fr', '2fa' => $config]), '1628632378'],
            ],
            ['config_data', 'ts'],
        );
    }

    public function testNone(): void
    {
        $request = new ServerRequest(self::createStub(ServerRequestInterface::class));
        $object = $this->getTwoFactorAndLoadConfig('user', ['type' => 'db']);
        $backend = $object->getBackend();
        self::assertSame('', $backend::$id);
        // Is always valid
        self::assertTrue($object->check($request, true));
        // Test session persistence
        self::assertTrue($object->check($request));
        self::assertTrue($object->check($request));
        self::assertSame('', $object->render($request));

        $_SESSION['two_factor_check'] = false;
        self::assertTrue($object->check($request));

        $this->dummyDbi->assertAllQueriesConsumed();

        $this->loadResultForConfig(['type' => 'db']);
        $this->loadQueriesForConfigure('');

        self::assertTrue($object->configure($request, ''));
        self::assertSame('', $object->setup($request));
    }

    public function testInvalidBackend(): void
    {
        $object = $this->getTwoFactorAndLoadConfig('user', ['type' => 'db', 'backend' => 'unknown-backend']);
        $backend = $object->getBackend();
        self::assertSame('invalid', $backend::$id);
        self::assertSame('Invalid two-factor authentication', $backend::getName());
        self::assertSame('Error fallback only!', $backend::getDescription());
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');
        self::assertFalse($object->check($request, true));
        self::assertStringContainsString(
            'The configured two factor authentication is not available, please install missing dependencies.',
            $object->render($request),
        );
        self::assertSame('', $object->setup($request));
    }

    public function testSimple(): void
    {
        $config = Config::getInstance();
        $config->config->debug->simple2fa = true;
        $object = $this->getTwoFactorAndLoadConfig('user', ['type' => 'db', 'backend' => 'simple']);
        $backend = $object->getBackend();
        self::assertSame('simple', $backend::$id);
        $config->config->debug->simple2fa = false;

        $serverRequestFactory = ServerRequestFactory::create();
        $request = $serverRequestFactory->createServerRequest('POST', 'https://example.com/');
        self::assertFalse($object->check($request, true));

        $request = $serverRequestFactory->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['2fa_confirm' => '1']);
        self::assertTrue($object->check($request, true));

        $request = $serverRequestFactory->createServerRequest('POST', 'https://example.com/');

        /* Test rendering */
        self::assertNotSame('', $object->render($request));
        self::assertSame('', $object->setup($request));
    }

    public function testLoad(): void
    {
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        $backend = $object->getBackend();
        self::assertSame('', $backend::$id);
    }

    public function testConfigureSimple(): void
    {
        $request = new ServerRequest(self::createStub(ServerRequestInterface::class));
        $config = Config::getInstance();
        $config->config->debug->simple2fa = true;
        $object = $this->getTwoFactorAndLoadConfig('user', null);

        $this->dummyDbi->assertAllQueriesConsumed();

        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('simple');

        self::assertTrue($object->configure($request, 'simple'));
        $backend = $object->getBackend();
        self::assertSame('simple', $backend::$id);

        $this->dummyDbi->assertAllQueriesConsumed();

        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('');

        self::assertTrue($object->configure($request, ''));
        $backend = $object->getBackend();
        self::assertSame('', $backend::$id);

        $this->dummyDbi->assertAllQueriesConsumed();

        $this->initStorageConfigAndData();// Needs a re-init

        $config->config->debug->simple2fa = false;
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        self::assertFalse($object->configure($request, 'simple'));
        $this->dummyDbi->assertAllQueriesConsumed();
    }

    #[Group('extension-iconv')]
    #[RequiresPhpExtension('iconv')]
    #[RequiresPhpExtension('xmlwriter')]
    public function testApplication(): void
    {
        $this->setLanguage();

        $request = new ServerRequest(self::createStub(ServerRequestInterface::class));

        $object = $this->getTwoFactorAndLoadConfig('user', null);
        if (! in_array('application', $object->getAvailable(), true)) {
            self::markTestSkipped('google2fa not available');
        }

        /* Without providing code this should fail */
        unset($_POST['2fa_code']);
        self::assertFalse($object->configure($request, 'application'));

        /* Invalid code */
        $_POST['2fa_code'] = 'invalid';
        self::assertFalse($object->configure($request, 'application'));

        /* Generate valid code */
        $app = $object->getBackend();
        self::assertInstanceOf(Application::class, $app);
        $google2fa = $app->getGoogle2fa();
        $_POST['2fa_code'] = $google2fa->oathTotp(
            $object->config['settings']['secret'],
            $google2fa->getTimestamp(),
        );

        $this->dummyDbi->assertAllQueriesConsumed();
        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('application', ['secret' => $object->config['settings']['secret']]);

        self::assertTrue($object->configure($request, 'application'));

        $this->dummyDbi->assertAllQueriesConsumed();
        unset($_POST['2fa_code']);

        /* Check code */
        unset($_POST['2fa_code']);
        self::assertFalse($object->check($request, true));
        $_POST['2fa_code'] = 'invalid';
        self::assertFalse($object->check($request, true));
        $_POST['2fa_code'] = $google2fa->oathTotp(
            $object->config['settings']['secret'],
            $google2fa->getTimestamp(),
        );
        self::assertTrue($object->check($request, true));
        unset($_POST['2fa_code']);

        /* Test rendering */
        self::assertNotSame('', $object->render($request));
        self::assertNotSame('', $object->setup($request));
    }

    public function testKey(): void
    {
        $this->setLanguage();

        $request = new ServerRequest(self::createStub(ServerRequestInterface::class));

        $object = $this->getTwoFactorAndLoadConfig('user', null);
        if (! in_array('key', $object->getAvailable(), true)) {
            self::markTestSkipped('u2f-php-server not available');
        }

        $_SESSION['registrationRequest'] = null;
        /* Without providing code this should fail */
        unset($_POST['u2f_registration_response']);
        self::assertFalse($object->configure($request, 'key'));

        /* Invalid code */
        $_POST['u2f_registration_response'] = 'invalid';
        self::assertFalse($object->configure($request, 'key'));

        /* Invalid code */
        $_POST['u2f_registration_response'] = '[]';
        self::assertFalse($object->configure($request, 'key'));

        /* Without providing code this should fail */
        unset($_POST['u2f_authentication_response']);
        self::assertFalse($object->check($request, true));

        /* Invalid code */
        $_POST['u2f_authentication_response'] = 'invalid';
        self::assertFalse($object->check($request, true));

        /* Invalid code */
        $_POST['u2f_authentication_response'] = '[]';
        self::assertFalse($object->check($request, true));

        /* Test rendering */
        self::assertNotSame('', $object->render($request));
        self::assertNotSame('', $object->setup($request));
    }

    /**
     * Test getting AppId
     */
    public function testKeyAppId(): void
    {
        $config = Config::getInstance();
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        $config->set('PmaAbsoluteUri', 'http://demo.example.com');
        self::assertSame('http://demo.example.com', $object->getBackend()->getAppId(true));
        self::assertSame('demo.example.com', $object->getBackend()->getAppId(false));
        $config->set('PmaAbsoluteUri', 'https://demo.example.com:123');
        self::assertSame('https://demo.example.com:123', $object->getBackend()->getAppId(true));
        self::assertSame('demo.example.com', $object->getBackend()->getAppId(false));
        $config->set('PmaAbsoluteUri', '');
        (new ReflectionProperty(Config::class, 'isHttps'))->setValue($config, true);
        $_SERVER['HTTP_HOST'] = 'pma.example.com';
        self::assertSame('https://pma.example.com', $object->getBackend()->getAppId(true));
        self::assertSame('pma.example.com', $object->getBackend()->getAppId(false));
        (new ReflectionProperty(Config::class, 'isHttps'))->setValue($config, false);
        self::assertSame('http://pma.example.com', $object->getBackend()->getAppId(true));
        self::assertSame('pma.example.com', $object->getBackend()->getAppId(false));
    }

    /**
     * Test based on upstream test data:
     * https://github.com/Yubico/php-u2flib-server
     */
    public function testKeyAuthentication(): void
    {
        $request = new ServerRequest(self::createStub(ServerRequestInterface::class));

        $object = $this->getTwoFactorAndLoadConfig('user', null);
        if (! in_array('key', $object->getAvailable(), true)) {
            self::markTestSkipped('u2f-php-server not available');
        }

        $_SESSION['registrationRequest'] = new RegistrationRequest(
            'yKA0x075tjJ-GE7fKTfnzTOSaNUOWQxRd9TWz5aFOg8',
            'http://demo.example.com',
        );
        unset($_POST['u2f_registration_response']);
        self::assertFalse($object->configure($request, 'key'));

        $_POST['u2f_registration_response'] = '';
        self::assertFalse($object->configure($request, 'key'));

        $_POST['u2f_registration_response'] = '{ "registrationData": "BQQtEmhWVgvbh-8GpjsHbj_d5F'
            . 'B9iNoRL8mNEq34-ANufKWUpVdIj6BSB_m3eMoZ3GqnaDy3RA5eWP8mhTkT1Ht3QAk1GsmaPIQgXgvrBk'
            . 'CQoQtMFvmwYPfW5jpRgoMPFxquHS7MTt8lofZkWAK2caHD-YQQdaRBgd22yWIjPuWnHOcwggLiMIHLAg'
            . 'EBMA0GCSqGSIb3DQEBCwUAMB0xGzAZBgNVBAMTEll1YmljbyBVMkYgVGVzdCBDQTAeFw0xNDA1MTUxMjU'
            . '4NTRaFw0xNDA2MTQxMjU4NTRaMB0xGzAZBgNVBAMTEll1YmljbyBVMkYgVGVzdCBFRTBZMBMGByqGSM49'
            . 'AgEGCCqGSM49AwEHA0IABNsK2_Uhx1zOY9ym4eglBg2U5idUGU-dJK8mGr6tmUQflaNxkQo6IOc-kV4T6'
            . 'L44BXrVeqN-dpCPr-KKlLYw650wDQYJKoZIhvcNAQELBQADggIBAJVAa1Bhfa2Eo7TriA_jMA8togoA2S'
            . 'UE7nL6Z99YUQ8LRwKcPkEpSpOsKYWJLaR6gTIoV3EB76hCiBaWN5HV3-CPyTyNsM2JcILsedPGeHMpMuW'
            . 'rbL1Wn9VFkc7B3Y1k3OmcH1480q9RpYIYr-A35zKedgV3AnvmJKAxVhv9GcVx0_CewHMFTryFuFOe78W8'
            . 'nFajutknarupekDXR4tVcmvj_ihJcST0j_Qggeo4_3wKT98CgjmBgjvKCd3Kqg8n9aSDVWyaOZsVOhZj3'
            . 'Fv5rFu895--D4qiPDETozJIyliH-HugoQpqYJaTX10mnmMdCa6aQeW9CEf-5QmbIP0S4uZAf7pKYTNmDQ'
            . '5z27DVopqaFw00MIVqQkae_zSPX4dsNeeoTTXrwUGqitLaGap5ol81LKD9JdP3nSUYLfq0vLsHNDyNgb3'
            . '06TfbOenRRVsgQS8tJyLcknSKktWD_Qn7E5vjOXprXPrmdp7g5OPvrbz9QkWa1JTRfo2n2AXV02LPFc-U'
            . 'fR9bWCBEIJBxvmbpmqt0MnBTHWnth2b0CU_KJTDCY3kAPLGbOT8A4KiI73pRW-e9SWTaQXskw3Ei_dHRI'
            . 'LM_l9OXsqoYHJ4Dd3tbfvmjoNYggSw4j50l3unI9d1qR5xlBFpW5sLr8gKX4bnY4SR2nyNiOQNLyPc0B0'
            . 'nW502aMEUCIQDTGOX-i_QrffJDY8XvKbPwMuBVrOSO-ayvTnWs_WSuDQIgZ7fMAvD_Ezyy5jg6fQeuOko'
            . 'Ji8V2naCtzV-HTly8Nww=", "clientData": "eyAiY2hhbGxlbmdlIjogInlLQTB4MDc1dGpKLUdFN2'
            . 'ZLVGZuelRPU2FOVU9XUXhSZDlUV3o1YUZPZzgiLCAib3JpZ2luIjogImh0dHA6XC9cL2RlbW8uZXhhbXB'
            . 'sZS5jb20iLCAidHlwIjogIm5hdmlnYXRvci5pZC5maW5pc2hFbnJvbGxtZW50IiB9", "errorCode": 0 }';

        $this->dummyDbi->assertAllQueriesConsumed();
        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('key', [
            'registrations' => [
                [
                    'keyHandle' => 'CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4'
                    . 'dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w',
                    'publicKey' => 'BC0SaFZWC9uH7wamOwduP93kUH2I2hEvyY0Srfj4A258pZSlV0iPoFIH'
                    . '+bd4yhncaqdoPLdEDl5Y\\/yaFORPUe3c=',
                    'certificate' => 'MIIC4jCBywIBATANBgkqhkiG9w0BAQsFADAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgQ0EwHhcN'
                    . 'MTQwNTE1MTI1ODU0WhcNMTQwNjE0MTI1ODU0WjAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgRUUwW'
                    . 'TATBgcqhkjOPQIBBggqhkjOPQMBBwNCAATbCtv1IcdczmPcpuHoJQYNlOYnVBlPnSSvJhq+rZlEH5WjcZ'
                    . 'EKOiDnPpFeE+i+OAV61XqjfnaQj6\\/iipS2MOudMA0GCSqGSIb3DQEBCwUAA4ICAQCVQGtQYX2thKO064'
                    . 'gP4zAPLaIKANklBO5y+mffWFEPC0cCnD5BKUqTrCmFiS2keoEyKFdxAe+oQogWljeR1d\\/gj8k8jbDNiX'
                    . 'CC7HnTxnhzKTLlq2y9Vp\\/VRZHOwd2NZNzpnB9ePNKvUaWCGK\\/gN+cynnYFdwJ75iSgMVYb\\/RnFcd'
                    . 'PwnsBzBU68hbhTnu\\/FvJxWo7rZJ2q7qXpA10eLVXJr4\\/4oSXEk9I\\/0IIHqOP98Ck\\/fAoI5gYI7'
                    . 'ygndyqoPJ\\/Wkg1VsmjmbFToWY9xb+axbvPefvg+KojwxE6MySMpYh\\/h7oKEKamCWk19dJp5jHQmumk'
                    . 'HlvQhH\\/uUJmyD9EuLmQH+6SmEzZg0Oc9uw1aKamhcNNDCFakJGnv80j1+HbDXnqE0168FBqorS2hmqea'
                    . 'JfNSyg\\/SXT950lGC36tLy7BzQ8jYG99Ok32znp0UVbIEEvLSci3JJ0ipLVg\\/0J+xOb4zl6a1z65nae'
                    . '4OTj7628\\/UJFmtSU0X6Np9gF1dNizxXPlH0fW1ggRCCQcb5m6ZqrdDJwUx1p7Ydm9AlPyiUwwmN5ADyx'
                    . 'mzk\\/AOCoiO96UVvnvUlk2kF7JMNxIv3R0SCzP5fTl7KqGByeA3d7W375o6DWIIEsOI+dJd7pyPXdakec'
                    . 'ZQRaVubC6\\/ICl+G52OEkdp8jYjkDS8j3NAdJ1udNmg==',
                    'counter' => -1,
                ],
            ],
        ]);

        self::assertTrue($object->configure($request, 'key'));

        unset($_POST['u2f_authentication_response']);
        self::assertFalse($object->check($request, true));

        $_POST['u2f_authentication_response'] = '';
        self::assertFalse($object->check($request, true));

        $_SESSION['authenticationRequest'] = [
            new SignRequest([
                'challenge' => 'fEnc9oV79EaBgK5BoNERU5gPKM2XGYWrz4fUjgc0Q7g',
                'keyHandle' => 'CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w',
                'appId' => 'http://demo.example.com',
            ]),
        ];
        self::assertFalse($object->check($request, true));
        $_POST['u2f_authentication_response'] = '{ "signatureData": "AQAAAAQwRQIhAI6FSrMD3KUUtkpiP0'
            . 'jpIEakql-HNhwWFngyw553pS1CAiAKLjACPOhxzZXuZsVO8im-HStEcYGC50PKhsGp_SUAng==", '
            . '"clientData": "eyAiY2hhbGxlbmdlIjogImZFbmM5b1Y3OUVhQmdLNUJvTkVSVTVnUEtNMlhHWVd'
            . 'yejRmVWpnYzBRN2ciLCAib3JpZ2luIjogImh0dHA6XC9cL2RlbW8uZXhhbXBsZS5jb20iLCAidHlwI'
            . 'jogIm5hdmlnYXRvci5pZC5nZXRBc3NlcnRpb24iIH0=", "keyHandle": "CTUayZo8hCBeC-sGQJC'
            . 'hC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w", "errorCode": 0 }';
        $this->dummyDbi->assertAllQueriesConsumed();
        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('key', [
            'registrations' => [
                [
                    'keyHandle' => 'CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJ'
                    . 'YiM-5acc5w',
                    'publicKey' => 'BC0SaFZWC9uH7wamOwduP93kUH2I2hEvyY0Srfj4A258pZSlV0iPoFIH+bd4yhncaqdo'
                        . 'PLdEDl5Y\\/yaFORPUe3c=',
                    'certificate' => 'MIIC4jCBywIBATANBgkqhkiG9w0BAQsFADAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgQ0EwHhcN'
                    . 'MTQwNTE1MTI1ODU0WhcNMTQwNjE0MTI1ODU0WjAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgRUUwW'
                    . 'TATBgcqhkjOPQIBBggqhkjOPQMBBwNCAATbCtv1IcdczmPcpuHoJQYNlOYnVBlPnSSvJhq+rZlEH5WjcZ'
                    . 'EKOiDnPpFeE+i+OAV61XqjfnaQj6\\/iipS2MOudMA0GCSqGSIb3DQEBCwUAA4ICAQCVQGtQYX2thKO064'
                    . 'gP4zAPLaIKANklBO5y+mffWFEPC0cCnD5BKUqTrCmFiS2keoEyKFdxAe+oQogWljeR1d\\/gj8k8jbDNiX'
                    . 'CC7HnTxnhzKTLlq2y9Vp\\/VRZHOwd2NZNzpnB9ePNKvUaWCGK\\/gN+cynnYFdwJ75iSgMVYb\\/RnFcd'
                    . 'PwnsBzBU68hbhTnu\\/FvJxWo7rZJ2q7qXpA10eLVXJr4\\/4oSXEk9I\\/0IIHqOP98Ck\\/fAoI5gYI7'
                    . 'ygndyqoPJ\\/Wkg1VsmjmbFToWY9xb+axbvPefvg+KojwxE6MySMpYh\\/h7oKEKamCWk19dJp5jHQmumk'
                    . 'HlvQhH\\/uUJmyD9EuLmQH+6SmEzZg0Oc9uw1aKamhcNNDCFakJGnv80j1+HbDXnqE0168FBqorS2hmqea'
                    . 'JfNSyg\\/SXT950lGC36tLy7BzQ8jYG99Ok32znp0UVbIEEvLSci3JJ0ipLVg\\/0J+xOb4zl6a1z65nae'
                    . '4OTj7628\\/UJFmtSU0X6Np9gF1dNizxXPlH0fW1ggRCCQcb5m6ZqrdDJwUx1p7Ydm9AlPyiUwwmN5ADyx'
                    . 'mzk\\/AOCoiO96UVvnvUlk2kF7JMNxIv3R0SCzP5fTl7KqGByeA3d7W375o6DWIIEsOI+dJd7pyPXdakec'
                    . 'ZQRaVubC6\\/ICl+G52OEkdp8jYjkDS8j3NAdJ1udNmg==',
                    'counter' => 4,
                ],
            ],
        ]);
        self::assertTrue($object->check($request, true));
        $this->dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Test listing of available backends.
     */
    public function testBackends(): void
    {
        $config = Config::getInstance();
        $config->config->debug->simple2fa = true;
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        $backends = $object->getAllBackends();
        self::assertCount(
            count($object->getAvailable()) + 1,
            $backends,
        );
        $config->config->debug->simple2fa = false;
    }
}
