<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * JavaScript management
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Collects information about which JavaScript
 * files and objects are necessary to render
 * the page and generates the relevant code.
 *
 * @package PhpMyAdmin
 */
class PMA_Scripts
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
     * An array of event names to bind and javascript code
     * snippets to fire for the corresponding events
     *
     * @access private
     * @var array
     */
    private $_events;

    /**
     * Returns HTML code to include javascript file.
     *
     * @param string $url            Location of javascript, relative to js/ folder.
     * @param int    $timestamp      The date when the file was last modified
     * @param string $ie_conditional true - wrap with IE conditional comment
     *                               'lt 9' etc. - wrap for specific IE version
     *
     * @return string HTML code for javascript inclusion.
     */
    private function _includeFile($url, $timestamp = null, $ie_conditional = false)
    {
        $include = '';
        if ($ie_conditional !== false) {
            if ($ie_conditional === true) {
                $include .= '<!--[if IE]>' . "\n    ";
            } else {
                $include .= '<!--[if IE ' . $ie_conditional . ']>' . "\n    ";
            }
        }
        $include .= '<script src="' . $url;
        if (! empty($timestamp)) {
            $include .= '?ts=' . filemtime($url);
        }
        $include .= '" type="text/javascript"></script>' . "\n";
        if ($ie_conditional !== false) {
            $include .= '<![endif]-->' . "\n";
        }
        return $include;
    }

    /**
     * Generates new PMA_Scripts objects
     *
     * @return PMA_Scripts object
     */
    public function __construct()
    {
        $this->_files  = array();
        $this->_code   = '';
        $this->_events = array();

    }

    /**
     * Adds a new file to the list of scripts
     *
     * @param string $filename       The name of the file to include
     * @param bool   $conditional_ie Whether to wrap the script tag in
     *                               conditional comments for IE
     *
     * @return void
     */
    public function addFile($filename, $conditional_ie = false)
    {
        $hash = md5($filename);
        if (empty($this->_files[$hash])) {
            $has_onload = $this->eventBlacklist($filename);
            $timestamp = null;
            if (strpos($filename, '?') === false) {
                $timestamp = filemtime('js/' . $filename);
            }
            $this->_files[$hash] = array(
                'has_onload' => $has_onload,
                'filename' => $filename,
                'timestamp' => $timestamp,
                'conditional_ie' => $conditional_ie
            );
        }
    }

    /**
     * Determines whether to fire up an onload event for a file
     *
     * @param string $filename
     *
     * @return int 1 to fire up the event, 0 not to
     */
    private function eventBlacklist($filename)
    {
        if (   strpos($filename, 'jquery') !== false
            || strpos($filename, 'codemirror') !== false
            || strpos($filename, 'messages.php') !== false
            || strpos($filename, 'ajax.js') !== false
            || strpos($filename, 'navigation.js') !== false
            || strpos($filename, 'get_image.js.php') !== false
        ) {
            return 0;
        } else {
            return 1;
        }
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
     * Adds a new event to the list of events
     *
     * @param string $event    The name of the event to register
     * @param string $function The code to execute when the event fires
     *                         E.g: 'function () { doSomething(); }'
     *                         or 'doSomething'
     *
     * @return void
     */
    public function addEvent($event, $function)
    {
        $this->_events[] = array(
            'event' => $event,
            'function' => $function
        );
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
            if (! $file['conditional_ie'] || PMA_USR_BROWSER_AGENT == 'IE') {
                $retval[] = array(
                    'name' => $file['filename'],
                    'fire' => $file['has_onload']
                );
            }
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

        foreach ($this->_files as $file) {
            $retval .= $this->_includeFile(
                'js/' . $file['filename'],
                $file['timestamp'],
                $file['conditional_ie']
            );
        }
        $code = 'AJAX.scriptHandler';
        foreach ($this->_files as $file) {
            $code .= sprintf(
                '.add("%s",%d)',
                PMA_escapeJsString($file['filename']),
                $file['has_onload'] ? 1 : 0
            );
        }
        $code .= ';';
        $this->addCode($code);

        $code = '$(function() {';
        foreach ($this->_files as $file) {
            if ($file['has_onload']) {
                $code .= 'AJAX.fireOnload("' . PMA_escapeJsString($file['filename']) . '");';
            }
        }
        $code .= '});';
        $this->addCode($code);

        $retval .= '<script type="text/javascript">';
        $retval .= "// <![CDATA[\n";
        $retval .= $this->_code;
        foreach ($this->_events as $js_event) {
            $retval .= sprintf(
                "$(window).bind('%s', %s);\n",
                $js_event['event'],
                $js_event['function']
            );
        }
        $retval .= '// ]]>';
        $retval .= '</script>';

        return $retval;
    }
}
