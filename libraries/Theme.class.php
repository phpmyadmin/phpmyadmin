<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA_Theme class
 *
 * @package PhpMyAdmin
 */

/**
 * handles theme
 *
 * @todo add the possibility to make a theme depend on another theme and by default on original
 * @todo make all components optional - get missing components from 'parent' theme
 * @todo make css optionally replacing 'parent' css or extending it (by appending at the end)
 * @todo add an optional global css file - which will be used for both frames
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
     * @var array   valid css types
     * @access  protected
     */
    var $types = array('left', 'right', 'print');

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
     * @access  public
     * @return  boolean     whether loading them info was successful or not
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
     * @static
     * @access  public
     * @param string  $folder path to theme
     * @return  object  PMA_Theme
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
     * checks image path for existance - if not found use img from original theme
     *
     * @access  public
     * @return bool
     */
    function checkImgPath()
    {
        if (is_dir($this->getPath() . '/img/')) {
            $this->setImgPath($this->getPath() . '/img/');
            return true;
        } elseif (is_dir($GLOBALS['cfg']['ThemePath'] . '/original/img/')) {
            $this->setImgPath($GLOBALS['cfg']['ThemePath'] . '/original/img/');
            return true;
        } else {
            trigger_error(
                sprintf(__('No valid image path for theme %s found!'), $this->getName()),
                E_USER_ERROR);
            return false;
        }
    }

    /**
     * returns path to theme
     *
     * @access  public
     * @return  string  $path   path to theme
     */
    function getPath()
    {
        return $this->path;
    }

    /**
     * returns layout file
     *
     * @access  public
     * @return  string  layout file
     */
    function getLayoutFile()
    {
        return $this->getPath() . '/layout.inc.php';
    }

    /**
     * set path to theme
     *
     * @access  public
     * @param string  $path   path to theme
     */
    function setPath($path)
    {
        $this->path = trim($path);
    }

    /**
     * sets version
     *
     * @access  public
     * @param string new version
     */
    function setVersion($version)
    {
        $this->version = trim($version);
    }

    /**
     * returns version
     *
     * @access  public
     * @return  string  version
     */
    function getVersion()
    {
        return $this->version;
    }

    /**
     * checks theme version agaisnt $version
     * returns true if theme version is equal or higher to $version
     *
     * @access  public
     * @param string  $version    version to compare to
     * @return  boolean
     */
    function checkVersion($version)
    {
        return version_compare($this->getVersion(), $version, 'lt');
    }

    /**
     * sets name
     *
     * @access  public
     * @param string  $name   new name
     */
    function setName($name)
    {
        $this->name = trim($name);
    }

    /**
     * returns name
     *
     * @access  public
     * @return  string name
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * sets id
     *
     * @access  public
     * @param string  $id   new id
     */
    function setId($id)
    {
        $this->id = trim($id);
    }

    /**
     * returns id
     *
     * @access  public
     * @return  string  id
     */
    function getId()
    {
        return $this->id;
    }

    /**
     * @access  public
     * @param string  path to images for this theme
     */
    function setImgPath($path)
    {
        $this->img_path = $path;
    }

    /**
     * @access  public
     * @return  string image path for this theme
     */
    function getImgPath()
    {
        return $this->img_path;
    }

    /**
     * load css (send to stdout, normally the browser)
     *
     * @access  public
     * @param string  $type   left, right or print
     * @return bool
     */
    function loadCss(&$type)
    {
        if (empty($type) || ! in_array($type, $this->types)) {
            $type = 'left';
        }

        if ($type == 'right') {
            echo PMA_SQP_buildCssData();
        }

        $_css_file = $this->getPath()
                   . '/css/theme_' . $type . '.css.php';

        if (! file_exists($_css_file)) {
            return false;
        }

        if ($GLOBALS['text_dir'] === 'ltr') {
            $right = 'right';
            $left = 'left';
        } else {
            $right = 'left';
            $left = 'right';
        }

        include $_css_file;

        if ($type != 'print') {
            $_sprites_data_file = $this->getPath() . '/sprites.lib.php';
            $_sprites_css_file = './themes/sprites.css.php';
            if (   (file_exists($_sprites_data_file)  && is_readable($_sprites_data_file))
                && (file_exists($_sprites_css_file) && is_readable($_sprites_css_file))
            ) {
                include $_sprites_data_file;
                include $_sprites_css_file;
            }
        }

        return true;
    }

    /**
     * prints out the preview for this theme
     *
     * @access  public
     */
    function printPreview()
    {
        echo '<div class="theme_preview">';
        echo '<h2>' . htmlspecialchars($this->getName())
            .' (' . htmlspecialchars($this->getVersion()) . ')</h2>';
        echo '<p>';
        echo '<a target="_top" class="take_theme" '
            .'name="' . htmlspecialchars($this->getId()) . '" '
            . 'href="index.php'.PMA_generate_common_url(array(
                'set_theme' => $this->getId()
                )) . '">';
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
    function getCssIEClearFilter() {
        return PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 6 && PMA_USR_BROWSER_VER <= 8
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
     * @return string CSS code.
     */
    function getCssCodeMirror()
    {
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
        return implode("\n", $result);
    }
}
?>
