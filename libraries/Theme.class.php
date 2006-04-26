<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

class PMA_Theme {
    /**
     * @var string version
     */
    var $version = '0.0.0.0';

    /**
     * @var string name
     */
    var $name = '';

    /**
     * @var string id
     */
    var $id = '';

    /**
     * @var string
     */
    var $path = '';

    /**
     * @var string
     */
    var $img_path = '';

    /**
     * @var array   valid css types
     */
    var $types = array('left', 'right', 'print');

    /**
     * @var integer last modification time for info file
     */
    var $mtime_info = 0;

    function __wakeup()
    {
        $this->loadInfo();
        $this->checkImgPath();
    }

    function loadInfo()
    {
        if (! file_exists($this->getPath() . '/info.inc.php')) {
            return false;
        }

        if ($this->mtime_info === filemtime($this->getPath() . '/info.inc.php')) {
            return true;
        }

        @include $this->getPath() . '/info.inc.php';

        // did it set correctly?
        if (! isset($theme_name)) {
            return false;
        }

        $this->mtime_info = filemtime($this->getPath() . '/info.inc.php');

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
     * @param   string  path to theme
     * @return  object  PMA_Theme
     */
    function load($folder)
    {

        $theme = new PMA_Theme();

        $theme->setPath($folder);

        if (! $theme->loadInfo()) {
            return false;
        }

        $theme->checkImgPath();

        return $theme;
    }

    function checkImgPath()
    {
        if (is_dir($this->getPath() . '/img/')) {
            $this->setImgPath($this->getPath() . '/img/');
            return true;
        } elseif (is_dir($GLOBALS['cfg']['ThemePath'] . '/original/img/')) {
            $this->setImgPath($GLOBALS['cfg']['ThemePath'] . '/original/img/');
            return true;
        } else {
            $GLOBALS['PMA_errors'][] =
                sprintf($GLOBALS['strThemeNoValidImgPath'], $this->getName());
            trigger_error(
                sprintf($GLOBALS['strThemeNoValidImgPath'], $this->getName()),
                E_USER_WARNING);
            return false;
        }
    }

    /**
     * returns path to theme
     * @uses    $this->$path    as return value
     * @return  string  $path   path to theme
     */
    function getPath()
    {
        return $this->path;
    }

    /**
     * returns layout file
     *
     * @return  string  layout file
     */
    function getLayoutFile()
    {
        return $this->getPath() . '/layout.inc.php';
    }

    /**
     * set path to theme
     * @uses    $this->$path    to set it
     * @param   string  $path   path to theme
     */
    function setPath($path)
    {
        $this->path = trim($path);
    }

    /**
     * sets version
     * @uses    $this->version
     * @param   string new version
     */
    function setVersion($version)
    {
        $this->version = trim($version);
    }

    /**
     * returns version
     * @uses    $this->version
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
     * @uses    version_compare()
     * @uses    $this->getVersion()
     * @param   string  $version    version to compare to
     * @return  boolean
     */
    function checkVersion($version)
    {
        return version_compare($this->getVersion(), $version, 'lt');
    }

    /**
     * sets name
     * @param   string  $name   new name
     */
    function setName($name)
    {
        $this->name = trim($name);
    }

    /**
     * returns name
     * @return  string name
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * sets id
     * @param   string  $id   new id
     */
    function setId($id)
    {
        $this->id = trim($id);
    }

    /**
     * returns id
     * @return  string id
     */
    function getId()
    {
        return $this->id;
    }

    function setImgPath($path)
    {
        $this->img_path = $path;
    }

    function getImgPath()
    {
        return $this->img_path;
    }

    /**
     * load css (send to stdout, normaly the browser)
     *
     * @uses    $this->getPath()
     * @uses    $this->types
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
     * @uses    $this->getName()
     * @uses    $this->getVersion()
     * @uses    $this->getId()
     * @uses    $this->getPath()
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