
README FILE FOR THE XAMPP THEME USED WITH PHPMAYADMIN
----------------------------------------------------------------------

CHANGE LOG:

    - 2007-09-20:
        - hover colors and backcolors in print css
        - id #selflink is hidden in print css
        - font-sizes in print css are same as in right css
        - inline tables (for comment column) in print view 
          without frames and smaller font
        - Supporting phpMyAdmin Version 2.11.x and higher
        - New Icons
        - Some specials for main.php and querywindow.php
          (please see source of theme_right.css.php)
        - static nav-panel and static server-info
        - $pma_http_url = '';
          set here your absolute url to the 'artic_ocean' theme
          directory (if required)

----------------------------------------------------------------------

1. INSTALLATION
----------------------------------------------------------------------
   Simply unzip the files.
   (sample: [whatever]/phpMyAdmin/themes/)

   One each .css you'll find in first line <?php $pma_http_url = ''; ?>.
   Here you can (if required) the url to the 'xampp' theme.
   This may fix some problems with relative urls.

   Then make sure, that all images are in the directory
   - [whatever]/phpMyAdmin/themes/xampp/img/

   and all *.css.php files are in the directory
   - [whatever]/phpMyAdmin/themes/xampp/css/.

   The two *.inc.php files must stored in the directory
   - [whatever]/phpMyAdmin/themes/xampp/.
			
  Note:
    [whatever] is any path to your phpMyAdmin-Installation.

----------------------------------------------------------------------

2. REQUIREMENTS / INFORMATIONS
----------------------------------------------------------------------
   - phpMyAdmin Version 2.9.x or higher
   - full CSS2 compatible browser
     I've tested with
       - Firefox 2.0.0.3
       - Microsoft(R) InternetExplorer 6.0 (some bad png's)
       - Microsoft(R) InternetExplorer 7.0
   - Your browser should support Javascript
     and png-images.

----------------------------------------------------------------------

3. INFORMATION ABOUT THE XAMPP THEME:
----------------------------------------------------------------------
   a) ICONS:
      Database Icon-Set made 2005-2007 by Michael Keck.
      Updated 2007-05-10 by Michael Keck

      The icons b_dbsock.png and db_client.png are from the nuvola icons
      and made by David Vignoni
      http://www.icon-king.com
      http://mail.icon-king.com/mailman/listinfo/nuvola_icon-king.com
      Copyright (c)  2003-2004  David Vignoni.
      
      Please see license.txt file for more informations.

   b) THEME:
      The theme is based on the 'darkblue_orange' theme made by the
      members of the phpMyAdmin Team.
      Modification and integration of the 'darkblue_orange' theme and
      the new Database Icon-Set is made by
      Michael Keck.
