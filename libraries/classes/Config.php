<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Configuration handling.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use DirectoryIterator;
use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Error;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
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
    public $default_source = ROOT_PATH . 'libraries/config.default.php';

    /**
     * @var array   default configuration settings
     */
    public $default = [];

    /**
     * @var array   configuration settings, without user preferences applied
     */
    public $base_settings = [];

    /**
     * @var array   configuration settings
     */
    public $settings = [];

    /**
     * @var string  config source
     */
    public $source = '';

    /**
     * @var int     source modification time
     */
    public $source_mtime = 0;
    public $default_source_mtime = 0;
    public $set_mtime = 0;

    /**
     * @var boolean
     */
    public $error_config_file = false;

    /**
     * @var boolean
     */
    public $error_config_default_file = false;

    /**
     * @var array
     */
    public $default_server = [];

    /**
     * @var boolean whether init is done or not
     * set this to false to force some initial checks
     * like checking for required functions
     */
    public $done = false;

    /**
     * constructor
     *
     * @param string $source source to read config from
     */
    public function __construct(?string $source = null)
    {
        $this->settings = ['is_setup' => false];

        // functions need to refresh in case of config file changed goes in
        // PhpMyAdmin\Config::load()
        $this->load($source);

        // other settings, independent from config file, comes in
        $this->checkSystem();

        $this->base_settings = $this->settings;
    }

    /**
     * sets system and application settings
     *
     * @return void
     */
    public function checkSystem(): void
    {
        $this->set('PMA_VERSION', '5.0.2');
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
    public function checkOutputCompression(): void
    {
        // If zlib output compression is set in the php configuration file, no
        // output buffering should be run
        if (ini_get('zlib.output_compression')) {
            $this->set('OBGzip', false);
        }

        // enable output-buffering (if set to 'auto')
        if (strtolower((string) $this->get('OBGzip')) == 'auto') {
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
    private function _setClientPlatform(string $user_agent): void
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
    public function checkClient(): void
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
                'PMA_USR_BROWSER_VER',
                $log_version[1]
            );
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // older Safari
        } elseif ($is_mozilla
            && preg_match('@Safari/([0-9]*)@', $HTTP_USER_AGENT, $log_version)
        ) {
            $this->set(
                'PMA_USR_BROWSER_VER',
                $mozilla_version[1] . '.' . $log_version[1]
            );
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // Firefox
        } elseif (! mb_strstr($HTTP_USER_AGENT, 'compatible')
            && preg_match('@Firefox/([\w.]+)@', $HTTP_USER_AGENT, $log_version)
        ) {
            $this->set(
                'PMA_USR_BROWSER_VER',
                $log_version[1]
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
    public function checkGd2(): void
    {
        if ($this->get('GD2Available') == 'yes') {
            $this->set('PMA_IS_GD2', 1);
            return;
        }

        if ($this->get('GD2Available') == 'no') {
            $this->set('PMA_IS_GD2', 0);
            return;
        }

        if (! function_exists('imagecreatetruecolor')) {
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
    public function checkWebServer(): void
    {
        // some versions return Microsoft-IIS, some Microsoft/IIS
        // we could use a preg_match() but it's slower
        if (Core::getenv('SERVER_SOFTWARE')
            && false !== stripos(Core::getenv('SERVER_SOFTWARE'), 'Microsoft')
            && false !== stripos(Core::getenv('SERVER_SOFTWARE'), 'IIS')
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
    public function checkWebServerOs(): void
    {
        // Default to Unix or Equiv
        $this->set('PMA_IS_WINDOWS', 0);
        // If PHP_OS is defined then continue
        if (defined('PHP_OS')) {
            if (false !== stripos(PHP_OS, 'win') && false === stripos(PHP_OS, 'darwin')) {
                // Is it some version of Windows
                $this->set('PMA_IS_WINDOWS', 1);
            } elseif (false !== stripos(PHP_OS, 'OS/2')) {
                // Is it OS/2 (No file permissions like Windows)
                $this->set('PMA_IS_WINDOWS', 1);
            }
        }
    }

    /**
     * detects if Git revision
     * @param string $git_location (optional) verified git directory
     * @return boolean
     */
    public function isGitRevision(&$git_location = null): bool
    {
        // PMA config check
        if (! $this->get('ShowGitRevision')) {
            return false;
        }

        // caching
        if (isset($_SESSION['is_git_revision'])
            && array_key_exists('git_location', $_SESSION)
        ) {
            // Define location using cached value
            $git_location = $_SESSION['git_location'];
            return $_SESSION['is_git_revision'];
        }

        // find out if there is a .git folder
        // or a .git file (--separate-git-dir)
        $git = '.git';
        if (is_dir($git)) {
            if (@is_file($git . '/config')) {
                $git_location = $git;
            } else {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;
                return false;
            }
        } elseif (is_file($git)) {
            $contents = file_get_contents($git);
            $gitmatch = [];
            // Matches expected format
            if (! preg_match(
                '/^gitdir: (.*)$/',
                $contents,
                $gitmatch
            )) {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;
                return false;
            } elseif (@is_dir($gitmatch[1])) {
                //Detected git external folder location
                $git_location = $gitmatch[1];
            } else {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;
                return false;
            }
        } else {
            $_SESSION['git_location'] = null;
            $_SESSION['is_git_revision'] = false;
            return false;
        }
        // Define session for caching
        $_SESSION['git_location'] = $git_location;
        $_SESSION['is_git_revision'] = true;
        return true;
    }

    /**
     * detects Git revision, if running inside repo
     *
     * @return void
     */
    public function checkGitRevision(): void
    {
        // find out if there is a .git folder
        $git_folder = '';
        if (! $this->isGitRevision($git_folder)) {
            $this->set('PMA_VERSION_GIT', 0);
            return;
        }

        if (! $ref_head = @file_get_contents($git_folder . '/HEAD')) {
            $this->set('PMA_VERSION_GIT', 0);
            return;
        }

        if ($common_dir_contents = @file_get_contents($git_folder . '/commondir')) {
            $git_folder = $git_folder . DIRECTORY_SEPARATOR . trim($common_dir_contents);
        }

        $branch = false;
        // are we on any branch?
        if (false !== strpos($ref_head, '/')) {
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
                    $this->set('PMA_VERSION_GIT', 0);
                    return;
                }
                $hash = trim($hash);
            } else {
                // deal with packed refs
                $packed_refs = @file_get_contents($git_folder . '/packed-refs');
                if (! $packed_refs) {
                    $this->set('PMA_VERSION_GIT', 0);
                    return;
                }
                // split file to lines
                $ref_lines = explode(PHP_EOL, $packed_refs);
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
                    $this->set('PMA_VERSION_GIT', 0);
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
            if (@file_exists($git_file_name)) {
                if (! $commit = @file_get_contents($git_file_name)) {
                    $this->set('PMA_VERSION_GIT', 0);
                    return;
                }
                $commit = explode("\0", gzuncompress($commit), 2);
                $commit = explode("\n", $commit[1]);
                $_SESSION['PMA_VERSION_COMMITDATA_' . $hash] = $commit;
            } else {
                $pack_names = [];
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
                        $git_folder . '/objects/pack/' . $pack_name,
                        'rb'
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
            switch ($is_found) {
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
                switch ($is_found) {
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
            $author = [
                'name' => '',
                'email' => '',
                'date' => '',
            ];
            $committer = [
                'name' => '',
                'email' => '',
                'date' => '',
            ];

            do {
                $dataline = array_shift($commit);
                $datalinearr = explode(' ', $dataline, 2);
                $linetype = $datalinearr[0];
                if (in_array($linetype, ['author', 'committer'])) {
                    $user = $datalinearr[1];
                    preg_match('/([^<]+)<([^>]+)> ([0-9]+)( [^ ]+)?/', $user, $user);
                    $user2 = [
                        'name' => trim($user[1]),
                        'email' => trim($user[2]),
                        'date' => date('Y-m-d H:i:s', (int) $user[3]),
                    ];
                    if (isset($user[4])) {
                        $user2['date'] .= $user[4];
                    }
                    $$linetype = $user2;
                }
            } while ($dataline != '');
            $message = trim(implode(' ', $commit));
        } elseif (isset($commit_json) && isset($commit_json->author) && isset($commit_json->committer) && isset($commit_json->message)) {
            $author = [
                'name' => $commit_json->author->name,
                'email' => $commit_json->author->email,
                'date' => $commit_json->author->date,
            ];
            $committer = [
                'name' => $commit_json->committer->name,
                'email' => $commit_json->committer->email,
                'date' => $commit_json->committer->date,
            ];
            $message = trim($commit_json->message);
        } else {
            $this->set('PMA_VERSION_GIT', 0);
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
    public function loadDefaults(): bool
    {
        $cfg = [];
        if (! @file_exists($this->default_source)) {
            $this->error_config_default_file = true;
            return false;
        }
        $canUseErrorReporting = function_exists('error_reporting');
        $oldErrorReporting = null;
        if ($canUseErrorReporting) {
            $oldErrorReporting = error_reporting(0);
        }
        ob_start();
        $GLOBALS['pma_config_loading'] = true;
        $eval_result = include $this->default_source;
        $GLOBALS['pma_config_loading'] = false;
        ob_end_clean();
        if ($canUseErrorReporting) {
            error_reporting($oldErrorReporting);
        }

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
    public function load(?string $source = null): bool
    {
        $this->loadDefaults();

        if (null !== $source) {
            $this->setSource($source);
        }

        if (! $this->checkConfigSource()) {
            return false;
        }

        $cfg = [];

        /**
         * Parses the configuration file, we throw away any errors or
         * output.
         */
        $canUseErrorReporting = function_exists('error_reporting');
        $oldErrorReporting = null;
        if ($canUseErrorReporting) {
            $oldErrorReporting = error_reporting(0);
        }
        ob_start();
        $GLOBALS['pma_config_loading'] = true;
        $eval_result = include $this->getSource();
        $GLOBALS['pma_config_loading'] = false;
        ob_end_clean();
        if ($canUseErrorReporting) {
            error_reporting($oldErrorReporting);
        }

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
            function ($key) {
                return strpos($key, '/') === false;
            }
        );

        $cfg = array_intersect_key($cfg, array_flip($matched_keys));

        /**
         * Backward compatibility code
         */
        if (! empty($cfg['DefaultTabTable'])) {
            $cfg['DefaultTabTable'] = str_replace(
                [
                    'tbl_properties.php',
                    '_properties',
                ],
                [
                    'tbl_sql.php',
                    '',
                ],
                $cfg['DefaultTabTable']
            );
        }
        if (! empty($cfg['DefaultTabDatabase'])) {
            $cfg['DefaultTabDatabase'] = str_replace(
                [
                    'db_details.php',
                    '_details',
                ],
                [
                    'db_sql.php',
                    '',
                ],
                $cfg['DefaultTabDatabase']
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
    private function _setConnectionCollation(): void
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
    public function loadUserPreferences(): void
    {
        $userPreferences = new UserPreferences();
        // index.php should load these settings, so that phpmyadmin.css.php
        // will have everything available in session cache
        $server = isset($GLOBALS['server'])
            ? $GLOBALS['server']
            : (! empty($GLOBALS['cfg']['ServerDefault'])
                ? $GLOBALS['cfg']['ServerDefault']
                : 0);
        $cache_key = 'server_' . $server;
        if ($server > 0 && ! defined('PMA_MINIMUM_COMMON')) {
            $config_mtime = max($this->default_source_mtime, $this->source_mtime);
            // cache user preferences, use database only when needed
            if (! isset($_SESSION['cache'][$cache_key]['userprefs'])
                || $_SESSION['cache'][$cache_key]['config_mtime'] < $config_mtime
            ) {
                $prefs = $userPreferences->load();
                $_SESSION['cache'][$cache_key]['userprefs']
                    = $userPreferences->apply($prefs['config_data']);
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
        if ($this->issetCookie('pma_lang') || isset($_POST['lang'])) {
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
     * @param string|null $cookie_name   can be null
     * @param string      $cfg_path      configuration path
     * @param mixed       $new_cfg_value new value
     * @param mixed       $default_value default value
     *
     * @return true|Message
     */
    public function setUserValue(
        ?string $cookie_name,
        string $cfg_path,
        $new_cfg_value,
        $default_value = null
    ) {
        $userPreferences = new UserPreferences();
        $result = true;
        // use permanent user preferences if possible
        $prefs_type = $this->get('user_preferences');
        if ($prefs_type) {
            if ($default_value === null) {
                $default_value = Core::arrayRead($cfg_path, $this->default);
            }
            $result = $userPreferences->persistOption($cfg_path, $new_cfg_value, $default_value);
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
    public function getUserValue(string $cookie_name, $cfg_value)
    {
        $cookie_exists = isset($_COOKIE) && ! empty($this->getCookie($cookie_name));
        $prefs_type = $this->get('user_preferences');
        if ($prefs_type == 'db') {
            // permanent user preferences value exists, remove cookie
            if ($cookie_exists) {
                $this->removeCookie($cookie_name);
            }
        } elseif ($cookie_exists) {
            return $this->getCookie($cookie_name);
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
    public function setSource(string $source): void
    {
        $this->source = trim($source);
    }

    /**
     * check config source
     *
     * @return boolean whether source is valid or not
     */
    public function checkConfigSource(): bool
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
    public function checkPermissions(): void
    {
        // Check for permissions (on platforms that support it):
        if ($this->get('CheckConfigurationPermissions') && @file_exists($this->getSource())) {
            $perms = @fileperms($this->getSource());
            if (! ($perms === false) && ($perms & 2)) {
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
    public function checkErrors(): void
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
    public function get(string $setting)
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
    public function set(string $setting, $value): void
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
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * returns a unique value to force a CSS reload if either the config
     * or the theme changes
     *
     * @return int Summary of unix timestamps, to be unique on theme parameters
     *             change
     */
    public function getThemeUniqueValue(): int
    {
        return (int) (
            $this->source_mtime +
            $this->default_source_mtime +
            $this->get('user_preferences_mtime') +
            $GLOBALS['PMA_Theme']->mtime_info +
            $GLOBALS['PMA_Theme']->filesize_info
        );
    }

    /**
     * checks if upload is enabled
     *
     * @return void
     */
    public function checkUpload(): void
    {
        if (! ini_get('file_uploads')) {
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
    public function checkUploadSize(): void
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
    public function isHttps(): bool
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
        } elseif (strtolower(Core::getenv('HTTP_CLOUDFRONT_FORWARDED_PROTO')) === 'https') {
            // Amazon CloudFront, issue #15621
            $is_https = true;
        } elseif (Util::getProtoFromForwardedHeader(Core::getenv('HTTP_FORWARDED')) === 'https') {
            // RFC 7239 Forwarded header
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
    public function getRootPath(): string
    {
        static $cookie_path = null;

        if (null !== $cookie_path && ! defined('TESTSUITE')) {
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
    public function enableBc(): void
    {
        $GLOBALS['cfg']             = $this->settings;
        $GLOBALS['default_server']  = $this->default_server;
        unset($this->default_server);
        $GLOBALS['is_upload']       = $this->get('enable_upload');
        $GLOBALS['max_upload_size'] = $this->get('max_upload_size');
        $GLOBALS['is_https']        = $this->get('is_https');

        $defines = [
            'PMA_VERSION',
            'PMA_MAJOR_VERSION',
            'PMA_THEME_VERSION',
            'PMA_THEME_GENERATION',
            'PMA_IS_WINDOWS',
            'PMA_IS_GD2',
            'PMA_USR_OS',
            'PMA_USR_BROWSER_VER',
            'PMA_USR_BROWSER_AGENT',
        ];

        foreach ($defines as $define) {
            if (! defined($define)) {
                define($define, $this->get($define));
            }
        }
    }

    /**
     * removes cookie
     *
     * @param string $cookieName name of cookie to remove
     *
     * @return boolean result of setcookie()
     */
    public function removeCookie(string $cookieName): bool
    {
        $httpCookieName = $this->getCookieName($cookieName);

        if ($this->issetCookie($cookieName)) {
            unset($_COOKIE[$httpCookieName]);
        }
        if (defined('TESTSUITE')) {
            return true;
        }
        return setcookie(
            $httpCookieName,
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
    public function setCookie(
        string $cookie,
        $value,
        ?string $default = null,
        ?int $validity = null,
        bool $httponly = true
    ): bool {
        if (strlen($value) > 0 && null !== $default && $value === $default
        ) {
            // default value is used
            if ($this->issetCookie($cookie)) {
                // remove cookie
                return $this->removeCookie($cookie);
            }
            return false;
        }

        if (strlen($value) === 0 && $this->issetCookie($cookie)) {
            // remove cookie, value is empty
            return $this->removeCookie($cookie);
        }

        $httpCookieName = $this->getCookieName($cookie);

        if (! $this->issetCookie($cookie) ||  $this->getCookie($cookie) !== $value) {
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
                $_COOKIE[$httpCookieName] = $value;
                return true;
            }
            return setcookie(
                $httpCookieName,
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
     * get cookie
     *
     * @param string $cookieName The name of the cookie to get
     *
     * @return mixed result of getCookie()
     */
    public function getCookie(string $cookieName)
    {
        if (isset($_COOKIE[$this->getCookieName($cookieName)])) {
            return $_COOKIE[$this->getCookieName($cookieName)];
        } else {
            return null;
        }
    }

    /**
     * Get the real cookie name
     *
     * @param string $cookieName The name of the cookie
     * @return string
     */
    public function getCookieName(string $cookieName): string
    {
        return $cookieName . ( ($this->isHttps()) ? '_https' : '' );
    }

    /**
     * isset cookie
     *
     * @param string $cookieName The name of the cookie to check
     *
     * @return bool result of issetCookie()
     */
    public function issetCookie(string $cookieName): bool
    {
        return isset($_COOKIE[$this->getCookieName($cookieName)]);
    }

    /**
     * Error handler to catch fatal errors when loading configuration
     * file
     *
     * @return void
     */
    public static function fatalErrorHandler(): void
    {
        if (! isset($GLOBALS['pma_config_loading'])
            || ! $GLOBALS['pma_config_loading']
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
    private static function _renderCustom(string $filename, string $id): string
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
    public static function renderFooter(): string
    {
        return self::_renderCustom(CUSTOM_FOOTER_FILE, 'pma_footer');
    }

    /**
     * Renders user configured footer
     *
     * @return string
     */
    public static function renderHeader(): string
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
    public function getTempDir(string $name): ?string
    {
        static $temp_dir = [];

        if (isset($temp_dir[$name]) && ! defined('TESTSUITE')) {
            return $temp_dir[$name];
        }

        $path = $this->get('TempDir');
        if (empty($path)) {
            $path = null;
        } else {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
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
     * @return string|null
     */
    public function getUploadTempDir(): ?string
    {
        // First try configured temp dir
        // Fallback to PHP upload_tmp_dir
        $dirs = [
            $this->getTempDir('upload'),
            ini_get('upload_tmp_dir'),
            sys_get_temp_dir(),
        ];

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
    public function selectServer(): int
    {
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
            if (! empty($this->settings['Servers'][$this->settings['ServerDefault']])) {
                $server = $this->settings['ServerDefault'];
                $this->settings['Server'] = $this->settings['Servers'][$server];
            } else {
                $server = 0;
                $this->settings['Server'] = [];
            }
        }

        return (int) $server;
    }

    /**
     * Checks whether Servers configuration is valid and possibly apply fixups.
     *
     * @return void
     */
    public function checkServers(): void
    {
        // Do we have some server?
        if (! isset($this->settings['Servers']) || count($this->settings['Servers']) === 0) {
            // No server => create one with defaults
            $this->settings['Servers'] = [1 => $this->default_server];
        } else {
            // We have server(s) => apply default configuration
            $new_servers = [];

            foreach ($this->settings['Servers'] as $server_index => $each_server) {
                // Detect wrong configuration
                if (! is_int($server_index) || $server_index < 1) {
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

if (! defined('TESTSUITE')) {
    register_shutdown_function([Config::class, 'fatalErrorHandler']);
}
