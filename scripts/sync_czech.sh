#!/bin/sh
#
# Shell script that synchronises all czech translations using iso-8859-2 as basic

echo -n "Creating windows-1250 ... "
iconv -f iso8859-2 -t windows-1250 czech-iso.inc.php3| sed 's/iso-8859-2/windows-1250/' > czech-win1250.inc.php3
echo done
echo -n "Creating utf-8 ... "
iconv -f iso8859-2 -t utf-8 czech-iso.inc.php3| sed -e 's/iso-8859-2/utf-8/' -e '/\$charset/a\
$allow_recoding = TRUE;' > czech-utf8.inc.php3
echo done

