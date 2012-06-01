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
            $this->_files[$hash] = array(
                'filename' => $filename,
                'conditional_ie' => $conditional_ie
            );
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
     * Renders all the JavaScript file inclusions, code and events
     *
     * @return string
     */
    public function getDisplay()
    {
        $retval = '';

        foreach ($this->_files as $file) {
            $retval .= PMA_includeJS(
                $file['filename'],
                $file['conditional_ie']
            );
        }
        $retval .= '<script type="text/javascript">';
        $retval .= "// <![CDATA[\n";
        $retval .= $this->_code;
        foreach ($this->_events as $js_event) {
            $retval .= sprintf(
                "$(window.parent).bind('%s', %s);\n",
                $js_event['event'],
                $js_event['function']
            );
        }
        $retval .= '// ]]>';
        $retval .= '</script>';

        return $retval;
    }
}
