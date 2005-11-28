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
     * returns theme object loaded from given folder
     * or false if theme is invalid
     * 
     * @static
     * @param   string  path to theme
     * @return  object  PMA_Theme
     */
    function load( $folder ) {
        if ( ! file_exists( $folder . '/info.inc.php' ) ) {
            return false;
        }
        
        @include( $folder . '/info.inc.php' );

        // did it set correctly?
        if ( ! isset( $theme_name ) ) {
            return false;
        }
        
        $theme = new PMA_Theme();
        
        $theme->setPath( $folder );

        if ( isset( $theme_full_version ) ) {
            $theme->setVersion( $theme_full_version );
        } elseif ( isset( $theme_generation, $theme_version ) ) {
            $theme->setVersion( $theme_generation . '.' . $theme_version );
        }
        $theme->setName( $theme_name );
        
        if ( is_dir( $theme->getPath() . 'img/' ) ) {
            $theme->setImgPath( $theme->getPath() . 'img/' );
        } elseif ( is_dir( $GLOBALS['cfg']['ThemePath'] . '/original/img/' ) ) {
            $theme->setImgPath( $GLOBALS['cfg']['ThemePath'] . '/original/img/' );
        } else {
            $GLOBALS['PMA_errors'][] = 
                sprintf( $GLOBALS['strThemeNoValidImgPath'], $theme_name );
            trigger_error(
                sprintf( $GLOBALS['strThemeNoValidImgPath'], $theme_name ),
                E_USER_WARNING );
        }
        
        return $theme;
    }
    
    function getPath() {
        return $this->path;
    }
    
    function setPath( $path ) {
        $this->path = trim( $path );
    }

    /**
     * sets version
     * @param   string new version
     */
    function setVersion( $version ) {
        $this->version = trim( $version );
    }
    
    /**
     * sets name
     * @param   string  $name   new name
     */
    function setName( $name ) {
        $this->name = trim( $name );
    }

    /**
     * returns name
     * @return  string name
     */
    function getName() {
        return $this->name;
    }

    /**
     * sets id
     * @param   string  $id   new id
     */
    function setId( $id ) {
        $this->id = trim( $id );
    }

    /**
     * returns id
     * @return  string id
     */
    function getId() {
        return $this->id;
    }

    function setImgPath( $path ) {
        $this->img_path = $path;
    }

    function getImgPath() {
        return $this->img_path;
    }
}

?>