#!/bin/sh
#
# $Id$
# vim: expandtab sw=4 ts=4 sts=4:
#

KITS="all-languages english"
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
  2. checked that all language files are valid (use
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

# Remove test directory from package to avoid Path disclosure messages
# if someone runs /test/wui.php and there are test failures
rm -rf phpMyAdmin/test

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

cat <<END


Todo now:
---------
 1. tag the subversion tree with the new revision number for a plain release
    or a release candidate:
    version 2.7.0 gets two tags: RELEASE_2_7_0 and STABLE
    version 2.7.1-rc1 gets RELEASE_2_7_1RC1 and TESTING

 2. prepare a phpMyAdmin-xxx-notes.html explaining in short the goal of
    this release and paste into it the ChangeLog for this release
 3. upload the files and the notes file to SF (procedure explained on the sf.net Project Admin/File Manager help page)
 4. add SF news item to phpMyAdmin project
 5. announce release on freshmeat (http://freshmeat.net/projects/phpmyadmin/)
 6. send a short mail (with list of major changes) to
        phpmyadmin-devel@lists.sourceforge.net
        phpmyadmin-news@lists.sourceforge.net
        phpmyadmin-users@lists.sourceforge.net

    Don't forget to update the Description section in the announcement,
    based on Documentation.html.

 7. increment rc count or version in subversion :
        - in libraries/Config.class.php PMA_Config::__constructor() the line
              " $this->set( 'PMA_VERSION', '2.7.1-dev' ); "
        - in Documentation.html the 2 lines
              " <title>phpMyAdmin 2.2.2-rc1 - Documentation</title> "
              " <h1>phpMyAdmin 2.2.2-rc1 Documentation</h1> "
        - in translators.html

 8. add a group for bug tracking this new version, at
    https://sourceforge.net/tracker/admin/index.php?group_id=23067&atid=377408&add_group=1

 9. the end :-)

END

fi
