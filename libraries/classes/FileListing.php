<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for listing directories
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

/**
 * PhpMyAdmin\FileListing class
 *
 * @package PhpMyAdmin
 */
class FileListing
{
    /**
     * Returns array of filtered file names
     *
     * @param string $dir        directory to list
     * @param string $expression regular expression to match files
     *
     * @return array   sorted file list on success, false on failure
     */
    public static function getDirContent($dir, $expression = '')
    {
        if (!@file_exists($dir) || !($handle = @opendir($dir))) {
            return false;
        }

        $result = array();
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }
        while ($file = @readdir($handle)) {
            if (@is_file($dir . $file)
                && ! @is_link($dir . $file)
                && ($expression == '' || preg_match($expression, $file))
            ) {
                $result[] = $file;
            }
        }
        closedir($handle);
        asort($result);
        return $result;
    }

    /**
     * Returns options of filtered file names
     *
     * @param string $dir        directory to list
     * @param string $extensions regular expression to match files
     * @param string $active     currently active choice
     *
     * @return array   sorted file list on success, false on failure
     */
    public static function getFileSelectOptions($dir, $extensions = '', $active = '')
    {
        $list = self::getDirContent($dir, $extensions);
        if ($list === false) {
            return false;
        }
        $result = '';
        foreach ($list as $val) {
            $result .= '<option value="' . htmlspecialchars($val) . '"';
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
     * @return string separated list of extensions usable in self::getDirContent
     */
    public static function supportedDecompressions()
    {
        global $cfg;

        $compressions = '';

        if ($cfg['GZipDump'] && function_exists('gzopen')) {
            if (!empty($compressions)) {
                $compressions .= '|';
            }
            $compressions .= 'gz';
        }
        if ($cfg['BZipDump'] && function_exists('bzopen')) {
            if (!empty($compressions)) {
                $compressions .= '|';
            }
            $compressions .= 'bz2';
        }
        if ($cfg['ZipDump'] && function_exists('gzinflate')) {
            if (!empty($compressions)) {
                $compressions .= '|';
            }
            $compressions .= 'zip';
        }

        return $compressions;
    }
}
