#!/bin/sh
#
# Shell script that synchronises all english translations using iso-8859-1 as basic

echo -n "Creating utf-8 ... "
iconv -f iso8859-1 -t utf-8 english.inc.php3| sed -e 's/iso-8859-1/utf-8/' -e '/\$charset/a\
$allow_recoding = TRUE;' > english-utf8.inc.php3
echo done

