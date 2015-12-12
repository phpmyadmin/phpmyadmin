<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme ClearView3
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage ClearView3
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>

body#body_leftFrame{padding:10px;margin:0;background:<?php echo (isset($_SESSION['tmp_user_values']['custom_color']) ? $_SESSION['tmp_user_values']['custom_color'] : $GLOBALS['cfg']['MainBackground']); ?> url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>frame_fone.png) repeat-y right;color:<?php echo $GLOBALS['cfg']['NaviColor'] ?>;font-family:<?php echo $GLOBALS['cfg']['FontFamily'] ?>;font-size: <?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : $_COOKIE['pma_fontsize']);?>;}
input,
select,
textarea{font-size:1em;}
a img{border:none}
a:link,
a:visited{color:#483D8B}
a:hover{color:#A0522D}
hr{color:#ccc;background:#ccc;border:none;height:1px}
/* classes
********************************************************************** */
div#pmalogo{text-align:center;border-bottom:1px solid #ccc;padding-bottom:0.5em}
div#leftframelinks{text-align:center;padding:0.5em 0;border-bottom:1px solid #ccc}
div#leftframelinks a:hover{background:none}
div#leftframelinks a img.icon{padding:0.2em 0.3em;border:none}
ul#databaseList{font-size:95%;border:none;padding:0.5em 0 0;margin:0;list-style:none}
ul#databaseList li{background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_db.png) no-repeat 0 0.2em;padding:0.2em 0 0.2em 17px}
div#databaseList form{padding-top:0.5em;font-size:85%}
ul#databaseList a{text-decoration:none}
div#left_tableList ul{background:none;font-size:0.9em;padding:0;margin:0;list-style:none}
div#left_tableList ul li{padding-bottom:1px}
/* marked items */
div#left_tableList{font-size:95%}
div#left_tableList a.item{font-weight:bold}
div#left_tableList ul li a{text-decoration:none}
div#left_tableList li{white-space:nowrap}
div#left_tableList > ul li.marked > a,
div#left_tableList > ul li.marked{font-weight:bold;background:none;text-decoration:none;color:#333}
div#left_tableList > ul li:hover > a,
div#left_tableList > ul li:hover{background:none}