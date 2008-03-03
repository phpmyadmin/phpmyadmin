
    README FILE FOR THE ARTIC-OCEAN THEME USED WITH PHPMAYADMIN
----------------------------------------------------------------------

CHANGE LOG:

    - 2008-03-03:
      > fixed bug #1901687 (undefined variable 'type')

    - 2007-05-11:
      > fixed bugs #1688536
        - fixed: resizing on each reload the width of left frame
        - fixed: centered table columns in print view
        - fixed: hover colors and background colors in print view
      > changes
        - hover colors and backcolors in print css
        - id #selflink is hidden in print css
        - font-sizes in print css are same as in right css
        - inline tables (for comment column) in print view 
          without frames and smaller font

    - 2007-05-10:
      > Supporting phpMyAdmin Version 2.9.x and higher
      > New Icons for some list elements
      > Some specials for main.php and querywindow.php
        (please see source of theme_right.css.php)
      > Static nav-panel and static server-info
      > reversed server-info / nav-panel

    - 2005-09-12:
      > Supporting phpMyAdmin Version 2.6.4 and higher

    - 2005-09-11:
      > $pma_http_url = '';
        set here your absolute url to the 'artic_ocean' theme
        directory (if required)

----------------------------------------------------------------------

1. INSTALLATION
----------------------------------------------------------------------
   Simply unzip the files.
   (sample: [whatever]/phpMyAdmin/themes/)

   One each .css you'll find in first line <?php $pma_http_url = ''; ?>.
   Here you can (if required) the url to the 'artic_ocean' theme.
   This may fix some problems with relative urls.

   Then make sure, that all images are in the directory
   - [whatever]/phpMyAdmin/themes/arctic_ocean/img/

   and all *.css.php files are in the directory
   - [whatever]/phpMyAdmin/themes/arctic_ocean/css/.

   The two *.inc.php files must stored in the directory
   - [whatever]/phpMyAdmin/themes/arctic_ocean/.
			
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

3. INFORMATION ABOUT THE ARCTIC-OCEAN THEME:
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
