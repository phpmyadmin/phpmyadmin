#!/bin/sh
#
# $Id$
# vim: expandtab sw=4 ts=4 sts=4:
#

# Fail on undefined variables
set -u
# Fail on failure
set -e

KITS="all-languages english"
COMPRESSIONS="zip-7z tbz tgz 7z"

if [ $# -lt 2 ]
then
  echo "Usages:"
  echo "  create-release.sh <version> <from_branch>"
  echo ""
  echo "Examples:"
  echo "  create-release.sh 2.9.0-rc1 QA_2_9"
  echo "  create-release.sh 2.9.0 MAINT_2_9_0 TAG"
  exit 65
fi


# Read required parameters
version=$1
shift
branch=$1
shift

# Create working copy
mkdir -p release
workdir=release/phpMyAdmin-$version
if [ -d $workdir ] ; then
    echo "Working directory '$workdir' already exists, please move it out of way"
    exit 1
fi
git clone --local . $workdir
cd $workdir

# Checkout branch
git checkout $branch

# Check release version
if ! grep -q "'PMA_VERSION', '$version'" libraries/Config.class.php ; then
    echo "There seems to be wrong version in libraries/Config.class.php!"
    exit 2
fi
if ! grep -q "phpMyAdmin $version - Documentation" Documentation.html ; then
    echo "There seems to be wrong version in Documentation.html"
    exit 2
fi
if ! grep -q "phpMyAdmin $version - Official translators" translators.html ; then
    echo "There seems to be wrong version in translators.html"
    exit 2
fi
if ! grep -q "Version $version\$" README ; then
    echo "There seems to be wrong version in README"
    exit 2
fi

cat <<END

Please ensure you have:
  1. incremented rc count or version in subversion :
     - in libraries/Config.class.php PMA_Config::__constructor() the line
          " \$this->set( 'PMA_VERSION', '$version' ); "
     - in Documentation.html the 2 lines
          " <title>phpMyAdmin $version - Documentation</title> "
          " <h1>phpMyAdmin $version Documentation</h1> "
     - in translators.html
     - in README
  2. checked that all language files are valid (use
     the "./scripts/check_lang.php" script to do it).

Continue (y/n)?
END
read do_release

if [ "$do_release" != 'y' ]; then
    exit 100
fi


# Cleanup release dir
LC_ALL=C date -u > RELEASE-DATE-${version}

# Building Documentation.txt
LC_ALL=C w3m -dump Documentation.html > Documentation.txt

# Remove test directory from package to avoid Path disclosure messages
# if someone runs /test/wui.php and there are test failures
rm -rf test

# Remove git metadata
rm -rf .git

cd ..

# Prepare all kits
for kit in $KITS ; do
    # Copy all files
	name=phpMyAdmin-$version-$kit
	cp -r phpMyAdmin-$version $name

	# Cleanup translations
    cd phpMyAdmin-$version-$kit
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
rm -rf phpMyAdmin-${version}


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
              " \$this->set( 'PMA_VERSION', '2.7.1-dev' ); "
        - in Documentation.html the 2 lines
              " <title>phpMyAdmin 2.2.2-rc1 - Documentation</title> "
              " <h1>phpMyAdmin 2.2.2-rc1 Documentation</h1> "
        - in translators.html

 8. add a group for bug tracking this new version, at
    https://sourceforge.net/tracker/admin/index.php?group_id=23067&atid=377408&add_group=1

 9. the end :-)

END
