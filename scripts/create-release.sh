#!/bin/sh
#
# $Id$
#
# 2003-01-17, rabus@users.sourceforge.net:
# - Changed the CVS hostname to cvs1 because cvs1.sourceforge.net is now blocked
#   for the SF shell servers, too. Note: The script now works on the SF shell
#   servers ONLY!
#
# 2002-11-22, rabus@users.sourceforge.net:
# - changed the CVS server dns to cvs1.sourceforge.net
#   (cvs.phpmyadmin.sourceforge.net does not work at the SF shell anymore).
#
# 2002-10-03, rabus@users.sourceforge.net:
# - more detailed instructions
#
# 2002-09-08, robbat2@users.sourceforge.net:
# - Tweaked final instruction list
#
# 2002-06-17, lem9@users.sourceforge.net:
# - I option to tar for bzip2 is deprecated, use j
#
# 2002-27-04, loic@phpmyadmin.net:
# - added the cvs branch feature
#
# 2001-08-08, swix@users.sourceforge.net:
# - created script
# - added release todo list
#


if [ $# == 0 ]
then
  echo "Usage: create-release.sh version from_branch"
  echo "  (no spaces allowed!)"
  echo ""
  echo "Example: create-release.sh 2.2.7-rc1 v2_2_7-branch"
  exit 65
fi

if [ $# == 1 ]
then
  branch=''
fi
if [ $# == 2 ]
then
  branch="-r $2"
fi


cat <<END

Please ensure you have:
  1. incremented rc count or version in CVS :
     - in libraries/defines_php.lib.php3 the line
          " define('PMA_VERSION', '$1'); "
     - in Documentation.html the 2 lines
          " <title>phpMyAdmin $1 - Documentation</title> "
          " <h1>phpMyAdmin $1 Documentation</h1> "
     - in translators.html
  2. built the new "Documentation.txt" version using:
       lynx --dont_wrap_pre --nolist --dump Documentation.html > Documentation.txt
  3. synchronized the language files:
       cd lang
       ./sync_lang.sh
     and checked all language files are valid (use
     the "./scripts/check_lang.php3" script to do it).

Continue (y/n)?
END
printf "\a"
read do_release

if [ $do_release != 'y' ]
then
  exit
fi


if [ -e cvs ];
then
    mv cvs cvs-`date +%s`
fi
mkdir cvs
cd cvs
echo "Press [ENTER]!"
cvs -d:pserver:anonymous@cvs1:/cvsroot/phpmyadmin login
cvs -z3 -d:pserver:anonymous@cvs1:/cvsroot/phpmyadmin co -P $branch phpMyAdmin

date > phpMyAdmin/RELEASE-DATE-$1
mv phpMyAdmin phpMyAdmin-$1
zip -9 -r phpMyAdmin-$1-php3.zip phpMyAdmin-$1
tar cvzf phpMyAdmin-$1-php3.tar.gz phpMyAdmin-$1
tar cvjf phpMyAdmin-$1-php3.tar.bz2 phpMyAdmin-$1
cd phpMyAdmin-$1
./scripts/extchg.sh php3 php
cd ..
zip -9 -r phpMyAdmin-$1-php.zip phpMyAdmin-$1
tar cvzf phpMyAdmin-$1-php.tar.gz phpMyAdmin-$1
tar cvjf phpMyAdmin-$1-php.tar.bz2 phpMyAdmin-$1

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
        binary
        mput cvs/*.gz *.zip *.bz2
 3. add files to SF files page (cut and paste changelog since last release)
 4. add SF news item to phpMyAdmin project
 5. update the download page: /home/groups/p/ph/phpmyadmin/htdocs
 6. announce release on freshmeat (http://freshmeat.net/projects/phpmyadmin/)
 7. send a short mail (with list of major changes) to
        phpmyadmin-devel@lists.sourceforge.net
        phpmyadmin-news@lists.sourceforge.net
        phpmyadmin-users@lists.sourceforge.net
 8. increment rc count or version in CVS :
        - in libraries/defines_php.lib.php3 the line
              " define('PHPMYADMIN_VERSION', '2.2.2-rc1'); "
        - in Documentation.html the 2 lines
              " <title>phpMyAdmin 2.2.2-rc1 - Documentation</title> "
              " <h1>phpMyAdmin 2.2.2-rc1 Documentation</h1> "
        - in translators.html
 9. the end :-)

END

# Removed due to not needed thanks to clever scripting by Robbat2
# 9. update the demo subdirectory:
#        - in htdocs, cvs update phpMyAdmin
#        - and don't forget to give write rights for the updated scripts to the
#          whole group
