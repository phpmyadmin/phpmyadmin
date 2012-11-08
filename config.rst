.. _config:

Configuration
=============

**Warning for :abbr:`Mac (Apple Macintosh)` users:** PHP does not seem
to like :abbr:`Mac (Apple Macintosh)` end of lines character
("``\r``"). So ensure you choose the option that allows to use the
\*nix end of line character ("``\n``") in your text editor before
saving a script you have modified.

**Configuration note:** Almost all configurable data is placed in
``config.inc.php``. If this file does not exist, please refer to the
section to create one. This file only needs to contain the parameters
you want to change from their corresponding default value in
``libraries/config.default.php``.

The parameters which relate to design (like colors) are placed in
``themes/themename/layout.inc.php``. You might also want to create
*config.footer.inc.php* and *config.header.inc.php* files to add your
site specific code to be included on start and end of each page.

