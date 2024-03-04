<?php
/**
 * Responsible for retrieving version information and notifying about latest version
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Utils\HttpRequest;

use function count;
use function explode;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function preg_match;
use function str_starts_with;
use function strlen;
use function substr;
use function time;
use function version_compare;

use const PHP_VERSION;

/**
 * Responsible for retrieving version information and notifying about latest version
 */
class VersionInformation
{
    /**
     * Returns information with latest version from phpmyadmin.net
     *
     * @return Release[]|null JSON decoded object with the data
     */
    public function getLatestVersions(): array|null
    {
        if (! Config::getInstance()->settings['VersionCheck']) {
            return null;
        }

        // Get response text from phpmyadmin.net or from the session
        // Update cache every 6 hours
        if (
            isset($_SESSION['cache']['version_check'])
            && time() < $_SESSION['cache']['version_check']['timestamp'] + 3600 * 6
        ) {
            $save = false;
            $response = $_SESSION['cache']['version_check']['response'];
        } else {
            $save = true;
            $file = 'https://www.phpmyadmin.net/home_page/version.json';
            $httpRequest = new HttpRequest();
            $response = $httpRequest->create($file, 'GET');
        }

        $response = $response ?: '{}';
        /* Parse response */
        $data = json_decode($response, true);

        /* Basic sanity checking */
        if (! is_array($data) || ! isset($data['releases']) || ! is_array($data['releases'])) {
            return null;
        }

        if ($save) {
            $_SESSION['cache']['version_check'] = ['response' => $response, 'timestamp' => time()];
        }

        $releases = [];
        /** @var string[] $release */
        foreach ($data['releases'] as $release) {
            $releases[] = new Release(
                $release['version'],
                $release['date'],
                $release['php_versions'],
                $release['mysql_versions'],
            );
        }

        return $releases;
    }

    /**
     * Calculates numerical equivalent of phpMyAdmin version string
     *
     * @param string $version version
     */
    public function versionToInt(string $version): int
    {
        $parts = explode('-', $version);
        $suffix = count($parts) > 1 ? $parts[1] : '';

        $parts = explode('.', $parts[0]);

        $result = 0;

        if (count($parts) >= 1 && is_numeric($parts[0])) {
            $result += 1000000 * (int) $parts[0];
        }

        if (count($parts) >= 2 && is_numeric($parts[1])) {
            $result += 10000 * (int) $parts[1];
        }

        if (count($parts) >= 3 && is_numeric($parts[2])) {
            $result += 100 * (int) $parts[2];
        }

        if (count($parts) >= 4 && is_numeric($parts[3])) {
            $result += (int) $parts[3];
        }

        if ($suffix !== '') {
            $matches = [];
            if (preg_match('/^(\D+)(\d+)$/', $suffix, $matches)) {
                $suffix = $matches[1];
                $result += (int) $matches[2];
            }

            switch ($suffix) {
                case 'pl':
                    $result += 60;
                    break;
                case 'rc':
                    $result += 30;
                    break;
                case 'beta':
                    $result += 20;
                    break;
                case 'alpha':
                    $result += 10;
                    break;
                case 'dev':
                    break;
            }
        } else {
            $result += 50; // for final
        }

        return $result;
    }

    /**
     * Returns the version and date of the latest phpMyAdmin version compatible
     * with the available PHP and MySQL versions
     *
     * @param Release[] $releases array of information related to each version
     *
     * @return Release|null containing the version and date of latest compatible version
     */
    public function getLatestCompatibleVersion(array $releases): Release|null
    {
        // Maintains the latest compatible version
        $latestRelease = null;
        foreach ($releases as $release) {
            $phpVersions = $release->phpVersions;
            $phpConditions = explode(',', $phpVersions);
            foreach ($phpConditions as $phpCondition) {
                if (! $this->evaluateVersionCondition('PHP', $phpCondition)) {
                    /** @infection-ignore-all */
                    continue 2;
                }
            }

            // We evaluate MySQL version constraint if there are only
            // one server configured.
            if (count(Config::getInstance()->settings['Servers']) === 1) {
                $mysqlVersions = $release->mysqlVersions;
                $mysqlConditions = explode(',', $mysqlVersions);
                foreach ($mysqlConditions as $mysqlCondition) {
                    if (! $this->evaluateVersionCondition('MySQL', $mysqlCondition)) {
                        continue 2;
                    }
                }
            }

            // To compare the current release with the previous latest release or no release is set
            if ($latestRelease !== null && ! version_compare($latestRelease->version, $release->version, '<')) {
                continue;
            }

            $latestRelease = $release;
        }

        // no compatible version
        return $latestRelease;
    }

    /**
     * Checks whether PHP or MySQL version meets supplied version condition
     *
     * @param string $type      PHP or MySQL
     * @param string $condition version condition
     *
     * @return bool whether the condition is met
     */
    public function evaluateVersionCondition(string $type, string $condition): bool
    {
        $operator = null;
        $version = null;
        $operators = ['<=', '>=', '!=', '<>', '<', '>', '=']; // preserve order
        foreach ($operators as $oneOperator) {
            if (str_starts_with($condition, $oneOperator)) {
                $operator = $oneOperator;
                $version = substr($condition, strlen($oneOperator));
                break;
            }
        }

        $myVersion = null;
        if ($type === 'PHP') {
            $myVersion = $this->getPHPVersion();
        } elseif ($type === 'MySQL') {
            $myVersion = $this->getMySQLVersion();
        }

        if (is_string($myVersion) && is_string($version) && is_string($operator)) {
            return version_compare($myVersion, $version, $operator);
        }

        return false;
    }

    /**
     * Returns the PHP version
     *
     * @return string PHP version
     */
    protected function getPHPVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * Returns the MySQL version if connected to a database
     *
     * @return string|null MySQL version
     */
    protected function getMySQLVersion(): string|null
    {
        $dbi = DatabaseInterface::getInstance();

        return $dbi->isConnected() ? $dbi->getVersionString() : null;
    }
}
