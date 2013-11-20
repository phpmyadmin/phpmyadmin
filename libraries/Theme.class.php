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
        'rte',
        'codemirror',
        'jqplot',
        'resizable-menu'
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
     * @return PMA_Theme|false
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
        // try current theme first
        if (is_dir($this->getPath() . '/img/')) {
            $this->setImgPath($this->getPath() . '/img/');
            return true;
        }

        // try fallback theme
        $fallback = $GLOBALS['cfg']['ThemePath'] . '/'
            . PMA_Theme_Manager::FALLBACK_THEME
            . '/img/';
        if (is_dir($fallback)) {
            $this->setImgPath($fallback);
            return true;
        }

        // we failed
        trigger_error(
            sprintf(
                __('No valid image path for theme %s found!'),
                $this->getName()
            ),
            E_USER_ERROR
        );
        return false;
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
     * Returns the path to image for the theme.
     * If filename is given, it possibly fallbacks to fallback
     * theme for it if image does not exist.
     *
     * @param string $file file name for image
     *
     * @access public
     * @return string image path for this theme
     */
    public function getImgPath($file = null)
    {
        if (is_null($file)) {
            return $this->img_path;
        } else {
            if (is_readable($this->img_path . $file)) {
                return $this->img_path . $file;
            } else {
                return $GLOBALS['cfg']['ThemePath'] . '/'
                    . PMA_Theme_Manager::FALLBACK_THEME . '/img/' . $file;
            }
        }
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

        if ($GLOBALS['text_dir'] === 'ltr') {
            $right = 'right';
            $left = 'left';
        } else {
            $right = 'left';
            $left = 'right';
        }

        foreach ($this->_cssFiles as $file) {
            $path = $this->getPath() . "/css/$file.css.php";
            $fallback = "./themes/"
                . PMA_Theme_Manager::FALLBACK_THEME .  "/css/$file.css.php";

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

        include './themes/sprites.css.php';

        return $success;
    }

    /**
     * Renders the preview for this theme
     *
     * @return string
     * @access public
     */
    public function getPrintPreview()
    {
        $url_params = array('set_theme' => $this->getId());
        $url = 'index.php'. PMA_URL_getCommon($url_params);

        $retval  = '<div class="theme_preview">';
        $retval .= '<h2>';
        $retval .= htmlspecialchars($this->getName());
        $retval .= ' (' . htmlspecialchars($this->getVersion()) . ') ';
        $retval .= '</h2>';
        $retval .= '<p>';
        $retval .= '<a class="take_theme" ';
        $retval .= 'name="' . htmlspecialchars($this->getId()) . '" ';
        $retval .=  'href="' . $url . '">';
        if (@file_exists($this->getPath() . '/screen.png')) {
            // if screen exists then output
            $retval .= '<img src="' . $this->getPath() . '/screen.png" border="1"';
            $retval .= ' alt="' . htmlspecialchars($this->getName()) . '"';
            $retval .= ' title="' . htmlspecialchars($this->getName()) . '" />';
            $retval .= '<br />';
        } else {
            $retval .= __('No preview available.');
        }
        $retval .= '[ <strong>' . __('take it') . '</strong> ]';
        $retval .= '</a>';
        $retval .= '</p>';
        $retval .= '</div>';
        return $retval;
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
     * Gets currently configured font size.
     *
     * @return String with font size.
     */
    function getFontSize()
    {
        $fs = $GLOBALS['PMA_Config']->get('fontsize');
        if (!is_null($fs)) {
            return $fs;
        }
        if (isset($_COOKIE['pma_fontsize'])) {
            return $_COOKIE['pma_fontsize'];
        }
        return '82%';
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
        $result[] = 'background-image: url(./themes/svg_gradient.php?from='
            . $start_color . '&to=' . $end_color . ');';
        $result[] = 'background-size: 100% 100%;';
        // Safari 4-5, Chrome 1-9
        $result[] = 'background: '
            . '-webkit-gradient(linear, left top, left bottom, from(#'
            . $start_color . '), to(#' . $end_color . '));';
        // Safari 5.1, Chrome 10+
        $result[] = 'background: -webkit-linear-gradient(top, #'
            . $start_color . ', #' . $end_color . ');';
        // Firefox 3.6+
        $result[] = 'background: -moz-linear-gradient(top, #'
            . $start_color . ', #' . $end_color . ');';
        // IE 10
        $result[] = 'background: -ms-linear-gradient(top, #'
            . $start_color . ', #' . $end_color . ');';
        // Opera 11.10
        $result[] = 'background: -o-linear-gradient(top, #'
            . $start_color . ', #' . $end_color . ');';
        // IE 6-8
        if (PMA_USR_BROWSER_AGENT == 'IE'
            && PMA_USR_BROWSER_VER >= 6
            && PMA_USR_BROWSER_VER <= 8
        ) {
            $result[] = 'filter: '
                . 'progid:DXImageTransform.Microsoft.gradient(startColorstr="#'
                . $start_color . '", endColorstr="#' . $end_color . '");';
        }
        return implode("\n", $result);
    }
}
?>
