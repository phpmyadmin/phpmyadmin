#!/bin/sh
#
# $Id$
# vim: expandtab sw=4 ts=4 sts=4:
#
# 2005-09-13, lem9@users.sourceforge.net
# - no longer create a config.default.php from config.inc.php
#
# 2005-06-12, lem9@users.sourceforge.net
# - cvs server name changed to cvs, because cvs1 no longer works from
#   shell.sourceforge.net
#
# 2003-08-23, nijel@users.sourceforge.net:
# - support for creating snapshots outside sourceforge:
#    * cvs server name can be read from environment variable cvsserver
#    * do not change to directories as used on sourceforge if $2 is local
#
# 2003-08-13, nijel@users.sourceforge.net:
# - config.default -> config.default.php
#
# 2004-08-09, lem9@users.sourceforge.net:
# - remember to create a new bug tracking group
#
# 2004-06-07  rabus@users.sourceforge.net
# - create backup config file
#
# 2004-04-29, lem9@users.sourceforge.net:
# - keep only the previous cvs directory created
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

KITS="all-languages-utf-8-only all-languages english"
COMPRESSIONS="zip-7z tbz tgz 7z"

if [ $# = 0 ]
then
  echo "Usages:"
  echo "  create-release.sh <version> [from_branch]"
  echo "  create-release.sh snapshot [sf]"
  echo "  (no spaces allowed!)"
  echo ""
  echo "Examples:"
  echo "  create-release.sh 2.9.0-rc1 branches/QA_2_9"
  echo "  create-release.sh 2.9.0 tags/RELEASE_2_9_0"
  exit 65
fi

branch='trunk'

if [ "$1" = "snapshot" ] ; then
    mode="snapshot"
    date_snapshot=`date +%Y%m%d-%H%M%S`
    target=$date_snapshot
else
    if [ "$#" -ge 2 ] ; then
        branch="$2"
    fi
    target="$1"
    cat <<END

Please ensure you have:
  1. incremented rc count or version in subversion :
     - in libraries/Config.class.php PMA_Config::__constructor() the line
          " \$this->set( 'PMA_VERSION', '$1' ); "
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
    read do_release

    if [ "$do_release" != 'y' ]; then
        exit
    fi
fi

if [ "$mode" = "snapshot" -a "$2" = "sf" ] ; then
    # Goto project dir
    cd /home/groups/p/ph/phpmyadmin/htdocs

    # Keep one previous version of the cvs directory
    if [ -e svn-prev ] ; then
        rm -rf svn-prev
    fi
    mv svn svn-prev
fi

# Do SVNcheckout
mkdir -p ./svn 
cd svn

echo "Exporting repository from subversion"

svn export -q https://phpmyadmin.svn.sourceforge.net/svnroot/phpmyadmin/$branch/phpMyAdmin

if [ $? -ne 0 ] ; then
    echo "Subversion checkout failed, bailing out"
    exit 2
fi

# Cleanup release dir
LC_ALL=C date -u > phpMyAdmin/RELEASE-DATE-${target}

# Building Documentation.txt
LC_ALL=C w3m -dump phpMyAdmin/Documentation.html > phpMyAdmin/Documentation.txt

# Renaming directory
mv phpMyAdmin phpMyAdmin-$target

# Prepare all kits
for kit in $KITS ; do
    # Copy all files
	name=phpMyAdmin-$target-$kit
	cp -r phpMyAdmin-$target $name

	# Cleanup translations
    cd phpMyAdmin-$target-$kit
    scripts/lang-cleanup.sh $kit
    cd ..

    # Prepare distributions
    for comp in $COMPRESSIONS ; do
        case $comp in
            tbz|tgz)
                echo "Creating $name.tar"
                tar cf $name.tar $name
                if [ $comp = tbz ] ; then
                    echo "Creating $name.tar.bz2"
                    bzip2 -9k $name.tar
                fi
                if [ $comp = tgz ] ; then
                    echo "Creating $name.tar.gz"
                    gzip -9c $name.tar > $name.tar.gz
                fi
                rm $name.tar
                ;;
            zip)
                echo "Creating $name.zip"
                zip -q -9 -r $name.zip $name
                ;;
            zip-7z)
                echo "Creating $name.zip"
                7za a -bd -tzip $name.zip $name > /dev/null
                ;;
            7z)
                echo "Creating $name.7z"
                7za a -bd $name.7z $name > /dev/null
                ;;
            *)
                echo "WARNING: ignoring compression '$comp', not known!"
                ;;
        esac
    done

    # Remove directory with current dist set
    rm -rf $name
done

# Cleanup
rm -rf phpMyAdmin-${target}

if [ "$mode" != "snapshot" ]
then


echo ""
echo ""
echo ""
echo "Files:"
echo "------"

ls -la *.gz *.zip *.bz2 *.7z

echo
echo "MD5 sums:"
echo "--------"

md5sum *.{gz,zip,bz2,7z} | sed "s/\([^ ]*\)[ ]*\([^ ]*\)/\$md5sum['\2'] = '\1';/"

echo
echo "Sizes:"
echo "------"

ls -l --block-size=k *.{gz,zip,bz2,7z} | sed -r "s/[a-z-]+[[:space:]]+[0-9]+[[:space:]]+[^[:space:]]+[[:space:]]+[^[:space:]]+[[:space:]]+([0-9]*)K.*[[:space:]]([^[:space:]]+)\$/\$size['\2'] = \1;/"

echo
echo "Add these to /home/groups/p/ph/phpmyadmin/htdocs/home_page/files.inc.php on sf"

cat <<END


Todo now:
---------
 1. tag the subversion tree with the new revision number for a plain release
    or a release candidate:
    version 2.7.0 gets two tags: RELEASE_2_7_0 and STABLE
    version 2.7.1-rc1 gets RELEASE_2_7_1RC1 and TESTING

 2. upload the files to SF:
        ftp upload.sourceforge.net
        cd incoming
        binary
        mput svn/*.gz *.zip *.bz2
 3. add files to SF files page (cut and paste changelog since last release)
 4. add SF news item to phpMyAdmin project
 5. update web page:
        - add MD5s and file sizes to /home/groups/p/ph/phpmyadmin/htdocs/home_page/includes/list_files.inc.php
        - add release to /home/groups/p/ph/phpmyadmin/htdocs/home_page/includes/list_release.inc.php
 6. announce release on freshmeat (http://freshmeat.net/projects/phpmyadmin/)
 7. send a short mail (with list of major changes) to
        phpmyadmin-devel@lists.sourceforge.net
        phpmyadmin-news@lists.sourceforge.net
        phpmyadmin-users@lists.sourceforge.net

    Don't forget to update the Description section in the announcement,
    based on Documentation.html.

 8. increment rc count or version in subversion :
        - in libraries/Config.class.php PMA_Config::__constructor() the line
              " $this->set( 'PMA_VERSION', '2.7.1-dev' ); "
        - in Documentation.html the 2 lines
              " <title>phpMyAdmin 2.2.2-rc1 - Documentation</title> "
              " <h1>phpMyAdmin 2.2.2-rc1 Documentation</h1> "
        - in translators.html

 9. add a group for bug tracking this new version, at
    https://sourceforge.net/tracker/admin/index.php?group_id=23067&atid=377408&add_group=1

10. Visit http://phpmyadmin.net/home_page/version.php then copy the results to /home/groups/p/ph/phpmyadmin/htdocs/latest.txt. This is needed for users of the pre-2.8.0 scripts/upgrade.pl.

11. the end :-)

END

fi

# Removed due to not needed thanks to clever scripting by Robbat2
# 9. update the demo subdirectory:
#        - in htdocs, cvs update phpMyAdmin
#        - and don't forget to give write rights for the updated scripts to the
#          whole group
