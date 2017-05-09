.. _themes:

Custom Themes
=============

phpMyAdmin comes with support for third party themes. You can download
additonal themes from our website at <https://www.phpmyadmin.net/themes/>.

Configuration
-------------

Themes are configured with :config:option:`$cfg['ThemeManager']` and
:config:option:`$cfg['ThemeDefault']`.  Under :file:`./themes/`, you should not
delete the directory ``pmahomme`` or its underlying structure, because this is
the system theme used by phpMyAdmin. ``pmahomme`` contains all images and
styles, for backwards compatibility and for all themes that would not include
images or css-files.  If :config:option:`$cfg['ThemeManager']` is enabled, you
can select your favorite theme on the main page. Your selected theme will be
stored in a cookie.

Creating custom theme
---------------------

To create a theme:

* make a new subdirectory (for example "your\_theme\_name") under :file:`./themes/`.
* copy the files and directories from ``pmahomme`` to "your\_theme\_name"
* edit the css-files in "your\_theme\_name/css"
* put your new images in "your\_theme\_name/img"
* edit :file:`layout.inc.php` in "your\_theme\_name"
* edit :file:`info.inc.php` in "your\_theme\_name" to contain your chosen
  theme name, that will be visible in user interface
* make a new screenshot of your theme and save it under
  "your\_theme\_name/screen.png"

In theme directory there is file :file:`info.inc.php` which contains theme
verbose name, theme generation and theme version. These versions and
generations are enumerated from 1 and do not have any direct
dependence on phpMyAdmin version. Themes within same generation should
be backwards compatible - theme with version 2 should work in
phpMyAdmin requiring version 1. Themes with different generation are
incompatible.

If you do not want to use your own symbols and buttons, remove the
directory "img" in "your\_theme\_name". phpMyAdmin will use the
default icons and buttons (from the system-theme ``pmahomme``).
