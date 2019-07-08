<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

/**
 * Class FeaturesForm
 * @package PhpMyAdmin\Config\Forms\Setup
 */
class FeaturesForm extends \PhpMyAdmin\Config\Forms\User\FeaturesForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        // phpcs:disable Squiz.Arrays.ArrayDeclaration.KeySpecified,Squiz.Arrays.ArrayDeclaration.NoKeySpecified
        $result = parent::getForms();
        /* Remove only_db/hide_db, we have proper Server form in setup */
        $result['Databases'] = array_diff(
            $result['Databases'],
            [
                'Servers/1/only_db',
                'Servers/1/hide_db',
            ]
        );
        /* Following are not available to user */
        $result['Import_export'] = [
            'UploadDir',
            'SaveDir',
            'RecodingEngine' => ':group',
            'IconvExtraParams',
            ':group:end',
            'ZipDump',
            'GZipDump',
            'BZipDump',
            'CompressOnFly',
        ];
        $result['Security'] = [
            'blowfish_secret',
            'CheckConfigurationPermissions',
            'TrustedProxies',
            'AllowUserDropDatabase',
            'AllowArbitraryServer',
            'ArbitraryServerRegexp',
            'LoginCookieRecall',
            'LoginCookieStore',
            'LoginCookieDeleteAll',
            'CaptchaLoginPublicKey',
            'CaptchaLoginPrivateKey',
        ];
        $result['Developer'] = [
            'UserprefsDeveloperTab',
            'DBG/sql',
        ];
        $result['Other_core_settings'] = [
            'OBGzip',
            'PersistentConnections',
            'ExecTimeLimit',
            'MemoryLimit',
            'UseDbSearch',
            'ProxyUrl',
            'ProxyUser',
            'ProxyPass',
            'AllowThirdPartyFraming',
            'ZeroConf',
        ];
        return $result;
        // phpcs:enable
    }
}
