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
  echo "Example: create-release.sh 2.2.2-rc1"
  exit
fi


cat <<END

Please ensure you have:
  1. incremented rc count or version in CVS :
     - in libraries/defines.lib.php3 the line
          " define('PHPMYADMIN_VERSION', '$1'); "
     - in Documentation.html the line
          " <h1>phpMyAdmin $1 Documentation</h1> "
  2. built the new "Documentation.txt" version using the Lynx "print" command
     on the "Documentation.html" file.

Continue (y/n)?
END
printf "\a"
read do_release

if [ $do_release != 'y' ]
then
  exit
fi


(mv cvs cvs-`date +%s`)
mkdir cvs
cd cvs
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
chmod -R 775 cvs


cat <<END


Todo now:
---------
 1. tag the cvs tree with the new revision number for a plain release or a
    release candidate
 2. upload the files to SF:
        ftp upload.sourceforge.net
        cd incoming
        mput cvs/*.gz *.zip *.bz2
 3. add files to SF files page (cut and paste changelog since last release)
 4. add SF news item to phpMyAdmin project
 5. update the download page: /home/groups/p/ph/phpmyadmin/htdocs
 6. announce release on freshmeat (http://freshmeat.net/projects/phpmyadmin/)
 7. announce release on http://phpwizard.net/phorum/list.php?f=1
 8. send a short mail (with list of major changes) to 
        phpmyadmin-devel@lists.sourceforge.net
        phpmyadmin-news@lists.sourceforge.net
        phpmyadmin-users@lists.sourceforge.net
 9. increment rc count or version in CVS :
        - in libraries/defines.lib.php3 the line
              " define('PHPMYADMIN_VERSION', '2.2.2-rc1'); "
        - in Documentation.html the line
              " <h1>phpMyAdmin 2.2.2-rc1 Documentation</h1> "
10. update the demo subdirectory:
        - in htdocs, cvs update phpMyAdmin
        - and don't forget to give write rights for the updated scripts to the
          whole group
11. the end :-)

END

