<?php

/* Theme Select */

// TODO: maybe move this one level up, to be able to require
//       the language files
/**
 * Gets some core libraries and displays a top message if required
 */
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?".">";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
<?php /*
        <title>phpMyAdmin - <?php echo $GLOBALS['strTheme']; ?></title>
      */?>
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
       <?php /*
            <tr>
         <th><b>phpMyAdmin - <?php echo $GLOBALS['strTheme']; ?></b></th>
            </tr>
             */  ?>

            <tr><td>&nbsp;</td></tr>
<?php
    if(@file_exists('./original/screen.png')){ // check if original theme hav a screen
?>
            <tr>
                <td>
                    <?php
        echo '<b>ORIGINAL</b><br /><br />';
        echo '<div align="center"><img src="./original/screen.png" border="0" alt="Original - Theme" />';
        echo '<script language="JavaScript"><!--' . "\n";
        echo '    document.write("<br />[ <b><a href=\"#top\" onclick=\"takeThis(\'original\'); return false;\">';
        echo (isset($strTakeIt) ? $strTakeIt : 'take it');
        echo '</a></b> ]");' . "\n";
        echo '//--></script></div><br />';
                    ?>
                </td>
            </tr>
<?php
    } // end original theme screen
    if ($handleThemes = opendir('./')) { // open themes
        while (false !== ($PMA_Theme = readdir($handleThemes))) {  // get screens
            if ($PMA_Theme != "." && $PMA_Theme != ".." && $PMA_Theme != 'original') { // but not the original
                if (is_dir('./'.$PMA_Theme) && @file_exists('./'.$PMA_Theme.'/screen.png')) { // if screen exists then output
?>
            <tr>
                <td><hr size="1" noshade="noshade" /></td>
            </tr>
            <tr>
                <td>
                    <?php
                    echo '<b>' . strtoupper(preg_replace("/_/"," ",$PMA_Theme)) . '</b><br /><br />';
                    echo '<div align="center"><img src="./'.$PMA_Theme.'/screen.png" border="0" alt="' . strtoupper(preg_replace("/_/"," ",$PMA_Theme)) . ' - Theme" />';
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
            } // end 'check if not original'
        } // end 'get screens'
        closedir($handleThemes); 
    } // end 'open themes'
?>
        </table>
        <br />
    </body>
</html>
