<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

class PMA_Scripts {
    private $_files;
    private $_code;
    private $_events;

    public function __construct()
    {
        $this->_files = array();
        $this->_code = '';
        $this->_events = array();
        // Include default scripts
        $this->_addDefaults();

    }

    public function addFile($filename)
    {
        if (! in_array($filename, $this->_files)) {
            $this->_files[] = $filename;
        }
    }

    public function addCode($code)
    {
        $this->_code .= "$code\n";
    }

    public function addEvent($event, $function)
    {
        $this->_events[] = array(
            'event' => $event,
            'function' => $function
        );
    }

    private function _addDefaults()
    {
        $this->addFile('jquery/jquery-1.6.2.js');
        $this->addFile('jquery/jquery-ui-1.8.16.custom.js');
        $this->addFile('jquery/jquery.sprintf.js');
        $this->addFile('update-location.js');

        $this->addFile('functions.js');
        $this->addFile('jquery/jquery.qtip-1.0.0-rc3.js');
        if ($GLOBALS['cfg']['CodemirrorEnable']) {
            $this->addFile('codemirror/lib/codemirror.js');
            $this->addFile('codemirror/mode/mysql/mysql.js');
        }
        // Cross-framing protection
        if ($GLOBALS['cfg']['AllowThirdPartyFraming'] === false) {
            $this->addFile('cross_framing_protection.js');
        }
        // Localised strings
        $params = array('lang' => $GLOBALS['lang']);
        if (isset($GLOBALS['db'])) {
            $params['db'] = $GLOBALS['db'];
        }
        $this->addFile('messages.php' . PMA_generate_common_url($params));
        // Append the theme id to this url to invalidate
        // the cache on a theme change
        $this->addFile(
            'get_image.js.php?theme='
            . urlencode($_SESSION['PMA_Theme']->getId())
        );

        // generate title (unless we already have
        // $GLOBALS['page_title'], from cookie auth)
        if (! isset($GLOBALS['page_title'])) {
            if ($GLOBALS['server'] > 0) {
                if (! empty($GLOBALS['table'])) {
                    $temp_title = $GLOBALS['cfg']['TitleTable'];
                } else if (! empty($GLOBALS['db'])) {
                    $temp_title = $GLOBALS['cfg']['TitleDatabase'];
                } elseif (! empty($GLOBALS['cfg']['Server']['host'])) {
                    $temp_title = $GLOBALS['cfg']['TitleServer'];
                } else {
                    $temp_title = $GLOBALS['cfg']['TitleDefault'];
                }
                $title = PMA_expandUserString($temp_title);
            }
        } else {
            $title = $GLOBALS['page_title'];
        }
        if (isset($title)) {
            $title = PMA_sanitize(
                PMA_escapeJsString($title),
                false,
                true
            );
            $this->addCode(
                "if (typeof(parent.document) != 'undefined'"
                . " && typeof(parent.document) != 'unknown'"
                . " && typeof(parent.document.title) == 'string')"
                . "{"
                . "parent.document.title = '$title'"
                . "}"
            );
        }
        $this->addCode(PMA_getReloadNavigationScript(true));
    }

    public function getDisplay()
    {
        $retval = '';

        foreach ($this->_files as $file) {
            $retval .= PMA_includeJS($file);
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
