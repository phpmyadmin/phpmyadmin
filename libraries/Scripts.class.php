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
     * @param array $files The list of js file to include
     *
     * @return string HTML code for javascript inclusion.
     */
    private function _includeFiles($files)
    {
        $first_dynamic_scripts = "";
        $dynamic_scripts = "";
        $scripts = array();
        foreach ($files as $value) {
            if (/*overload*/mb_strpos($value['filename'], "?") !== false) {
                if ($value['before_statics'] === true) {
                    $first_dynamic_scripts
                        .= "<script data-cfasync='false' type='text/javascript' src='js/"
                        . $value['filename'] . "'></script>";
                } else {
                    $dynamic_scripts .= "<script data-cfasync='false' type='text/javascript' src='js/"
                        . $value['filename'] . "'></script>";
                }
                continue;
            }
            $include = true;
            if ($value['conditional_ie'] !== false
                && PMA_USR_BROWSER_AGENT === 'IE'
            ) {
                if ($value['conditional_ie'] === true) {
                    $include = true;
                } else if ($value['conditional_ie'] == PMA_USR_BROWSER_VER) {
                    $include = true;
                } else {
                    $include = false;
                }
            }
            if ($include) {
                $scripts[] = "scripts%5B%5D=" . $value['filename'];
            }
        }
        $separator = PMA_URL_getArgSeparator();
        $static_scripts = '';
        // Using chunks of 10 files to avoid too long URLs
        // as some servers are set to 512 bytes URL limit
        $script_chunks = array_chunk($scripts, 10);
        foreach ($script_chunks as $script_chunk) {
            $url = 'js/get_scripts.js.php?' . implode($separator, $script_chunk);

            $static_scripts .= sprintf(
                '<script data-cfasync="false" type="text/javascript" src="%s"></script>',
                htmlspecialchars($url)
            );
        }
        return $first_dynamic_scripts . $static_scripts . $dynamic_scripts;
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
     * @param bool   $before_statics Whether this dynamic script should be
     *                               included before the static ones
     *
     * @return void
     */
    public function addFile(
        $filename,
        $conditional_ie = false,
        $before_statics = false
    ) {
        $hash = md5($filename);
        if (!empty($this->_files[$hash])) {
            return;
        }

        $has_onload = $this->_eventBlacklist($filename);
        $this->_files[$hash] = array(
            'has_onload' => $has_onload,
            'filename' => $filename,
            'conditional_ie' => $conditional_ie,
            'before_statics' => $before_statics
        );
    }

    /**
     * Add new files to the list of scripts
     *
     * @param array $filelist       The array of file names
     * @param bool  $conditional_ie Whether to wrap the script tag in
     *                              conditional comments for IE
     *
     * @return void
     */
    public function addFiles($filelist, $conditional_ie = false)
    {
        foreach ($filelist as $filename) {
            $this->addFile($filename, $conditional_ie);
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
        if (   strpos($filename, 'jquery') !== false
            || strpos($filename, 'codemirror') !== false
            || strpos($filename, 'messages.php') !== false
            || strpos($filename, 'ajax.js') !== false
            || strpos($filename, 'navigation.js') !== false
            || strpos($filename, 'get_image.js.php') !== false
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
            //If filename contains a "?", continue.
            if (strpos($file['filename'], "?") !== false) {
                continue;
            }

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

        if (count($this->_files) > 0) {
            $retval .= $this->_includeFiles(
                $this->_files
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
                $code .= 'AJAX.fireOnload("';
                $code .= PMA_escapeJsString($file['filename']);
                $code .= '");';
            }
        }
        $code .= '});';
        $this->addCode($code);

        $retval .= '<script data-cfasync="false" type="text/javascript">';
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
