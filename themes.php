<?php
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
/* Theme Select */
$path_to_themes = './' . $cfg['ThemePath'] . '/';
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?".">";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>phpMyAdmin - <?php echo ($strTheme ? $strTheme : 'Theme / Style'); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <style type="text/css">
        <!--
            body {
                font-family:      Verdana, Arial, Helvetica, sans-serif;
                font-size:        12px;
                background-color: #666699;
            }
            td {
                font-family:      Verdana, Arial, Helvetica, sans-serif;
                font-size:        12px;
            }
            th{
                font-family:      Verdana, Arial, Helvetica, sans-serif;
                font-size:        16px;
                font-weight:      bold;
            }
            a:hover{
                text-decoration:  none;
            }
            hr{
                color:            #000000;
                background-color: #000000;
                border:           0;
                height:           1px;
            }
            img{
                border:           1px solid #000000;
            }
        -->
        </style>
        <script language="JavaScript">
        <!--
            function takeThis(what){
                if (window.opener && window.opener.document.forms['setTheme'].elements['set_theme']) {
                    window.opener.document.forms['setTheme'].elements['set_theme'].value = what;
                    window.opener.document.forms['setTheme'].submit();
                    self.close();
                } else {
                    alert('No theme support, please check your configs!');
                    self.close();
                }
            }
        //-->
        </script>
    </head>

    <body bgcolor="#666699" text="#FFFFFF" link="#FF9900" vlink="#FF9900" alink="#FF9900" leftmargin="0" topmargin="0" marginwidth="3" marginheight="3">
        <table width="480" border="0" align="center" cellpadding="2" cellspacing="0">
            <tr>
         <th><b>phpMyAdmin - <?php echo ($strTheme ? $strTheme : 'Theme / Style'); ?></b></th>
            </tr>

            <tr><td>&nbsp;</td></tr>
<?php
    /*
    $org_theme_screen = $path_to_themes . 'original/screen.png';
    if(@file_exists($org_theme_screen)){ // check if original theme have a screen

            <tr>
                <td>
                    <?php
        echo '<b>ORIGINAL</b><br /><br />';
        echo '<div align="center"><img src="' . $org_theme_screen . '" border="0" alt="Original - Theme" />';
        echo '<script language="JavaScript"><!--' . "\n";
        echo '    document.write("<br />[ <b><a href=\"#top\" onclick=\"takeThis(\'original\'); return false;\">';
        echo (isset($strTakeIt) ? $strTakeIt : 'take it');
        echo '</a></b> ]");' . "\n";
        echo '//--></script></div><br />';
                    ?>
                </td>
            </tr>

    } // end original theme screen
    */
    if ($handleThemes = opendir($path_to_themes)) { // open themes
        while (false !== ($PMA_Theme = readdir($handleThemes))) {  // get screens
            if ($PMA_Theme != "." && $PMA_Theme != "..") { // && !strstr($PMA_Theme,'original')) { // but not the original
                $screen_directory = $path_to_themes . $PMA_Theme;
                if (is_dir($screen_directory) && @file_exists($screen_directory.'/screen.png')) { // if screen exists then output
?>
            <tr>
                <td><hr size="1" noshade="noshade" /></td>
            </tr>
            <tr>
                <td>
                    <?php
                    echo '<b>' . strtoupper(preg_replace("/_/"," ",$PMA_Theme)) . '</b><br /><br />';
                    echo '<div align="center"><img src="' . $screen_directory . '/screen.png" border="0" alt="' . strtoupper(preg_replace("/_/"," ",$PMA_Theme)) . ' - Theme" />';
                    echo '<script language="JavaScript"><!--' . "\n";
                    echo '    document.write("<br />[ <b><a href=\"#top\" onclick=\"takeThis(\'' . $PMA_Theme . '\'); return false;\">';
                    echo (isset($strTakeIt) ? $strTakeIt : 'take it');
                    echo '</a></b> ]");' . "\n";
                    echo '//--></script></div><br />';
                    ?>
                </td>
            </tr>
<?php   
                } // end 'screen output'
            } // end 'check theme'
        } // end 'get screens'
        closedir($handleThemes); 
    } // end 'open themes'
?>
        </table>
        <br />
    </body>
</html>
