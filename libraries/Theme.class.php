<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA_Theme class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * handles theme
 *
 * @todo add the possibility to make a theme depend on another theme
 * and by default on original
 * @todo make all components optional - get missing components from 'parent' theme
 *
 * @package PhpMyAdmin
 */
class PMA_Theme
{
    /**
     * @var string theme version
     * @access  protected
     */
    var $version = '0.0.0.0';

    /**
     * @var string theme name
     * @access  protected
     */
    var $name = '';

    /**
     * @var string theme id
     * @access  protected
     */
    var $id = '';

    /**
     * @var string theme path
     * @access  protected
     */
    var $path = '';

    /**
     * @var string image path
     * @access  protected
     */
    var $img_path = '';

    /**
     * @var integer last modification time for info file
     * @access  protected
     */
    var $mtime_info = 0;

    /**
     * needed because sometimes, the mtime for different themes
     * is identical
     * @var integer filesize for info file
     * @access  protected
     */
    var $filesize_info = 0;

    /**
     * @var array List of css files to load
     * @access private
     */
    private $_cssFiles = array(
        'common',
        'enum_editor',
        'gis',
        'navigation',
        'pmd',
        'rte'
    );

    /**
     * Loads theme information
     *
     * @return boolean whether loading them info was successful or not
     * @access  public
     */
    function loadInfo()
    {
        if (! file_exists($this->getPath() . '/info.inc.php')) {
            return false;
        }

        if ($this->mtime_info === filemtime($this->getPath() . '/info.inc.php')) {
            return true;
        }

        @include $this->getPath() . '/info.inc.php';

        // was it set correctly?
        if (! isset($theme_name)) {
            return false;
        }

        $this->mtime_info = filemtime($this->getPath() . '/info.inc.php');
        $this->filesize_info = filesize($this->getPath() . '/info.inc.php');

        if (isset($theme_full_version)) {
            $this->setVersion($theme_full_version);
        } elseif (isset($theme_generation, $theme_version)) {
            $this->setVersion($theme_generation . '.' . $theme_version);
        }
        $this->setName($theme_name);

        return true;
    }

    /**
     * returns theme object loaded from given folder
     * or false if theme is invalid
     *
     * @param string $folder path to theme
     *
     * @return object PMA_Theme
     * @static
     * @access public
     */
    static public function load($folder)
    {
        $theme = new PMA_Theme();

        $theme->setPath($folder);

        if (! $theme->loadInfo()) {
            return false;
        }

        $theme->checkImgPath();

        return $theme;
    }

    /**
     * checks image path for existance - if not found use img from fallback theme
     *
     * @access public
     * @return bool
     */
    public function checkImgPath()
    {
        if (is_dir($this->getPath() . '/img/')) {
            $this->setImgPath($this->getPath() . '/img/');
            return true;
        } elseif (is_dir($GLOBALS['cfg']['ThemePath'] . '/' . PMA_Theme_Manager::FALLBACK_THEME . '/img/')) {
            $this->setImgPath($GLOBALS['cfg']['ThemePath'] . '/' . PMA_Theme_Manager::FALLBACK_THEME . '/img/');
            return true;
        } else {
            trigger_error(
                sprintf(
                    __('No valid image path for theme %s found!'),
                    $this->getName()
                ),
                E_USER_ERROR
            );
            return false;
        }
    }

    /**
     * returns path to theme
     *
     * @access public
     * @return string path to theme
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * returns layout file
     *
     * @access public
     * @return string layout file
     */
    public function getLayoutFile()
    {
        return $this->getPath() . '/layout.inc.php';
    }

    /**
     * set path to theme
     *
     * @param string $path path to theme
     *
     * @return void
     * @access public
     */
    public function setPath($path)
    {
        $this->path = trim($path);
    }

    /**
     * sets version
     *
     * @param string $version version to set
     *
     * @return void
     * @access public
     */
    public function setVersion($version)
    {
        $this->version = trim($version);
    }

    /**
     * returns version
     *
     * @return string version
     * @access public
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * checks theme version agaisnt $version
     * returns true if theme version is equal or higher to $version
     *
     * @param string $version version to compare to
     *
     * @return boolean true if theme version is equal or higher to $version
     * @access public
     */
    public function checkVersion($version)
    {
        return version_compare($this->getVersion(), $version, 'lt');
    }

    /**
     * sets name
     *
     * @param string $name name to set
     *
     * @return void
     * @access public
     */
    public function setName($name)
    {
        $this->name = trim($name);
    }

    /**
     * returns name
     *
     * @access  public
     * @return string name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * sets id
     *
     * @param string $id new id
     *
     * @return void
     * @access public
     */
    public function setId($id)
    {
        $this->id = trim($id);
    }

    /**
     * returns id
     *
     * @return string id
     * @access public
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets path to images for the theme
     *
     * @param string $path path to images for this theme
     *
     * @return void
     * @access public
     */
    public function setImgPath($path)
    {
        $this->img_path = $path;
    }

    /**
     * Returns the path to images for the theme
     *
     * @access public
     * @return string image path for this theme
     */
    public function getImgPath()
    {
        return $this->img_path;
    }

    /**
     * load css (send to stdout, normally the browser)
     *
     * @return bool
     * @access  public
     */
    public function loadCss()
    {
        $success = true;

        echo PMA_SQP_buildCssData();

        if ($GLOBALS['text_dir'] === 'ltr') {
            $right = 'right';
            $left = 'left';
        } else {
            $right = 'left';
            $left = 'right';
        }

        foreach ($this->_cssFiles as $file) {
            $path = $this->getPath() . "/css/$file.css.php";
            $fallback = "./themes/" . PMA_Theme_Manager::FALLBACK_THEME .  "/css/$file.css.php";

            if (is_readable($path)) {
                echo "\n/* FILE: $file.css.php */\n";
                include $path;
            } else if (is_readable($fallback)) {
                echo "\n/* FILE: $file.css.php */\n";
                include $fallback;
            } else {
                $success = false;
            }
        }

        $_sprites_data_file = $this->getPath() . '/sprites.lib.php';
        $_sprites_css_file = './themes/sprites.css.php';
        if (is_readable($_sprites_data_file)) {
            include $_sprites_data_file;
            include $_sprites_css_file;
        }

        return $success;
    }

    /**
     * prints out the preview for this theme
     *
     * @return void
     * @access public
     */
    public function printPreview()
    {
        echo '<div class="theme_preview">';
        echo '<h2>' . htmlspecialchars($this->getName())
            .' (' . htmlspecialchars($this->getVersion()) . ')</h2>';
        echo '<p>';
        echo '<a target="_top" class="take_theme" '
            .'name="' . htmlspecialchars($this->getId()) . '" '
            . 'href="index.php'. PMA_generate_common_url(
                array('set_theme' => $this->getId())
            ) . '">';
        if (@file_exists($this->getPath() . '/screen.png')) {
            // if screen exists then output

            echo '<img src="' . $this->getPath() . '/screen.png" border="1"'
                .' alt="' . htmlspecialchars($this->getName()) . '"'
                .' title="' . htmlspecialchars($this->getName()) . '" /><br />';
        } else {
            echo __('No preview available.');
        }

        echo '[ <strong>' . __('take it') . '</strong> ]</a>'
            .'</p>'
            .'</div>';
    }

    /**
     * Remove filter for IE.
     *
     * @return string CSS code.
     */
    function getCssIEClearFilter()
    {
        return PMA_USR_BROWSER_AGENT == 'IE'
            && PMA_USR_BROWSER_VER >= 6
            && PMA_USR_BROWSER_VER <= 8
            ? 'filter: none'
            : '';
    }

    /**
     * Generates code for CSS gradient using various browser extensions.
     *
     * @param string $start_color Color of gradient start, hex value without #
     * @param string $end_color   Color of gradient end, hex value without #
     *
     * @return string CSS code.
     */
    function getCssGradient($start_color, $end_color)
    {
        $result = array();
        // Opera 9.5+, IE 9
        $result[] = 'background-image: url(./themes/svg_gradient.php?from=' . $start_color . '&to=' . $end_color . ');';
        $result[] = 'background-size: 100% 100%;';
        // Safari 4-5, Chrome 1-9
        $result[] = 'background: -webkit-gradient(linear, left top, left bottom, from(#' . $start_color . '), to(#' . $end_color . '));';
        // Safari 5.1, Chrome 10+
        $result[] = 'background: -webkit-linear-gradient(top, #' . $start_color . ', #' . $end_color . ');';
        // Firefox 3.6+
        $result[] = 'background: -moz-linear-gradient(top, #' . $start_color . ', #' . $end_color . ');';
        // IE 10
        $result[] = 'background: -ms-linear-gradient(top, #' . $start_color . ', #' . $end_color . ');';
        // Opera 11.10
        $result[] = 'background: -o-linear-gradient(top, #' . $start_color . ', #' . $end_color . ');';
        // IE 6-8
        if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 6 && PMA_USR_BROWSER_VER <= 8) {
            $result[] = 'filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#' . $start_color . '", endColorstr="#' . $end_color . '");';
        }
        return implode("\n", $result);
    }

    /**
     * Returns CSS styles for CodeMirror editor based on query formatter colors.
     *
     * @param boolean $generic Whether to include generic CodeMirror CSS as well
     *
     * @return string CSS code.
     */
    function getCssCodeMirror($generic = false)
    {
        if (! $GLOBALS['cfg']['CodemirrorEnable']) {
            return '';
        }

        $result[] = 'span.cm-keyword, span.cm-statement-verb {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['alpha_reservedWord'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-variable {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['alpha_identifier'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-comment {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['comment'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-mysql-string {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['quote'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-operator {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['punct'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-mysql-word {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['alpha_identifier'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-builtin {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['alpha_functionName'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-variable-2 {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['alpha_columnType'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-variable-3 {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['alpha_columnAttrib'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-separator {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['punct'] . ';';
        $result[] = '}';
        $result[] = 'span.cm-number {';
        $result[] = '    color: ' . $GLOBALS['cfg']['SQP']['fmtColor']['digit_integer'] . ';';
        $result[] = '}';

        if ($generic) {
            $height = ceil($GLOBALS['cfg']['TextareaRows'] * 1.2);
            $result[] = <<<EOT
.CodeMirror {
  font-size: 140%;
  font-family: monospace;
  background: #fff;
  border: 1px solid #000;
}

.CodeMirror-scroll {
  overflow: auto;
  height:   ${height}em;
  /* This is needed to prevent an IE[67] bug where the scrolled content
     is visible outside of the scrolling box. */
  position: relative;
}

.CodeMirror-gutter {
  position: absolute; left: 0; top: 0;
  z-index: 10;
  background-color: #f7f7f7;
  border-right: 1px solid #eee;
  min-width: 2em;
  height: 100%;
}
.CodeMirror-gutter-text {
  color: #aaa;
  text-align: right;
  padding: .4em .2em .4em .4em;
  white-space: pre !important;
}
.CodeMirror-lines {
  padding: .4em;
}

.CodeMirror pre {
  -moz-border-radius: 0;
  -webkit-border-radius: 0;
  -o-border-radius: 0;
  border-radius: 0;
  border-width: 0; margin: 0; padding: 0; background: transparent;
  font-family: inherit;
  font-size: inherit;
  padding: 0; margin: 0;
  white-space: pre;
  word-wrap: normal;
}

.CodeMirror-wrap pre {
  word-wrap: break-word;
  white-space: pre-wrap;
}
.CodeMirror-wrap .CodeMirror-scroll {
  overflow-x: hidden;
}

.CodeMirror textarea {
  font-family: inherit !important;
  font-size: inherit !important;
}

.CodeMirror-cursor {
  z-index: 10;
  position: absolute;
  visibility: hidden;
  border-left: 1px solid black !important;
}

.CodeMirror-focused .CodeMirror-cursor {
  visibility: visible;
}

span.CodeMirror-selected {
  background: #ccc !important;
  color: HighlightText !important;
}

.CodeMirror-focused span.CodeMirror-selected {
  background: Highlight !important;
}

.CodeMirror-matchingbracket {
    color: #0f0 !important;
}

.CodeMirror-nonmatchingbracket {
    color: #f22 !important;
}
EOT;
        }
        return implode("\n", $result);
    }
}
?>
