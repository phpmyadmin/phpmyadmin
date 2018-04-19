<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Configuration handling.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use DirectoryIterator;
use PhpMyAdmin\Core;
use PhpMyAdmin\Error;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\HttpRequest;

/**
 * Indication for error handler (see end of this file).
 */
$GLOBALS['pma_config_loading'] = false;

/**
 * Configuration class
 *
 * @package PhpMyAdmin
 */
class Config
{
    /**
     * @var string  default config source
     */
    var $default_source = './libraries/config.default.php';

    /**
     * @var array   default configuration settings
     */
    var $default = array();

    /**
     * @var array   configuration settings, without user preferences applied
     */
    var $base_settings = array();

    /**
     * @var array   configuration settings
     */
    var $settings = array();

    /**
     * @var string  config source
     */
    var $source = '';

    /**
     * @var int     source modification time
     */
    var $source_mtime = 0;
    var $default_source_mtime = 0;
    var $set_mtime = 0;

    /**
     * @var boolean
     */
    var $error_config_file = false;

    /**
     * @var boolean
     */
    var $error_config_default_file = false;

    /**
     * @var array
     */
    var $default_server = array();

    /**
     * @var boolean whether init is done or not
     * set this to false to force some initial checks
     * like checking for required functions
     */
    var $done = false;

    /**
     * @var UserPreferences
     */
    private $userPreferences;

    /**
     * constructor
     *
     * @param string $source source to read config from
     */
    public function __construct($source = null)
    {
        $this->settings = array('is_setup' => false);

        // functions need to refresh in case of config file changed goes in
        // PhpMyAdmin\Config::load()
        $this->load($source);

        // other settings, independent from config file, comes in
        $this->checkSystem();

        $this->base_settings = $this->settings;

        $this->userPreferences = new UserPreferences();
    }

    /**
     * sets system and application settings
     *
     * @return void
     */
    public function checkSystem()
    {
        $this->set('PMA_VERSION', '4.8.0.1');
        /* Major version */
        $this->set(
            'PMA_MAJOR_VERSION',
            implode('.', array_slice(explode('.', $this->get('PMA_VERSION'), 3), 0, 2))
        );

        $this->checkWebServerOs();
        $this->checkWebServer();
        $this->checkGd2();
        $this->checkClient();
        $this->checkUpload();
        $this->checkUploadSize();
        $this->checkOutputCompression();
    }

    /**
     * whether to use gzip output compression or not
     *
     * @return void
     */
    public function checkOutputCompression()
    {
        // If zlib output compression is set in the php configuration file, no
        // output buffering should be run
        if (ini_get('zlib.output_compression')) {
            $this->set('OBGzip', false);
        }

        // enable output-buffering (if set to 'auto')
        if (strtolower($this->get('OBGzip')) == 'auto') {
            $this->set('OBGzip', true);
        }
    }

    /**
     * Sets the client platform based on user agent
     *
     * @param string $user_agent the user agent
     *
     * @return void
     */
    private function _setClientPlatform($user_agent)
    {
        if (mb_strstr($user_agent, 'Win')) {
            $this->set('PMA_USR_OS', 'Win');
        } elseif (mb_strstr($user_agent, 'Mac')) {
            $this->set('PMA_USR_OS', 'Mac');
        } elseif (mb_strstr($user_agent, 'Linux')) {
            $this->set('PMA_USR_OS', 'Linux');
        } elseif (mb_strstr($user_agent, 'Unix')) {
            $this->set('PMA_USR_OS', 'Unix');
        } elseif (mb_strstr($user_agent, 'OS/2')) {
            $this->set('PMA_USR_OS', 'OS/2');
        } else {
            $this->set('PMA_USR_OS', 'Other');
        }
    }

    /**
     * Determines platform (OS), browser and version of the user
     * Based on a phpBuilder article:
     *
     * @see http://www.phpbuilder.net/columns/tim20000821.php
     *
     * @return void
     */
    public function checkClient()
    {
        if (Core::getenv('HTTP_USER_AGENT')) {
            $HTTP_USER_AGENT = Core::getenv('HTTP_USER_AGENT');
        } else {
            $HTTP_USER_AGENT = '';
        }

        // 1. Platform
        $this->_setClientPlatform($HTTP_USER_AGENT);

        // 2. browser and version
        // (must check everything else before Mozilla)

        $is_mozilla = preg_match(
            '@Mozilla/([0-9]\.[0-9]{1,2})@',
            $HTTP_USER_AGENT,
            $mozilla_version
        );

        if (preg_match(
            '@Opera(/| )([0-9]\.[0-9]{1,2})@',
            $HTTP_USER_AGENT,
            $log_version
        )) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OPERA');
        } elseif (preg_match(
            '@(MS)?IE ([0-9]{1,2}\.[0-9]{1,2})@',
            $HTTP_USER_AGENT,
            $log_version
        )) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'IE');
        } elseif (preg_match(
            '@Trident/(7)\.0@',
            $HTTP_USER_AGENT,
            $log_version
        )) {
            $this->set('PMA_USR_BROWSER_VER', intval($log_version[1]) + 4);
            $this->set('PMA_USR_BROWSER_AGENT', 'IE');
        } elseif (preg_match(
            '@OmniWeb/([0-9]{1,3})@',
            $HTTP_USER_AGENT,
            $log_version
        )) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
            // Konqueror 2.2.2 says Konqueror/2.2.2
            // Konqueror 3.0.3 says Konqueror/3
        } elseif (preg_match(
            '@(Konqueror/)(.*)(;)@',
            $HTTP_USER_AGENT,
            $log_version
        )) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
            // must check Chrome before Safari
        } elseif ($is_mozilla
            && preg_match('@Chrome/([0-9.]*)@', $HTTP_USER_AGENT, $log_version)
        ) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'CHROME');
            // newer Safari
        } elseif ($is_mozilla
            && preg_match('@Version/(.*) Safari@', $HTTP_USER_AGENT, $log_version)
        ) {
            $this->set(
                'PMA_USR_BROWSER_VER', $log_version[1]
            );
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // older Safari
        } elseif ($is_mozilla
            && preg_match('@Safari/([0-9]*)@', $HTTP_USER_AGENT, $log_version)
        ) {
            $this->set(
                'PMA_USR_BROWSER_VER', $mozilla_version[1] . '.' . $log_version[1]
            );
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // Firefox
        } elseif (! mb_strstr($HTTP_USER_AGENT, 'compatible')
            && preg_match('@Firefox/([\w.]+)@', $HTTP_USER_AGENT, $log_version)
        ) {
            $this->set(
                'PMA_USR_BROWSER_VER', $log_version[1]
            );
            $this->set('PMA_USR_BROWSER_AGENT', 'FIREFOX');
        } elseif (preg_match('@rv:1\.9(.*)Gecko@', $HTTP_USER_AGENT)) {
            $this->set('PMA_USR_BROWSER_VER', '1.9');
            $this->set('PMA_USR_BROWSER_AGENT', 'GECKO');
        } elseif ($is_mozilla) {
            $this->set('PMA_USR_BROWSER_VER', $mozilla_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        } else {
            $this->set('PMA_USR_BROWSER_VER', 0);
            $this->set('PMA_USR_BROWSER_AGENT', 'OTHER');
        }
    }

    /**
     * Whether GD2 is present
     *
     * @return void
     */
    public function checkGd2()
    {
        if ($this->get('GD2Available') == 'yes') {
            $this->set('PMA_IS_GD2', 1);
            return;
        }

        if ($this->get('GD2Available') == 'no') {
            $this->set('PMA_IS_GD2', 0);
            return;
        }

        if (!function_exists('imagecreatetruecolor')) {
            $this->set('PMA_IS_GD2', 0);
            return;
        }

        if (function_exists('gd_info')) {
            $gd_nfo = gd_info();
            if (mb_strstr($gd_nfo["GD Version"], '2.')) {
                $this->set('PMA_IS_GD2', 1);
            } else {
                $this->set('PMA_IS_GD2', 0);
            }
        } else {
            $this->set('PMA_IS_GD2', 0);
        }
    }

    /**
     * Whether the Web server php is running on is IIS
     *
     * @return void
     */
    public function checkWebServer()
    {
        // some versions return Microsoft-IIS, some Microsoft/IIS
        // we could use a preg_match() but it's slower
        if (Core::getenv('SERVER_SOFTWARE')
            && stristr(Core::getenv('SERVER_SOFTWARE'), 'Microsoft')
            && stristr(Core::getenv('SERVER_SOFTWARE'), 'IIS')
        ) {
            $this->set('PMA_IS_IIS', 1);
        } else {
            $this->set('PMA_IS_IIS', 0);
        }
    }

    /**
     * Whether the os php is running on is windows or not
     *
     * @return void
     */
    public function checkWebServerOs()
    {
        // Default to Unix or Equiv
        $this->set('PMA_IS_WINDOWS', 0);
        // If PHP_OS is defined then continue
        if (defined('PHP_OS')) {
            if (stristr(PHP_OS, 'win') && !stristr(PHP_OS, 'darwin')) {
                // Is it some version of Windows
                $this->set('PMA_IS_WINDOWS', 1);
            } elseif (stristr(PHP_OS, 'OS/2')) {
                // Is it OS/2 (No file permissions like Windows)
                $this->set('PMA_IS_WINDOWS', 1);
            }
        }
    }

    /**
     * detects if Git revision
     *
     * @return boolean
     */
    public function isGitRevision()
    {
        if (!$this->get('ShowGitRevision')) {
            return false;
        }

        // caching
        if (isset($_SESSION['is_git_revision'])) {
            if ($_SESSION['is_git_revision']) {
                $this->set('PMA_VERSION_GIT', 1);
            }
            return $_SESSION['is_git_revision'];
        }
        // find out if there is a .git folder
        $git_folder = '.git';
        if (! @file_exists($git_folder)
            || ! @file_exists($git_folder . '/config')
        ) {
            $_SESSION['is_git_revision'] = false;
            return false;
        }
        $_SESSION['is_git_revision'] = true;
        return true;
    }

    /**
     * detects Git revision, if running inside repo
     *
     * @return void
     */
    public function checkGitRevision()
    {
        // find out if there is a .git folder
        $git_folder = '.git';
        if (! $this->isGitRevision()) {
            return;
        }

        if (! $ref_head = @file_get_contents($git_folder . '/HEAD')) {
            return;
        }

        $branch = false;
        // are we on any branch?
        if (strstr($ref_head, '/')) {
            // remove ref: prefix
            $ref_head = substr(trim($ref_head), 5);
            if (substr($ref_head, 0, 11) === 'refs/heads/') {
                $branch = substr($ref_head, 11);
            } else {
                $branch = basename($ref_head);
            }

            $ref_file = $git_folder . '/' . $ref_head;
            if (@file_exists($ref_file)) {
                $hash = @file_get_contents($ref_file);
                if (! $hash) {
                    return;
                }
                $hash = trim($hash);
            } else {
                // deal with packed refs
                $packed_refs = @file_get_contents($git_folder . '/packed-refs');
                if (! $packed_refs) {
                    return;
                }
                // split file to lines
                $ref_lines = explode("\n", $packed_refs);
                foreach ($ref_lines as $line) {
                    // skip comments
                    if ($line[0] == '#') {
                        continue;
                    }
                    // parse line
                    $parts = explode(' ', $line);
                    // care only about named refs
                    if (count($parts) != 2) {
                        continue;
                    }
                    // have found our ref?
                    if ($parts[1] == $ref_head) {
                        $hash = $parts[0];
                        break;
                    }
                }
                if (! isset($hash)) {
                    // Could not find ref
                    return;
                }
            }
        } else {
            $hash = trim($ref_head);
        }

        $commit = false;
        if (! preg_match('/^[0-9a-f]{40}$/i', $hash)) {
            $commit = false;
        } elseif (isset($_SESSION['PMA_VERSION_COMMITDATA_' . $hash])) {
            $commit = $_SESSION['PMA_VERSION_COMMITDATA_' . $hash];
        } elseif (function_exists('gzuncompress')) {
            $git_file_name = $git_folder . '/objects/'
                . substr($hash, 0, 2) . '/' . substr($hash, 2);
            if (@file_exists($git_file_name) ) {
                if (! $commit = @file_get_contents($git_file_name)) {
                    return;
                }
                $commit = explode("\0", gzuncompress($commit), 2);
                $commit = explode("\n", $commit[1]);
                $_SESSION['PMA_VERSION_COMMITDATA_' . $hash] = $commit;
            } else {
                $pack_names = array();
                // work with packed data
                $packs_file = $git_folder . '/objects/info/packs';
                if (@file_exists($packs_file)
                    && $packs = @file_get_contents($packs_file)
                ) {
                    // File exists. Read it, parse the file to get the names of the
                    // packs. (to look for them in .git/object/pack directory later)
                    foreach (explode("\n", $packs) as $line) {
                        // skip blank lines
                        if (strlen(trim($line)) == 0) {
                            continue;
                        }
                        // skip non pack lines
                        if ($line[0] != 'P') {
                            continue;
                        }
                        // parse names
                        $pack_names[] = substr($line, 2);
                    }
                } else {
                    // '.git/objects/info/packs' file can be missing
                    // (atlease in mysGit)
                    // File missing. May be we can look in the .git/object/pack
                    // directory for all the .pack files and use that list of
                    // files instead
                    $dirIterator = new DirectoryIterator(
                        $git_folder . '/objects/pack'
                    );
                    foreach ($dirIterator as $file_info) {
                        $file_name = $file_info->getFilename();
                        // if this is a .pack file
                        if ($file_info->isFile() && substr($file_name, -5) == '.pack'
                        ) {
                            $pack_names[] = $file_name;
                        }
                    }
                }
                $hash = strtolower($hash);
                foreach ($pack_names as $pack_name) {
                    $index_name = str_replace('.pack', '.idx', $pack_name);

                    // load index
                    $index_data = @file_get_contents(
                        $git_folder . '/objects/pack/' . $index_name
                    );
                    if (! $index_data) {
                        continue;
                    }
                    // check format
                    if (substr($index_data, 0, 4) != "\377tOc") {
                        continue;
                    }
                    // check version
                    $version = unpack('N', substr($index_data, 4, 4));
                    if ($version[1] != 2) {
                        continue;
                    }
                    // parse fanout table
                    $fanout = unpack(
                        "N*",
                        substr($index_data, 8, 256 * 4)
                    );

                    // find where we should search
                    $firstbyte = intval(substr($hash, 0, 2), 16);
                    // array is indexed from 1 and we need to get
                    // previous entry for start
                    if ($firstbyte == 0) {
                        $start = 0;
                    } else {
                        $start = $fanout[$firstbyte];
                    }
                    $end = $fanout[$firstbyte + 1];

                    // stupid linear search for our sha
                    $found = false;
                    $offset = 8 + (256 * 4);
                    for ($position = $start; $position < $end; $position++) {
                        $sha = strtolower(
                            bin2hex(
                                substr($index_data, $offset + ($position * 20), 20)
                            )
                        );
                        if ($sha == $hash) {
                            $found = true;
                            break;
                        }
                    }
                    if (! $found) {
                        continue;
                    }
                    // read pack offset
                    $offset = 8 + (256 * 4) + (24 * $fanout[256]);
                    $pack_offset = unpack(
                        'N',
                        substr($index_data, $offset + ($position * 4), 4)
                    );
                    $pack_offset = $pack_offset[1];

                    // open pack file
                    $pack_file = fopen(
                        $git_folder . '/objects/pack/' . $pack_name, 'rb'
                    );
                    if ($pack_file === false) {
                        continue;
                    }
                    // seek to start
                    fseek($pack_file, $pack_offset);

                    // parse header
                    $header = ord(fread($pack_file, 1));
                    $type = ($header >> 4) & 7;
                    $hasnext = ($header & 128) >> 7;
                    $size = $header & 0xf;
                    $offset = 4;

                    while ($hasnext) {
                        $byte = ord(fread($pack_file, 1));
                        $size |= ($byte & 0x7f) << $offset;
                        $hasnext = ($byte & 128) >> 7;
                        $offset += 7;
                    }

                    // we care only about commit objects
                    if ($type != 1) {
                        continue;
                    }

                    // read data
                    $commit = fread($pack_file, $size);
                    $commit = gzuncompress($commit);
                    $commit = explode("\n", $commit);
                    $_SESSION['PMA_VERSION_COMMITDATA_' . $hash] = $commit;
                    fclose($pack_file);
                }
            }
        }

        $httpRequest = new HttpRequest();

        // check if commit exists in Github
        if ($commit !== false
            && isset($_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash])
        ) {
            $is_remote_commit = $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash];
        } else {
            $link = 'https://www.phpmyadmin.net/api/commit/' . $hash . '/';
            $is_found = $httpRequest->create($link, 'GET');
            switch($is_found) {
            case false:
                $is_remote_commit = false;
                $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash] = false;
                break;
            case null:
                // no remote link for now, but don't cache this as Github is down
                $is_remote_commit = false;
                break;
            default:
                $is_remote_commit = true;
                $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash] = true;
                if ($commit === false) {
                    // if no local commit data, try loading from Github
                    $commit_json = json_decode($is_found);
                }
                break;
            }
        }

        $is_remote_branch = false;
        if ($is_remote_commit && $branch !== false) {
            // check if branch exists in Github
            if (isset($_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash])) {
                $is_remote_branch = $_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash];
            } else {
                $link = 'https://www.phpmyadmin.net/api/tree/' . $branch . '/';
                $is_found = $httpRequest->create($link, 'GET', true);
                switch($is_found) {
                case true:
                    $is_remote_branch = true;
                    $_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash] = true;
                    break;
                case false:
                    $is_remote_branch = false;
                    $_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash] = false;
                    break;
                case null:
                    // no remote link for now, but don't cache this as Github is down
                    $is_remote_branch = false;
                    break;
                }
            }
        }

        if ($commit !== false) {
            $author = array('name' => '', 'email' => '', 'date' => '');
            $committer = array('name' => '', 'email' => '', 'date' => '');

            do {
                $dataline = array_shift($commit);
                $datalinearr = explode(' ', $dataline, 2);
                $linetype = $datalinearr[0];
                if (in_array($linetype, array('author', 'committer'))) {
                    $user = $datalinearr[1];
                    preg_match('/([^<]+)<([^>]+)> ([0-9]+)( [^ ]+)?/', $user, $user);
                    $user2 = array(
                        'name' => trim($user[1]),
                        'email' => trim($user[2]),
                        'date' => date('Y-m-d H:i:s', $user[3]));
                    if (isset($user[4])) {
                        $user2['date'] .= $user[4];
                    }
                    $$linetype = $user2;
                }
            } while ($dataline != '');
            $message = trim(implode(' ', $commit));

        } elseif (isset($commit_json) && isset($commit_json->author) && isset($commit_json->committer)) {
            $author = array(
                'name' => $commit_json->author->name,
                'email' => $commit_json->author->email,
                'date' => $commit_json->author->date);
            $committer = array(
                'name' => $commit_json->committer->name,
                'email' => $commit_json->committer->email,
                'date' => $commit_json->committer->date);
            $message = trim($commit_json->message);
        } else {
            return;
        }

        $this->set('PMA_VERSION_GIT', 1);
        $this->set('PMA_VERSION_GIT_COMMITHASH', $hash);
        $this->set('PMA_VERSION_GIT_BRANCH', $branch);
        $this->set('PMA_VERSION_GIT_MESSAGE', $message);
        $this->set('PMA_VERSION_GIT_AUTHOR', $author);
        $this->set('PMA_VERSION_GIT_COMMITTER', $committer);
        $this->set('PMA_VERSION_GIT_ISREMOTECOMMIT', $is_remote_commit);
        $this->set('PMA_VERSION_GIT_ISREMOTEBRANCH', $is_remote_branch);
    }

    /**
     * loads default values from default source
     *
     * @return boolean     success
     */
    public function loadDefaults()
    {
        $cfg = array();
        if (! @file_exists($this->default_source)) {
            $this->error_config_default_file = true;
            return false;
        }
        $old_error_reporting = error_reporting(0);
        ob_start();
        $GLOBALS['pma_config_loading'] = true;
        $eval_result = include $this->default_source;
        $GLOBALS['pma_config_loading'] = false;
        ob_end_clean();
        error_reporting($old_error_reporting);

        if ($eval_result === false) {
            $this->error_config_default_file = true;
            return false;
        }

        $this->default_source_mtime = filemtime($this->default_source);

        $this->default_server = $cfg['Servers'][1];
        unset($cfg['Servers']);

        $this->default = $cfg;
        $this->settings = array_replace_recursive($this->settings, $cfg);

        $this->error_config_default_file = false;

        return true;
    }

    /**
     * loads configuration from $source, usually the config file
     * should be called on object creation
     *
     * @param string $source config file
     *
     * @return bool
     */
    public function load($source = null)
    {
        $this->loadDefaults();

        if (null !== $source) {
            $this->setSource($source);
        }

        if (! $this->checkConfigSource()) {
            return false;
        }

        $cfg = array();

        /**
         * Parses the configuration file, we throw away any errors or
         * output.
         */
        $old_error_reporting = error_reporting(0);
        ob_start();
        $GLOBALS['pma_config_loading'] = true;
        $eval_result = include $this->getSource();
        $GLOBALS['pma_config_loading'] = false;
        ob_end_clean();
        error_reporting($old_error_reporting);

        if ($eval_result === false) {
            $this->error_config_file = true;
        } else {
            $this->error_config_file = false;
            $this->source_mtime = filemtime($this->getSource());
        }

        /**
         * Ignore keys with / as we do not use these
         *
         * These can be confusing for user configuration layer as it
         * flatten array using / and thus don't see difference between
         * $cfg['Export/method'] and $cfg['Export']['method'], while rest
         * of thre code uses the setting only in latter form.
         *
         * This could be removed once we consistently handle both values
         * in the functional code as well.
         *
         * It could use array_filter(...ARRAY_FILTER_USE_KEY), but it's not
         * supported on PHP 5.5 and HHVM.
         */
        $matched_keys = array_filter(
            array_keys($cfg),
            function ($key) {return strpos($key, '/') === false;}
        );

        $cfg = array_intersect_key($cfg, array_flip($matched_keys));

        /**
         * Backward compatibility code
         */
        if (!empty($cfg['DefaultTabTable'])) {
            $cfg['DefaultTabTable'] = str_replace(
                '_properties',
                '',
                str_replace(
                    'tbl_properties.php',
                    'tbl_sql.php',
                    $cfg['DefaultTabTable']
                )
            );
        }
        if (!empty($cfg['DefaultTabDatabase'])) {
            $cfg['DefaultTabDatabase'] = str_replace(
                '_details',
                '',
                str_replace(
                    'db_details.php',
                    'db_sql.php',
                    $cfg['DefaultTabDatabase']
                )
            );
        }

        $this->settings = array_replace_recursive($this->settings, $cfg);

        return true;
    }

    /**
     * Sets the connection collation
     *
     * @return void
     */
    private function _setConnectionCollation()
    {
        $collation_connection = $this->get('DefaultConnectionCollation');
        if (! empty($collation_connection)
            && $collation_connection != $GLOBALS['collation_connection']
        ) {
            $GLOBALS['dbi']->setCollation($collation_connection);
        }
    }

    /**
     * Loads user preferences and merges them with current config
     * must be called after control connection has been established
     *
     * @return void
     */
    public function loadUserPreferences()
    {
        // index.php should load these settings, so that phpmyadmin.css.php
        // will have everything available in session cache
        $server = isset($GLOBALS['server'])
            ? $GLOBALS['server']
            : (!empty($GLOBALS['cfg']['ServerDefault'])
                ? $GLOBALS['cfg']['ServerDefault']
                : 0);
        $cache_key = 'server_' . $server;
        if ($server > 0 && !defined('PMA_MINIMUM_COMMON')) {
            $config_mtime = max($this->default_source_mtime, $this->source_mtime);
            // cache user preferences, use database only when needed
            if (! isset($_SESSION['cache'][$cache_key]['userprefs'])
                || $_SESSION['cache'][$cache_key]['config_mtime'] < $config_mtime
            ) {
                $prefs = $this->userPreferences->load();
                $_SESSION['cache'][$cache_key]['userprefs']
                    = $this->userPreferences->apply($prefs['config_data']);
                $_SESSION['cache'][$cache_key]['userprefs_mtime'] = $prefs['mtime'];
                $_SESSION['cache'][$cache_key]['userprefs_type'] = $prefs['type'];
                $_SESSION['cache'][$cache_key]['config_mtime'] = $config_mtime;
            }
        } elseif ($server == 0
            || ! isset($_SESSION['cache'][$cache_key]['userprefs'])
        ) {
            $this->set('user_preferences', false);
            return;
        }
        $config_data = $_SESSION['cache'][$cache_key]['userprefs'];
        // type is 'db' or 'session'
        $this->set(
            'user_preferences',
            $_SESSION['cache'][$cache_key]['userprefs_type']
        );
        $this->set(
            'user_preferences_mtime',
            $_SESSION['cache'][$cache_key]['userprefs_mtime']
        );

        // load config array
        $this->settings = array_replace_recursive($this->settings, $config_data);
        $GLOBALS['cfg'] = array_replace_recursive($GLOBALS['cfg'], $config_data);
        if (defined('PMA_MINIMUM_COMMON')) {
            return;
        }

        // settings below start really working on next page load, but
        // changes are made only in index.php so everything is set when
        // in frames

        // save theme
        /** @var ThemeManager $tmanager */
        $tmanager = ThemeManager::getInstance();
        if ($tmanager->getThemeCookie() || isset($_REQUEST['set_theme'])) {
            if ((! isset($config_data['ThemeDefault'])
                && $tmanager->theme->getId() != 'original')
                || isset($config_data['ThemeDefault'])
                && $config_data['ThemeDefault'] != $tmanager->theme->getId()
            ) {
                // new theme was set in common.inc.php
                $this->setUserValue(
                    null,
                    'ThemeDefault',
                    $tmanager->theme->getId(),
                    'original'
                );
            }
        } else {
            // no cookie - read default from settings
            if ($this->settings['ThemeDefault'] != $tmanager->theme->getId()
                && $tmanager->checkTheme($this->settings['ThemeDefault'])
            ) {
                $tmanager->setActiveTheme($this->settings['ThemeDefault']);
                $tmanager->setThemeCookie();
            }
        }

        // save language
        if (isset($_COOKIE['pma_lang']) || isset($_POST['lang'])) {
            if ((! isset($config_data['lang'])
                && $GLOBALS['lang'] != 'en')
                || isset($config_data['lang'])
                && $GLOBALS['lang'] != $config_data['lang']
            ) {
                $this->setUserValue(null, 'lang', $GLOBALS['lang'], 'en');
            }
        } else {
            // read language from settings
            if (isset($config_data['lang'])) {
                $language = LanguageManager::getInstance()->getLanguage(
                    $config_data['lang']
                );
                if ($language !== false) {
                    $language->activate();
                    $this->setCookie('pma_lang', $language->getCode());
                }
            }
        }

        // set connection collation
        $this->_setConnectionCollation();
    }

    /**
     * Sets config value which is stored in user preferences (if available)
     * or in a cookie.
     *
     * If user preferences are not yet initialized, option is applied to
     * global config and added to a update queue, which is processed
     * by {@link loadUserPreferences()}
     *
     * @param string $cookie_name   can be null
     * @param string $cfg_path      configuration path
     * @param mixed  $new_cfg_value new value
     * @param mixed  $default_value default value
     *
     * @return true|PhpMyAdmin\Message
     */
    public function setUserValue($cookie_name, $cfg_path, $new_cfg_value,
        $default_value = null
    ) {
        $result = true;
        // use permanent user preferences if possible
        $prefs_type = $this->get('user_preferences');
        if ($prefs_type) {
            if ($default_value === null) {
                $default_value = Core::arrayRead($cfg_path, $this->default);
            }
            $result = $this->userPreferences->persistOption($cfg_path, $new_cfg_value, $default_value);
        }
        if ($prefs_type != 'db' && $cookie_name) {
            // fall back to cookies
            if ($default_value === null) {
                $default_value = Core::arrayRead($cfg_path, $this->settings);
            }
            $this->setCookie($cookie_name, $new_cfg_value, $default_value);
        }
        Core::arrayWrite($cfg_path, $GLOBALS['cfg'], $new_cfg_value);
        Core::arrayWrite($cfg_path, $this->settings, $new_cfg_value);
        return $result;
    }

    /**
     * Reads value stored by {@link setUserValue()}
     *
     * @param string $cookie_name cookie name
     * @param mixed  $cfg_value   config value
     *
     * @return mixed
     */
    public function getUserValue($cookie_name, $cfg_value)
    {
        $cookie_exists = isset($_COOKIE) && !empty($_COOKIE[$cookie_name]);
        $prefs_type = $this->get('user_preferences');
        if ($prefs_type == 'db') {
            // permanent user preferences value exists, remove cookie
            if ($cookie_exists) {
                $this->removeCookie($cookie_name);
            }
        } elseif ($cookie_exists) {
            return $_COOKIE[$cookie_name];
        }
        // return value from $cfg array
        return $cfg_value;
    }

    /**
     * set source
     *
     * @param string $source source
     *
     * @return void
     */
    public function setSource($source)
    {
        $this->source = trim($source);
    }

    /**
     * check config source
     *
     * @return boolean whether source is valid or not
     */
    public function checkConfigSource()
    {
        if (! $this->getSource()) {
            // no configuration file set at all
            return false;
        }

        if (! @file_exists($this->getSource())) {
            $this->source_mtime = 0;
            return false;
        }

        if (! @is_readable($this->getSource())) {
            // manually check if file is readable
            // might be bug #3059806 Supporting running from CIFS/Samba shares

            $contents = false;
            $handle = @fopen($this->getSource(), 'r');
            if ($handle !== false) {
                $contents = @fread($handle, 1); // reading 1 byte is enough to test
                fclose($handle);
            }
            if ($contents === false) {
                $this->source_mtime = 0;
                Core::fatalError(
                    sprintf(
                        function_exists('__')
                        ? __('Existing configuration file (%s) is not readable.')
                        : 'Existing configuration file (%s) is not readable.',
                        $this->getSource()
                    )
                );
                return false;
            }
        }

        return true;
    }

    /**
     * verifies the permissions on config file (if asked by configuration)
     * (must be called after config.inc.php has been merged)
     *
     * @return void
     */
    public function checkPermissions()
    {
        // Check for permissions (on platforms that support it):
        if ($this->get('CheckConfigurationPermissions') && @file_exists($this->getSource())) {
            $perms = @fileperms($this->getSource());
            if (!($perms === false) && ($perms & 2)) {
                // This check is normally done after loading configuration
                $this->checkWebServerOs();
                if ($this->get('PMA_IS_WINDOWS') == 0) {
                    $this->source_mtime = 0;
                    Core::fatalError(
                        __(
                            'Wrong permissions on configuration file, '
                            . 'should not be world writable!'
                        )
                    );
                }
            }
        }
    }

    /**
     * Checks for errors
     * (must be called after config.inc.php has been merged)
     *
     * @return void
     */
    public function checkErrors()
    {
        if ($this->error_config_default_file) {
            Core::fatalError(
                sprintf(
                    __('Could not load default configuration from: %1$s'),
                    $this->default_source
                )
            );
        }

        if ($this->error_config_file) {
            $error = '[strong]' . __('Failed to read configuration file!') . '[/strong]'
                . '[br][br]'
                . __(
                    'This usually means there is a syntax error in it, '
                    . 'please check any errors shown below.'
                )
                . '[br][br]'
                . '[conferr]';
            trigger_error($error, E_USER_ERROR);
        }
    }

    /**
     * returns specific config setting
     *
     * @param string $setting config setting
     *
     * @return mixed value
     */
    public function get($setting)
    {
        if (isset($this->settings[$setting])) {
            return $this->settings[$setting];
        }
        return null;
    }

    /**
     * sets configuration variable
     *
     * @param string $setting configuration option
     * @param mixed  $value   new value for configuration option
     *
     * @return void
     */
    public function set($setting, $value)
    {
        if (! isset($this->settings[$setting])
            || $this->settings[$setting] !== $value
        ) {
            $this->settings[$setting] = $value;
            $this->set_mtime = time();
        }
    }

    /**
     * returns source for current config
     *
     * @return string  config source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * returns a unique value to force a CSS reload if either the config
     * or the theme changes
     *
     * @return int Summary of unix timestamps and fontsize,
     * to be unique on theme parameters change
     */
    public function getThemeUniqueValue()
    {
        if (null !== $this->get('FontSize')) {
            $fontsize = intval($this->get('FontSize'));
        } else {
            $fontsize = 0;
        }
        return (
            $fontsize +
            $this->source_mtime +
            $this->default_source_mtime +
            $this->get('user_preferences_mtime') +
            $GLOBALS['PMA_Theme']->mtime_info +
            $GLOBALS['PMA_Theme']->filesize_info);
    }

    /**
     * checks if upload is enabled
     *
     * @return void
     */
    public function checkUpload()
    {
        if (!ini_get('file_uploads')) {
            $this->set('enable_upload', false);
            return;
        }

        $this->set('enable_upload', true);
        // if set "php_admin_value file_uploads Off" in httpd.conf
        // ini_get() also returns the string "Off" in this case:
        if ('off' == strtolower(ini_get('file_uploads'))) {
            $this->set('enable_upload', false);
        }
    }

    /**
     * Maximum upload size as limited by PHP
     * Used with permission from Moodle (https://moodle.org/) by Martin Dougiamas
     *
     * this section generates $max_upload_size in bytes
     *
     * @return void
     */
    public function checkUploadSize()
    {
        if (! $filesize = ini_get('upload_max_filesize')) {
            $filesize = "5M";
        }

        if ($postsize = ini_get('post_max_size')) {
            $this->set(
                'max_upload_size',
                min(Core::getRealSize($filesize), Core::getRealSize($postsize))
            );
        } else {
            $this->set('max_upload_size', Core::getRealSize($filesize));
        }
    }

    /**
     * Checks if protocol is https
     *
     * This function checks if the https protocol on the active connection.
     *
     * @return bool
     */
    public function isHttps()
    {

        if (null !== $this->get('is_https')) {
            return $this->get('is_https');
        }

        $url = $this->get('PmaAbsoluteUri');

        $is_https = false;
        if (! empty($url) && parse_url($url, PHP_URL_SCHEME) === 'https') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_SCHEME')) == 'https') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTPS')) == 'on') {
            $is_https = true;
        } elseif (substr(strtolower(Core::getenv('REQUEST_URI')), 0, 6) == 'https:') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_HTTPS_FROM_LB')) == 'on') {
            // A10 Networks load balancer
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_FRONT_END_HTTPS')) == 'on') {
            $is_https = true;
        } elseif (strtolower(Core::getenv('HTTP_X_FORWARDED_PROTO')) == 'https') {
            $is_https = true;
        } elseif (Core::getenv('SERVER_PORT') == 443) {
            $is_https = true;
        }

        $this->set('is_https', $is_https);

        return $is_https;
    }

    /**
     * Get phpMyAdmin root path
     *
     * @return string
     */
    public function getRootPath()
    {
        static $cookie_path = null;

        if (null !== $cookie_path && !defined('TESTSUITE')) {
            return $cookie_path;
        }

        $url = $this->get('PmaAbsoluteUri');

        if (! empty($url)) {
            $path = parse_url($url, PHP_URL_PATH);
            if (! empty($path)) {
                if (substr($path, -1) != '/') {
                    return $path . '/';
                }
                return $path;
            }
        }

        $parsed_url = parse_url($GLOBALS['PMA_PHP_SELF']);

        $parts = explode(
            '/',
            rtrim(str_replace('\\', '/', $parsed_url['path']), '/')
        );

        /* Remove filename */
        if (substr($parts[count($parts) - 1], -4) == '.php') {
            $parts = array_slice($parts, 0, count($parts) - 1);
        }

        /* Remove extra path from javascript calls */
        if (defined('PMA_PATH_TO_BASEDIR')) {
            $parts = array_slice($parts, 0, count($parts) - 1);
        }

        $parts[] = '';

        return implode('/', $parts);
    }

    /**
     * enables backward compatibility
     *
     * @return void
     */
    public function enableBc()
    {
        $GLOBALS['cfg']             = $this->settings;
        $GLOBALS['default_server']  = $this->default_server;
        unset($this->default_server);
        $GLOBALS['is_upload']       = $this->get('enable_upload');
        $GLOBALS['max_upload_size'] = $this->get('max_upload_size');
        $GLOBALS['is_https']        = $this->get('is_https');

        $defines = array(
            'PMA_VERSION',
            'PMA_MAJOR_VERSION',
            'PMA_THEME_VERSION',
            'PMA_THEME_GENERATION',
            'PMA_IS_WINDOWS',
            'PMA_IS_GD2',
            'PMA_USR_OS',
            'PMA_USR_BROWSER_VER',
            'PMA_USR_BROWSER_AGENT'
            );

        foreach ($defines as $define) {
            if (! defined($define)) {
                define($define, $this->get($define));
            }
        }
    }

    /**
     * returns options for font size selection
     *
     * @param string $current_size current selected font size with unit
     *
     * @return array selectable font sizes
     */
    protected static function getFontsizeOptions($current_size = '82%')
    {
        $unit = preg_replace('/[0-9.]*/', '', $current_size);
        $value = preg_replace('/[^0-9.]*/', '', $current_size);

        $factors = array();
        $options = array();
        $options["$value"] = $value . $unit;

        if ($unit === '%') {
            $factors[] = 1;
            $factors[] = 5;
            $factors[] = 10;
            $options['100'] = '100%';
        } elseif ($unit === 'em') {
            $factors[] = 0.05;
            $factors[] = 0.2;
            $factors[] = 1;
        } elseif ($unit === 'pt') {
            $factors[] = 0.5;
            $factors[] = 2;
        } elseif ($unit === 'px') {
            $factors[] = 1;
            $factors[] = 5;
            $factors[] = 10;
        } else {
            //unknown font size unit
            $factors[] = 0.05;
            $factors[] = 0.2;
            $factors[] = 1;
            $factors[] = 5;
            $factors[] = 10;
        }

        foreach ($factors as $key => $factor) {
            $option_inc = $value + $factor;
            $option_dec = $value - $factor;
            while (count($options) < 21) {
                $options["$option_inc"] = $option_inc . $unit;
                if ($option_dec > $factors[0]) {
                    $options["$option_dec"] = $option_dec . $unit;
                }
                $option_inc += $factor;
                $option_dec -= $factor;
                if (isset($factors[$key + 1])
                    && $option_inc >= $value + $factors[$key + 1]
                ) {
                    break;
                }
            }
        }
        ksort($options);
        return $options;
    }

    /**
     * returns html selectbox for font sizes
     *
     * @return string html selectbox
     */
    protected static function getFontsizeSelection()
    {
        $current_size = $GLOBALS['PMA_Config']->get('FontSize');
        // for the case when there is no config file (this is supported)
        if (empty($current_size)) {
            $current_size = '82%';
        }
        $options = Config::getFontsizeOptions($current_size);

        $return = '<label for="select_fontsize">' . __('Font size')
            . ':</label>' . "\n"
            . '<select name="set_fontsize" id="select_fontsize"'
            . ' class="autosubmit">' . "\n";
        foreach ($options as $option) {
            $return .= '<option value="' . $option . '"';
            if ($option == $current_size) {
                $return .= ' selected="selected"';
            }
            $return .= '>' . $option . '</option>' . "\n";
        }
        $return .= '</select>';

        return $return;
    }

    /**
     * return complete font size selection form
     *
     * @return string html selectbox
     */
    public static function getFontsizeForm()
    {
        return '<form name="form_fontsize_selection" id="form_fontsize_selection"'
            . ' method="post" action="index.php" class="disableAjax">' . "\n"
            . Url::getHiddenInputs() . "\n"
            . Config::getFontsizeSelection() . "\n"
            . '</form>';
    }

    /**
     * removes cookie
     *
     * @param string $cookie name of cookie to remove
     *
     * @return boolean result of setcookie()
     */
    public function removeCookie($cookie)
    {
        if (defined('TESTSUITE')) {
            if (isset($_COOKIE[$cookie])) {
                unset($_COOKIE[$cookie]);
            }
            return true;
        }
        return setcookie(
            $cookie,
            '',
            time() - 3600,
            $this->getRootPath(),
            '',
            $this->isHttps()
        );
    }

    /**
     * sets cookie if value is different from current cookie value,
     * or removes if value is equal to default
     *
     * @param string $cookie   name of cookie to remove
     * @param mixed  $value    new cookie value
     * @param string $default  default value
     * @param int    $validity validity of cookie in seconds (default is one month)
     * @param bool   $httponly whether cookie is only for HTTP (and not for scripts)
     *
     * @return boolean result of setcookie()
     */
    public function setCookie($cookie, $value, $default = null,
        $validity = null, $httponly = true
    ) {
        if (strlen($value) > 0 && null !== $default && $value === $default
        ) {
            // default value is used
            if (isset($_COOKIE[$cookie])) {
                // remove cookie
                return $this->removeCookie($cookie);
            }
            return false;
        }

        if (strlen($value) === 0 && isset($_COOKIE[$cookie])) {
            // remove cookie, value is empty
            return $this->removeCookie($cookie);
        }

        if (! isset($_COOKIE[$cookie]) || $_COOKIE[$cookie] !== $value) {
            // set cookie with new value
            /* Calculate cookie validity */
            if ($validity === null) {
                /* Valid for one month */
                $validity = time() + 2592000;
            } elseif ($validity == 0) {
                /* Valid for session */
                $validity = 0;
            } else {
                $validity = time() + $validity;
            }
            if (defined('TESTSUITE')) {
                $_COOKIE[$cookie] = $value;
                return true;
            }
            return setcookie(
                $cookie,
                $value,
                $validity,
                $this->getRootPath(),
                '',
                $this->isHttps(),
                $httponly
            );
        }

        // cookie has already $value as value
        return true;
    }


    /**
     * Error handler to catch fatal errors when loading configuration
     * file
     *
     *
     * PMA_Config_fatalErrorHandler
     * @return void
     */
    public static function fatalErrorHandler()
    {
        if (!isset($GLOBALS['pma_config_loading'])
            || !$GLOBALS['pma_config_loading']
        ) {
            return;
        }

        $error = error_get_last();
        if ($error === null) {
            return;
        }

        Core::fatalError(
            sprintf(
                'Failed to load phpMyAdmin configuration (%s:%s): %s',
                Error::relPath($error['file']),
                $error['line'],
                $error['message']
            )
        );
    }

    /**
     * Wrapper for footer/header rendering
     *
     * @param string $filename File to check and render
     * @param string $id       Div ID
     *
     * @return string
     */
    private static function _renderCustom($filename, $id)
    {
        $retval = '';
        if (@file_exists($filename)) {
            $retval .= '<div id="' . $id . '">';
            ob_start();
            include $filename;
            $retval .= ob_get_contents();
            ob_end_clean();
            $retval .= '</div>';
        }
        return $retval;
    }

    /**
     * Renders user configured footer
     *
     * @return string
     */
    public static function renderFooter()
    {
        return self::_renderCustom(CUSTOM_FOOTER_FILE, 'pma_footer');
    }

    /**
     * Renders user configured footer
     *
     * @return string
     */
    public static function renderHeader()
    {
        return self::_renderCustom(CUSTOM_HEADER_FILE, 'pma_header');
    }

    /**
     * Returns temporary dir path
     *
     * @param string $name Directory name
     *
     * @return string|null
     */
    public function getTempDir($name)
    {
        static $temp_dir = array();

        if (isset($temp_dir[$name]) && !defined('TESTSUITE')) {
            return $temp_dir[$name];
        }

        $path = $this->get('TempDir');
        if (empty($path)) {
            $path = null;
        } else {
            $path .= '/' . $name;
            if (! @is_dir($path)) {
                @mkdir($path, 0770, true);
            }
            if (! @is_dir($path) || ! @is_writable($path)) {
                $path = null;
            }
        }

        $temp_dir[$name] = $path;
        return $path;
    }

    /**
     * Returns temporary directory
     *
     * @return string
     */
    public function getUploadTempDir()
    {
        // First try configured temp dir
        // Fallback to PHP upload_tmp_dir
        $dirs = array(
            $this->getTempDir('upload'),
            ini_get('upload_tmp_dir'),
            sys_get_temp_dir(),
        );

        foreach ($dirs as $dir) {
            if (! empty($dir) && @is_writable($dir)) {
                return realpath($dir);
            }
        }

        return null;
    }

    /**
     * Selects server based on request parameters.
     *
     * @return integer
     */
    public function selectServer() {
        $server = 0;
        $request = empty($_REQUEST['server']) ? 0 : $_REQUEST['server'];

        /**
         * Lookup server by name
         * (see FAQ 4.8)
         */
        if (! is_numeric($request)) {
            foreach ($this->settings['Servers'] as $i => $server) {
                $verboseToLower = mb_strtolower($server['verbose']);
                $serverToLower = mb_strtolower($request);
                if ($server['host'] == $request
                    || $server['verbose'] == $request
                    || $verboseToLower == $serverToLower
                    || md5($verboseToLower) === $serverToLower
                ) {
                    $request = $i;
                    break;
                }
            }
            if (is_string($request)) {
                $request = 0;
            }
        }

        /**
         * If no server is selected, make sure that $this->settings['Server'] is empty (so
         * that nothing will work), and skip server authentication.
         * We do NOT exit here, but continue on without logging into any server.
         * This way, the welcome page will still come up (with no server info) and
         * present a choice of servers in the case that there are multiple servers
         * and '$this->settings['ServerDefault'] = 0' is set.
         */

        if (is_numeric($request) && ! empty($request) && ! empty($this->settings['Servers'][$request])) {
            $server = $request;
            $this->settings['Server'] = $this->settings['Servers'][$server];
        } else {
            if (!empty($this->settings['Servers'][$this->settings['ServerDefault']])) {
                $server = $this->settings['ServerDefault'];
                $this->settings['Server'] = $this->settings['Servers'][$server];
            } else {
                $server = 0;
                $this->settings['Server'] = array();
            }
        }

        return $server;
    }

    /**
     * Checks whether Servers configuration is valid and possibly apply fixups.
     *
     * @return void
     */
    public function checkServers() {
        // Do we have some server?
        if (! isset($this->settings['Servers']) || count($this->settings['Servers']) == 0) {
            // No server => create one with defaults
            $this->settings['Servers'] = array(1 => $this->default_server);
        } else {
            // We have server(s) => apply default configuration
            $new_servers = array();

            foreach ($this->settings['Servers'] as $server_index => $each_server) {

                // Detect wrong configuration
                if (!is_int($server_index) || $server_index < 1) {
                    trigger_error(
                        sprintf(__('Invalid server index: %s'), $server_index),
                        E_USER_ERROR
                    );
                }

                $each_server = array_merge($this->default_server, $each_server);

                // Final solution to bug #582890
                // If we are using a socket connection
                // and there is nothing in the verbose server name
                // or the host field, then generate a name for the server
                // in the form of "Server 2", localized of course!
                if (empty($each_server['host']) && empty($each_server['verbose'])) {
                    $each_server['verbose'] = sprintf(__('Server %d'), $server_index);
                }

                $new_servers[$server_index] = $each_server;
            }
            $this->settings['Servers'] = $new_servers;
        }
    }
}

if (!defined('TESTSUITE')) {
    register_shutdown_function(array('PhpMyAdmin\Config', 'fatalErrorHandler'));
}
