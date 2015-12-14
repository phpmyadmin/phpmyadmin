
    README FILE FOR THE VERY SMALL THEME USED WITH PHPMYADMIN
----------------------------------------------------------------------

CHANGE LOG:
    - 2007-04-22:
      > Supporting phpMyAdmin Version 2.10.1-rc1
      > Fixed the text of selected (marked) and hovered lines (they weren't visible)
      > Fixed the startup page icons
      > Fixed the "Open new window" missing icon (it is from the "Original" theme)
      > The theme does not depend on the Arctic Ocean
      > Removed the $pma_http_url = ''; parameter because it is not neccessary any more
    - 2005-09-12;
      > Supporting phpMyAdmin Version 2.6.4 and higher
    - 2005-09-11:
      > $pma_http_url = '';
        set here your absolute url to the theme
        directory (if required)

1. INSTALLATION
----------------------------------------------------------------------
   Simply unzip the files.
   (sample: /phpMyAdmin/themes/)

1b. OPTIONAL
-----------------------------------------------------------------------
  in your config.inc.php you should set the configuration 
	$cfg['LeftDisplayLogo']       =FALSE;
  to save space on the left frame at the top

  Optional for Internet Explorer:
	In mozilla it looks very small now ;)
	but in Internet Explorer the new style is not valid so it is ignored (but its
	still smaller than the original already)
	
	To get an even smaller view in IE, you need a small hack:
	(use an editor with a replace function where you can 
	replace text inside all documents inside your PMA folder [whatever])
	
	1. 
	replace all occurrences of 
	`type="checkbox" `
	 ----------------
	with
	`type="checkbox" class="inpcheck" `
	 ---------------------------------
	(take care that you also replace the spaces after the quotes)

	
	2. 
	replace all  occurrences of 
	`height="16"`
	 -----------
	with
	`height="14"`
	 -----------

----------------------------------------------------------------------


2. REQUIREMENTS / INFORMATIONS
----------------------------------------------------------------------
   - phpMyAdmin Version 2.6.2 or higher
   - full CSS2 compatible browser
     (I've tested with Firefox 1.02, Microsoft(R) 
      InternetExplorer 6.0, and Opera 7.54 and Opera 9.20)
   - Your browser should support Javascript
     and png-images.

----------------------------------------------------------------------


3. INFORMATION ABOUT THE ARCTIC-OCEAN THEME:
----------------------------------------------------------------------
   a) ICONS:
      Database Icon-Set made 2005 by Michael Keck.
      Please see license.txt file for more informations.
   b) THEME:
      The theme is based on the 'arctic ocean' theme by Michael Keck 
	  which is based on the 'darkblue_orange' theme made by the
      members of the phpMyAdmin Team.
      Modification and integration of the 'darkblue_orange' theme and
      the new Database Icon-Set is made by
      Michael Keck and Ruben Barkow.
