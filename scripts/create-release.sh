#!/bin/sh
#
# $Id$
#
# 2004-04-16, lem9@users.sourceforge.net:
# - daily snapshot when called with first parameter "snapshot"
# - remove directory used for the checkout
#
# 2003-11-18, nijel@users.sourceforge.net:
# - switch php3 -> php
#
# 2003-10-10, nijel@users.sourceforge.net:
# - cvsserver set on just one place to ease testing
# - echoes md5 sums to include on download page
#
# 2003-06-22, robbat2@users.sourceforge.net:
# - Moved to using updatedocs.sh for updating documentation
# - Make tarring faster by re-arranging ops
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

cvsserver=cvs1

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

if [ $1 == "snapshot" ]
then
  mode="snapshot"
  date_snapshot=`date +%Y%m%d-%H%M%S`
fi

# Set target name
if [ "$mode" != "snapshot" ]
then
 target=$1
else
 target=$date_snapshot
fi


if [ "$mode" != "snapshot" ]
then

 cat <<END

Please ensure you have:
  1. incremented rc count or version in CVS :
     - in libraries/defines.lib.php the line
          " define('PMA_VERSION', '$1'); "
     - in Documentation.html the 2 lines
          " <title>phpMyAdmin $1 - Documentation</title> "
          " <h1>phpMyAdmin $1 Documentation</h1> "
     - in translators.html
     - in README
  2. synchronized the language files:
       cd lang
       ./sync_lang.sh
     and checked all language files are valid (use
     the "./scripts/check_lang.php" script to do it).

Continue (y/n)?
END
 printf "\a"
 read do_release

 if [ "$do_release" != 'y' ]; then
   exit
 fi
fi

# Move old cvs dir
if [ -e cvs ];
then
    mv cvs cvs-`date +%s`
fi
# Do CVS checkout
mkdir cvs
cd cvs

if [ "$mode" != "snapshot" ]
then
 echo "Press [ENTER]!"
 cvs -q -d:pserver:anonymous@$cvsserver:/cvsroot/phpmyadmin login
 if [ $? -ne 0 ] ; then
     echo "CVS login failed, bailing out"
     exit 1
 fi
fi

cvs -q -z3 -d:pserver:anonymous@$cvsserver:/cvsroot/phpmyadmin co -P $branch phpMyAdmin

if [ $? -ne 0 ] ; then
    echo "CVS checkout failed, bailing out"
    exit 2
fi

# Cleanup release dir
LC_ALL=C date -u > phpMyAdmin/RELEASE-DATE-${target}

# Olivier asked to keep those in the cvs release, to allow testers to use
# cvs update on it
if [ "$mode" != "snapshot" ]
then
 find phpMyAdmin \( -name .cvsignore -o -name CVS \) -print0 | xargs -0 rm -rf
fi

find phpMyAdmin -type d -print0 | xargs -0 chmod 755
find phpMyAdmin -type f -print0 | xargs -0 chmod 644
find phpMyAdmin \( -name '*.sh' -o -name '*.pl' \) -print0 | xargs -0 chmod 755

# Building Documentation.txt
lynx --dont_wrap_pre --nolist --dump phpMyAdmin/Documentation.html > phpMyAdmin/Documentation.txt

# Renaming directory
 mv phpMyAdmin phpMyAdmin-$target

# Building distribution kits
zip -9 -r phpMyAdmin-${target}.zip phpMyAdmin-${target}
tar cvf phpMyAdmin-${target}.tar phpMyAdmin-${target}
bzip2 -9kv phpMyAdmin-${target}.tar
gzip -9v phpMyAdmin-${target}.tar

# Cleanup
rm -rf phpMyAdmin-${target}

if [ "$mode" != "snapshot" ]
then


echo ""
echo ""
echo ""
echo "Files:"
echo "------"

ls -la *.gz *.zip *.bz2

echo
echo "MD5 sums:"
echo "--------"

md5sum *.{gz,zip,bz2} | sed "s/\([^ ]*\)[ ]*\([^ ]*\)/\$md5sum['\2'] = '\1';/"

echo
echo "Sizes:"
echo "------"

ls -l --block-size=k *.{gz,zip,bz2} | sed -r "s/[a-z-]+[[:space:]]+[0-9]+[[:space:]]+[^[:space:]]+[[:space:]]+[^[:space:]]+[[:space:]]+([0-9]*)K.*[[:space:]]([^[:space:]]+)\$/\$size['\2'] = \1;/"

echo
echo "Add these to /home/groups/p/ph/phpmyadmin/htdocs/home_page/files.inc.php on sf"

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
 5. update web page:
        - add MD5s and file sizes to /home/groups/p/ph/phpmyadmin/htdocs/home_page/files.inc.php
        - add release to /home/groups/p/ph/phpmyadmin/htdocs/home_page/config.inc.php
 6. announce release on freshmeat (http://freshmeat.net/projects/phpmyadmin/)
 7. send a short mail (with list of major changes) to
        phpmyadmin-devel@lists.sourceforge.net
        phpmyadmin-news@lists.sourceforge.net
        phpmyadmin-users@lists.sourceforge.net
 8. increment rc count or version in CVS :
        - in libraries/defines.lib.php the line
              " define('PHPMYADMIN_VERSION', '2.2.2-rc1'); "
        - in Documentation.html the 2 lines
              " <title>phpMyAdmin 2.2.2-rc1 - Documentation</title> "
              " <h1>phpMyAdmin 2.2.2-rc1 Documentation</h1> "
        - in translators.html
 9. the end :-)

END

fi

cd ..
find cvs -type d -print0 | xargs -0 chmod 775
find cvs -type f -print0 | xargs -0 chmod 664

# Removed due to not needed thanks to clever scripting by Robbat2
# 9. update the demo subdirectory:
#        - in htdocs, cvs update phpMyAdmin
#        - and don't forget to give write rights for the updated scripts to the
#          whole group
