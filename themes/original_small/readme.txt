
    README FILE FOR THE ORIGINAL SMALL THEME USED WITH PHPMAYADMIN
----------------------------------------------------------------------

CHANGE LOG:
    - 2006-09-20;
      > Supporting phpMyAdmin Version 2.9.0-rc1 and higher

1. INFORMATION ABOUT THIS THEME:
----------------------------------------------------------------------
   a) THEME:
      The theme is based on the 'Original' theme made by the
      members of the phpMyAdmin Team. This theme is simply the Original 
      Theme but with small icons and checkboxes, so there fits more one page.
      Modifications are made by Ruben Barkow. (www.eclabs.de)
      
   b) ICONS:
      The Icons are the original Icons from The "Original" theme but they are resized by the browser.
      The Icons have a height of 16 but are resized to a height of 13 so they look a bit squeezed.

   c) USAGE:
      The best effect you get if you use a Font size of "70%" in the front screen of phpMyAdmin 

   d) QUESTIONS:
      I posted this theme in the PMA-theme-area, you`ll find additional info there:
      http://sourceforge.net/tracker/index.php?func=detail&aid=1561967&group_id=23067&atid=689412

2. INSTALLATION
----------------------------------------------------------------------
   Simply unzip the files.
   (sample: [whatever]/phpMyAdmin/themes/)

   make sure, that all files are in the directory
   - [whatever]/phpMyAdmin/themes/original_small/.
			
  Note:
    [whatever] is any path to your phpMyAdmin-Installation.

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


3. REQUIREMENTS / INFORMATIONS
----------------------------------------------------------------------
   - phpMyAdmin Version 2.9.0-rc1 or higher
   - full CSS2 compatible browser
     (I've tested with Firefox 1.5 and Microsoft(R) InternetExplorer 6.0)
   - Your browser should support Javascript
     and png-images.

----------------------------------------------------------------------

   
