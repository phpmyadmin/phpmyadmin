#!/bin/sh
#
# $Id$ 
#
# 2001-08-08, swix@users.sourceforge.net:
# - created script
# - added release todo list
#

if [ $# != 1 ]
then
  echo "Usage: create-release.sh version"
  echo "  (no spaces allowed!)"
  echo ""
  echo "Example: create-release.sh 2.2.0-rc5"
  exit
fi

(mv tmp tmp-`date +%s`)
mkdir tmp
cd tmp
echo "Press [ENTER]!"
cvs -d:pserver:anonymous@cvs.phpmyadmin.sourceforge.net:/cvsroot/phpmyadmin login
cvs -z3 -d:pserver:anonymous@cvs.phpmyadmin.sourceforge.net:/cvsroot/phpmyadmin co phpMyAdmin

date > phpMyAdmin/RELEASE-DATE-$1
mv phpMyAdmin phpMyAdmin-$1
zip -9 -r phpMyAdmin-$1-php3.zip phpMyAdmin-$1
tar cvzf phpMyAdmin-$1-php3.tar.gz phpMyAdmin-$1
tar cvIf phpMyAdmin-$1-php3.tar.bz2 phpMyAdmin-$1
cd phpMyAdmin-$1
./scripts/extchg.sh php3 php
cd ..
zip -9 -r phpMyAdmin-$1-php.zip phpMyAdmin-$1
tar cvzf phpMyAdmin-$1-php.tar.gz phpMyAdmin-$1
tar cvIf phpMyAdmin-$1-php.tar.bz2 phpMyAdmin-$1

echo ""
echo ""
echo ""
echo "Files:"
echo "------"

ls -la *.gz *.zip *.bz2
cd ..

cat <<END

Todo now:
---------
1. upload the files to SF:
        ftp upload.sourceforge.net
        cd incoming
        mput tmp/*.gz *.zip
2. add files to SF files page (cut and paste changelog since last release)
3. add SF news item to phpMyAdmin project
4. update the download page: /home/groups/p/ph/phpmyadmin/htdocs
5. announce release on freshmeat (http://freshmeat.net/projects/phpmyadmin/)
6. announce release on http://phpwizard.net/phorum/list.php?f=1
7. send a short mail (with list of major changes) to 
        phpmyadmin-devel@lists.sourceforge.net
        phpmyadmin-news@lists.sourceforge.net
        phpmyadmin-users@lists.sourceforge.net
8. increment rc count or version in CVS : in defines.inc.php3
        the line    " define('PHPMYADMIN_VERSION', '2.2.0rc4'); "
9. the end :-)

END

