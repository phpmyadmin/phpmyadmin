<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for listing directories
 *
 * @todo rename to file_listing.lib.php
 * @package PhpMyAdmin
 */

/**
 * Returns array of filtered file names
 *
 * @param string $dir        directory to list
 * @param string $expression regular expression to match files
 * @return array   sorted file list on success, false on failure
 */
function PMA_getDirContent($dir, $expression = '')
{
    if (file_exists($dir) && $handle = @opendir($dir)) {
        $result = array();
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }
        while ($file = @readdir($handle)) {
        // for PHP < 5.2.4, is_file() gives a warning when using open_basedir
        // and verifying '..' or '.'
            if ('.' != $file && '..' != $file && is_file($dir . $file) && ($expression == '' || preg_match($expression, $file))) {
                $result[] = $file;
            }
        }
        @closedir($handle);
        asort($result);
        return $result;
    } else {
        return false;
    }
}

/**
 * Returns options of filtered file names
 *
 * @param string $dir        directory to list
 * @param string $extensions regullar expression to match files
 * @param string $active     currently active choice
 * @return array   sorted file list on success, false on failure
 */
function PMA_getFileSelectOptions($dir, $extensions = '', $active = '')
{
    $list = PMA_getDirContent($dir, $extensions);
    if ($list === false) {
        return false;
    }
    $result = '';
    foreach ($list as $key => $val) {
        $result .= '<option value="'. htmlspecialchars($val) . '"';
        if ($val == $active) {
            $result .= ' selected="selected"';
        }
        $result .= '>' . htmlspecialchars($val) . '</option>' . "\n";
    }
    return $result;
}

/**
 * Get currently supported decompressions.
 *
 * @return string | separated list of extensions usable in PMA_getDirContent
 */
function PMA_supportedDecompressions()
{
    global $cfg;

    $compressions = '';

    if ($cfg['GZipDump'] && @function_exists('gzopen')) {
        if (!empty($compressions)) {
            $compressions .= '|';
        }
        $compressions .= 'gz';
    }
    if ($cfg['BZipDump'] && @function_exists('bzopen')) {
        if (!empty($compressions)) {
            $compressions .= '|';
        }
        $compressions .= 'bz2';
    }
    if ($cfg['ZipDump'] && @function_exists('gzinflate')) {
        if (!empty($compressions)) {
            $compressions .= '|';
        }
        $compressions .= 'zip';
    }

    return $compressions;
}
