<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * JavaScript management
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Header;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Url;

/**
 * Collects information about which JavaScript
 * files and objects are necessary to render
 * the page and generates the relevant code.
 *
 * @package PhpMyAdmin
 */
class Scripts
{
    /**
     * An array of SCRIPT tags
     *
     * @access private
     * @var array of strings
     */
    private $_files;
    /**
     * An array of discrete javascript code snippets
     *
     * @access private
     * @var array of strings
     */
    private $_code;

    /**
     * Returns HTML code to include javascript file.
     *
     * @param array $files The list of js file to include
     *
     * @return string HTML code for javascript inclusion.
     */
    private function _includeFiles(array $files)
    {
        $result = '';
        foreach ($files as $value) {
            if (strpos($value['filename'], ".php") !== false) {
                $file_name = $value['filename'] . Url::getCommon($value['params'] + array('v' => PMA_VERSION));
                $result .= "<script data-cfasync='false' "
                    . "type='text/javascript' src='js/" . $file_name
                    . "'></script>\n";
            } else {
                $result .= '<script data-cfasync="false" type="text/javascript" src="js/'
                    .  $value['filename'] . '?' . Header::getVersionParameter() . '"></script>' . "\n";
            }
        }
        return $result;
    }

    /**
     * Generates new Scripts objects
     *
     */
    public function __construct()
    {
        $this->_files  = array();
        $this->_code   = '';

    }

    /**
     * Adds a new file to the list of scripts
     *
     * @param string $filename The name of the file to include
     * @param array  $params   Additional parameters to pass to the file
     *
     * @return void
     */
    public function addFile(
        $filename,
        array $params = array()
    ) {
        $hash = md5($filename);
        if (!empty($this->_files[$hash])) {
            return;
        }

        $has_onload = $this->_eventBlacklist($filename);
        $this->_files[$hash] = array(
            'has_onload' => $has_onload,
            'filename' => $filename,
            'params' => $params,
        );
    }

    /**
     * Add new files to the list of scripts
     *
     * @param array $filelist The array of file names
     *
     * @return void
     */
    public function addFiles(array $filelist)
    {
        foreach ($filelist as $filename) {
            $this->addFile($filename);
        }
    }

    /**
     * Determines whether to fire up an onload event for a file
     *
     * @param string $filename The name of the file to be checked
     *                         against the blacklist
     *
     * @return int 1 to fire up the event, 0 not to
     */
    private function _eventBlacklist($filename)
    {
        if (strpos($filename, 'jquery') !== false
            || strpos($filename, 'codemirror') !== false
            || strpos($filename, 'messages.php') !== false
            || strpos($filename, 'ajax.js') !== false
            || strpos($filename, 'cross_framing_protection.js') !== false
        ) {
            return 0;
        }

        return 1;
    }

    /**
     * Adds a new code snippet to the code to be executed
     *
     * @param string $code The JS code to be added
     *
     * @return void
     */
    public function addCode($code)
    {
        $this->_code .= "$code\n";
    }

    /**
     * Returns a list with filenames and a flag to indicate
     * whether to register onload events for this file
     *
     * @return array
     */
    public function getFiles()
    {
        $retval = array();
        foreach ($this->_files as $file) {
            //If filename contains a "?", continue.
            if (strpos($file['filename'], "?") !== false) {
                continue;
            }
            $retval[] = array(
                'name' => $file['filename'],
                'fire' => $file['has_onload']
            );

        }
        return $retval;
    }

    /**
     * Renders all the JavaScript file inclusions, code and events
     *
     * @return string
     */
    public function getDisplay()
    {
        $retval = '';

        if (count($this->_files) > 0) {
            $retval .= $this->_includeFiles(
                $this->_files
            );
        }

        $code = 'AJAX.scriptHandler';
        foreach ($this->_files as $file) {
            $code .= sprintf(
                '.add("%s",%d)',
                Sanitize::escapeJsString($file['filename']),
                $file['has_onload'] ? 1 : 0
            );
        }
        $code .= ';';
        $this->addCode($code);

        $code = '$(function() {';
        foreach ($this->_files as $file) {
            if ($file['has_onload']) {
                $code .= 'AJAX.fireOnload("';
                $code .= Sanitize::escapeJsString($file['filename']);
                $code .= '");';
            }
        }
        $code .= '});';
        $this->addCode($code);

        $retval .= '<script data-cfasync="false" type="text/javascript">';
        $retval .= "// <![CDATA[\n";
        $retval .= $this->_code;
        $retval .= '// ]]>';
        $retval .= '</script>';

        return $retval;
    }
}
