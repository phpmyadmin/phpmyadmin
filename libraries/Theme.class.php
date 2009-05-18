<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA_Theme class
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * handles theme
 *
 * @todo add the possibility to make a theme depend on another theme and by default on original
 * @todo make all components optional - get missing components from 'parent' theme
 * @todo make css optionally replacing 'parent' css or extending it (by appending at the end)
 * @todo add an optional global css file - which will be used for both frames
 *
 * @package phpMyAdmin
 */
class PMA_Theme {
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
     * @uses    PMA_Theme::getPath()
     * @uses    PMA_Theme::$mtime_info
     * @uses    PMA_Theme::setVersion()
     * @uses    PMA_Theme::setName()
     * @uses    filemtime()
     * @uses    filesize()
     * @uses    file_exists()
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
     * @uses    PMA_Theme
     * @uses    PMA_Theme::setPath()
     * @uses    PMA_Theme::loadInfo()
     * @uses    PMA_Theme::checkImgPath()
     * @param   string  $folder path to theme
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
     * @uses    PMA_Theme::getPath()
     * @uses    PMA_Theme::setImgPath()
     * @uses    PMA_Theme::getName()
     * @uses    $GLOBALS['cfg']['ThemePath']
     * @uses    $GLOBALS['strThemeNoValidImgPath']
     * @uses    is_dir()
     * @uses    sprintf()
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
                sprintf($GLOBALS['strThemeNoValidImgPath'], $this->getName()),
                E_USER_ERROR);
            return false;
        }
    }

    /**
     * returns path to theme
     *
     * @access  public
     * @uses    PMA_Theme::$path    as return value
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
     * @uses    PMA_Theme::getPath()
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
     * @uses    PMA_Theme::$path    to set it
     * @param   string  $path   path to theme
     */
    function setPath($path)
    {
        $this->path = trim($path);
    }

    /**
     * sets version
     *
     * @access  public
     * @uses    PMA_Theme::$version
     * @param   string new version
     */
    function setVersion($version)
    {
        $this->version = trim($version);
    }

    /**
     * returns version
     *
     * @access  public
     * @uses    PMA_Theme::$version
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
     * @uses    version_compare()
     * @uses    PMA_Theme::getVersion()
     * @param   string  $version    version to compare to
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
     * @uses    PMA_Theme::$name to set it
     * @uses    trim()
     * @param   string  $name   new name
     */
    function setName($name)
    {
        $this->name = trim($name);
    }

    /**
     * returns name
     *
     * @access  public
     * @uses    PMA_Theme::$name    as return value
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
     * @uses    PMA_Theme::$id to set it
     * @param   string  $id   new id
     */
    function setId($id)
    {
        $this->id = trim($id);
    }

    /**
     * returns id
     *
     * @access  public
     * @uses    PMA_Theme::$id as return value
     * @return  string  id
     */
    function getId()
    {
        return $this->id;
    }

    /**
     * @access  public
     * @uses    PMA_Theme::$img_path to set it
     * @param   string  path to images for this theme
     */
    function setImgPath($path)
    {
        $this->img_path = $path;
    }

    /**
     * @access  public
     * @uses    PMA_Theme::$img_path as retunr value
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
     * @uses    PMA_Theme::getPath()
     * @uses    PMA_Theme::$types
     * @uses    PMA_SQP_buildCssData()
     * @uses    file_exists()
     * @uses    in_array()
     * @param   string  $type   left, right or print
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
        return true;
    }

    /**
     * prints out the preview for this theme
     *
     * @access  public
     * @uses    PMA_Theme::getName()
     * @uses    PMA_Theme::getVersion()
     * @uses    PMA_Theme::getId()
     * @uses    PMA_Theme::getPath()
     * @uses    $GLOBALS['strThemeNoPreviewAvailable']
     * @uses    $GLOBALS['strTakeIt']
     * @uses    PMA_generate_common_url()
     * @uses    addslashes()
     * @uses    file_exists()
     * @uses    htmlspecialchars()
     */
    function printPreview()
    {
        echo '<div class="theme_preview">';
        echo '<h2>' . htmlspecialchars($this->getName())
            .' (' . htmlspecialchars($this->getVersion()) . ')</h2>'
            .'<p>'
            .'<a target="_top" href="index.php'
            .PMA_generate_common_url(array('set_theme' => $this->getId())) . '"'
            .' onclick="takeThis(\'' . addslashes($this->getId()) . '\');'
            .' return false;">';
        if (@file_exists($this->getPath() . '/screen.png')) {
            // if screen exists then output

            echo '<img src="' . $this->getPath() . '/screen.png" border="1"'
                .' alt="' . htmlspecialchars($this->getName()) . '"'
                .' title="' . htmlspecialchars($this->getName()) . '" /><br />';
        } else {
            echo $GLOBALS['strThemeNoPreviewAvailable'];
        }

        echo '[ <strong>' . $GLOBALS['strTakeIt'] . '</strong> ]</a>'
            .'</p>'
            .'</div>';
    }
}
?>
