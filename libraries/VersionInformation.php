<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Responsile for retrieving version information and notifiying about latest version
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Responsile for retrieving version information and notifiying about latest version
 *
 * @package PhpMyAdmin
 *
 */
class VersionInformation
{
    /**
     * Returns information with latest version from phpmyadmin.net
     *
     * @return object JSON decoded object with the data
     */
    public function getLatestVersion()
    {
        if (!$GLOBALS['cfg']['VersionCheck']) {
            return null;
        }

        // wait 3s at most for server response, it's enough to get information
        // from a working server
        $connection_timeout = 3;

        $response = '{}';
        // Get response text from phpmyadmin.net or from the session
        // Update cache every 6 hours
        if (isset($_SESSION['cache']['version_check'])
            && time() < $_SESSION['cache']['version_check']['timestamp'] + 3600 * 6
        ) {
            $save = false;
            $response = $_SESSION['cache']['version_check']['response'];
        } else {
            $save = true;
            $file = 'https://www.phpmyadmin.net/home_page/version.json';
            if (ini_get('allow_url_fopen')) {
                $context = array(
                    'http' => array(
                        'request_fulluri' => true,
                        'timeout' => $connection_timeout,
                    )
                );
                $context = PMA_Util::handleContext($context);
                if (! defined('TESTSUITE')) {
                    session_write_close();
                }
                $response = file_get_contents(
                    $file,
                    false,
                    stream_context_create($context)
                );
            } else if (function_exists('curl_init')) {
                $curl_handle = curl_init($file);
                if ($curl_handle === false) {
                    return null;
                }
                $curl_handle = PMA_Util::configureCurl($curl_handle);
                curl_setopt(
                    $curl_handle,
                    CURLOPT_HEADER,
                    false
                );
                curl_setopt(
                    $curl_handle,
                    CURLOPT_RETURNTRANSFER,
                    true
                );
                curl_setopt(
                    $curl_handle,
                    CURLOPT_TIMEOUT,
                    $connection_timeout
                );
                if (! defined('TESTSUITE')) {
                    session_write_close();
                }
                $response = curl_exec($curl_handle);
            }
        }

        $data = json_decode($response);
        if (is_object($data)
            && ! empty($data->version)
            && ! empty($data->date)
            && $save
        ) {
            if (! isset($_SESSION) && ! defined('TESTSUITE')) {
                ini_set('session.use_only_cookies', 'false');
                ini_set('session.use_cookies', 'false');
                ini_set('session.use_trans_sid', 'false');
                ini_set('session.cache_limiter', 'nocache');
                session_start();
            }
            $_SESSION['cache']['version_check'] = array(
                'response' => $response,
                'timestamp' => time()
            );
        }
        return $data;
    }

    /**
     * Calculates numerical equivalent of phpMyAdmin version string
     *
     * @param string $version version
     *
     * @return mixed false on failure, integer on success
     */
    public function versionToInt($version)
    {
        $parts = explode('-', $version);
        if (count($parts) > 1) {
            $suffix = $parts[1];
        } else {
            $suffix = '';
        }
        $parts = explode('.', $parts[0]);

        $result = 0;

        if (count($parts) >= 1 && is_numeric($parts[0])) {
            $result += 1000000 * $parts[0];
        }

        if (count($parts) >= 2 && is_numeric($parts[1])) {
            $result += 10000 * $parts[1];
        }

        if (count($parts) >= 3 && is_numeric($parts[2])) {
            $result += 100 * $parts[2];
        }

        if (count($parts) >= 4 && is_numeric($parts[3])) {
            $result += 1 * $parts[3];
        }

        if (!empty($suffix)) {
            $matches = array();
            if (preg_match('/^(\D+)(\d+)$/', $suffix, $matches)) {
                $suffix = $matches[1];
                $result += intval($matches[2]);
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
                $result += 0;
                break;
            }
        } else {
            $result += 50; // for final
        }

        return $result;
    }

    /**
     * Returns the version and date of the latest phpMyAdmin version compatible
     * with avilable PHP and MySQL versions
     *
     * @param array $releases array of information related to each version
     *
     * @return array containing the version and date of latest compatibel version
     */
    public function getLatestCompatibleVersion($releases)
    {
        foreach ($releases as $release) {
            $phpVersions = $release->php_versions;
            $phpConditions = explode(",", $phpVersions);
            foreach ($phpConditions as $phpCondition) {
                if (! $this->evaluateVersionCondition("PHP", $phpCondition)) {
                    continue 2;
                }
            }

            // We evalute MySQL version constraint if there are only
            // one server configured.
            if (count($GLOBALS['cfg']['Servers']) == 1) {
                $mysqlVersions = $release->mysql_versions;
                $mysqlConditions = explode(",", $mysqlVersions);
                foreach ($mysqlConditions as $mysqlCondition) {
                    if (! $this->evaluateVersionCondition('MySQL', $mysqlCondition)) {
                        continue 2;
                    }
                }
            }

            return array(
                'version' => $release->version,
                'date' => $release->date,
            );
        }

        // no compatible version
        return null;
    }

    /**
     * Checks whether PHP or MySQL version meets supplied version condition
     *
     * @param string $type      PHP or MySQL
     * @param string $condition version condition
     *
     * @return boolean whether the condition is met
     */
    public function evaluateVersionCondition($type, $condition)
    {
        $operator = null;
        $operators = array("<=", ">=", "!=", "<>", "<", ">", "="); // preserve order
        foreach ($operators as $oneOperator) {
            if (strpos($condition, $oneOperator) === 0) {
                $operator = $oneOperator;
                $version = substr($condition, strlen($oneOperator));
                break;
            }
        }

        $myVersion = null;
        if ($type == 'PHP') {
            $myVersion = $this->getPHPVersion();
        } elseif ($type == 'MySQL') {
            $myVersion = $this->getMySQLVersion();
        }

        if ($myVersion != null && $operator != null) {
            return version_compare($myVersion, $version, $operator);
        }
        return false;
    }

    /**
     * Returns the PHP version
     *
     * @return string PHP version
     */
    protected function getPHPVersion()
    {
        return PHP_VERSION;
    }

    /**
     * Returns the MySQL version
     *
     * @return string MySQL version
     */
    protected function getMySQLVersion()
    {
        return PMA_Util::cacheGet('PMA_MYSQL_STR_VERSION');
    }
}
?>