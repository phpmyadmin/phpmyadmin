<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
// Functions for listing directories

/**
 * Returns array of filtered file names
 *
 * @param   string  directory to list
 * @param   string  regullar expression to match files
 * @returns array   sorted file list on success, FALSE on failure
 */
function PMA_getDirContent($dir, $expression = '') {
    if ($handle = @opendir($dir)) {
        $result = array();
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }
        while ($file = @readdir($handle)) {
            if (is_file($dir . $file) && ($expression == '' || preg_match($expression, $file))) {
                $result[] = $file;
            }
        }
        @closedir($handle);
        asort($result);
        return $result;
    } else {
        return FALSE;
    }
}

/**
 * Returns options of filtered file names
 *
 * @param   string  directory to list
 * @param   string  regullar expression to match files
 * @param   string  currently active choice
 * @returns array   sorted file list on success, FALSE on failure
 */
function PMA_getFileSelectOptions($dir, $extensions = '', $active = '') {
    $list = PMA_getDirContent($dir, $extensions);
    if ($list === FALSE) return FALSE;
    $result = '';
    foreach($list as $key => $val) {
        $result .= '<option value="'. htmlspecialchars($val) . '"';
        if ($val == $active) {
            $result .= ' selected="selected"';
        }
        $result .= '>' . htmlspecialchars($val) . '</option>' . "\n";
    }
    return $result;
}
