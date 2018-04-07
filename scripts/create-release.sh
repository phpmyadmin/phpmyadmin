#!/bin/sh
#
# vim: expandtab sw=4 ts=4 sts=4:
#

# Do not run as CGI
if [ -n "$GATEWAY_INTERFACE" ] ; then
    echo 'Can not invoke as CGI!'
    exit 1
fi

# More documentation about making a release is available at:
# https://wiki.phpmyadmin.net/pma/Releasing

# Fail on undefined variables
set -u
# Fail on failure
set -e

KITS="all-languages english source"
COMPRESSIONS="zip-7z txz tgz"

# Process parameters

version=""
branch=""
do_tag=0
do_stable=0
do_test=0
do_ci=0
do_sign=1
do_pull=0
do_daily=0

while [ $# -gt 0 ] ; do
    case "$1" in
        --tag)
            do_tag=1
            ;;
        --stable)
            do_stable=1
            ;;
        --test)
            do_test=1
            ;;
        --daily)
            do_sign=0
            do_pull=1
            do_daily=1
            do_test=1
            ;;
        --ci)
            do_test=1
            do_ci=1
            if [ -z "$branch" ] ; then
                git branch ci
                branch="ci"
            fi
            version="ci"
            ;;
        --help)
            echo "Usages:"
            echo "  create-release.sh <version> <from_branch> [--tag] [--stable] [--test] [--ci]"
            echo ""
            echo "If --tag is specified, release tag is automatically created (use this for all releases including pre-releases)"
            echo "If --stable is specified, the STABLE branch is updated with this release"
            echo "If --test is specified, the testsuite is executed before creating the release"
            echo "If --ci is specified, the testsuite is executed and no actual release is created"
            echo ""
            echo "Examples:"
            echo "  create-release.sh 2.9.0-rc1 QA_2_9"
            echo "  create-release.sh 2.9.0 MAINT_2_9_0 --tag --stable"
            exit 65
            ;;
        *)
            if [ -z "$version" ] ; then
                version=`echo $1 | tr -d -c '0-9a-z.+-'`
                if [ "x$version" != "x$1" ] ; then
                    echo "Invalid version: $1"
                    exit 1
                fi
            elif [ -z "$branch" ] ; then
                branch=`echo $1 | tr -d -c '0-9A-Za-z_-'`
                if [ "x$branch" != "x$1" ] ; then
                    echo "Invalid branch: $1"
                    exit 1
                fi
            else
                echo "Unknown parameter: $1!"
                exit 1
            fi
    esac
    shift
done

if [ -z "$version" -o -z "$branch" ] ; then
    echo "Branch and version have to be specified!"
    exit 1
fi

# Checks whether remote branch has local tracking branch
ensure_local_branch() {
    if ! git branch | grep -q '^..'"$1"'$' ; then
        git branch --track $1 origin/$1
    fi
}

# Marks current head of given branch as head of other branch
# Used for STABLE tracking
mark_as_release() {
    branch=$1
    rel_branch=$2
    echo "* Marking release as $rel_branch"
    ensure_local_branch $rel_branch
    git checkout $rel_branch
    git merge -s recursive -X theirs $branch
    git checkout master
}

# Ensure we have tracking branch
ensure_local_branch $branch

# Check if we're releasing older
if git cat-file -e $branch:libraries/classes/Config.php 2> /dev/null ; then
    CONFIG_LIB=libraries/classes/Config.php
elif git cat-file -e $branch:libraries/Config.php 2> /dev/null ; then
    CONFIG_LIB=libraries/Config.php
else
    CONFIG_LIB=libraries/Config.class.php
fi

if [ $do_ci -eq 0 -a -$do_daily -eq 0 ] ; then
    cat <<END

Please ensure you have incremented rc count or version in the repository :
     - in $CONFIG_LIB Config::__constructor() the line
          " \$this->set( 'PMA_VERSION', '$version' ); "
     - in doc/conf.py the line
          " version = '$version' "
     - in README
     - set release date in ChangeLog

Continue (y/n)?
END
    read do_release

    if [ "$do_release" != 'y' ]; then
        exit 100
    fi
fi

# Create working copy
mkdir -p release
git worktree prune
workdir=release/phpMyAdmin-$version
if [ -d $workdir ] ; then
    echo "Working directory '$workdir' already exists, please move it out of way"
    exit 1
fi

# Add worktree with chosen branch
git worktree add --force $workdir $branch
cd $workdir
if [ $do_pull -eq 1 ] ; then
    git pull -q
fi
if [ $do_daily -eq 1 ] ; then
    git_head=`git log -n 1 --format=%H`
fi

# Check release version
if [ $do_ci -eq 0 -a -$do_daily -eq 0 ] ; then
    if ! grep -q "'PMA_VERSION', '$version'" $CONFIG_LIB ; then
        echo "There seems to be wrong version in $CONFIG_LIB!"
        exit 2
    fi
    if ! grep -q "version = '$version'" doc/conf.py ; then
        echo "There seems to be wrong version in doc/conf.py"
        exit 2
    fi
    if ! grep -q "Version $version\$" README ; then
        echo "There seems to be wrong version in README"
        exit 2
    fi
fi

# Cleanup release dir
LC_ALL=C date -u > RELEASE-DATE-${version}

# Building documentation
echo "* Generating documentation"
LC_ALL=C make -C doc html
find doc -name '*.pyc' -print0 | xargs -0 -r rm -f

# Check for gettext support
if [ -d po ] ; then
    echo "* Generating mo files"
    ./scripts/generate-mo
    if [ -f ./scripts/remove-incomplete-mo ] ; then
        echo "* Removing incomplete translations"
        ./scripts/remove-incomplete-mo
    fi
fi

if [ -f ./scripts/line-counts.sh ] ; then
    echo "* Generating line counts"
    ./scripts/line-counts.sh
fi

echo "* Removing unneeded files"

# Remove developer information
rm -rf .github

# Remove phpcs coding standard definition
rm -rf PMAStandard

# Testsuite setup
rm -f .travis.yml .coveralls.yml .scrutinizer.yml .jshintrc .weblate codecov.yml

# Remove readme for github
rm -f README.rst

if [ ! -d libraries/tcpdf ] ; then
    PHP_REQ=`sed -n '/"php"/ s/.*">=\([0-9]\.[0-9]\).*/\1/p' composer.json`
    if [ -z "$PHP_REQ" ] ; then
        echo "Failed to figure out required PHP version from composer.json"
        exit 2
    fi
    # Okay, there is no way to tell composer to install
    # suggested package. Let's require it and then revert
    # composer.json to original state.
    cp composer.json composer.json.backup
    echo "* Running composer"
    composer config platform.php "$PHP_REQ"
    composer update --no-dev
    composer require --update-no-dev tecnickcom/tcpdf pragmarx/google2fa bacon/bacon-qr-code samyoul/u2f-php-server
    mv composer.json.backup composer.json
    echo "* Cleanup of composer packages"
    rm -rf \
        vendor/phpmyadmin/sql-parser/tests/ \
        vendor/phpmyadmin/sql-parser/tools/ \
        vendor/phpmyadmin/sql-parser/locale/*/LC_MESSAGES/sqlparser.po \
        vendor/phpmyadmin/motranslator/tests/ \
        vendor/phpmyadmin/shapefile/tests/ \
        vendor/phpmyadmin/shapefile/examples/ \
        vendor/phpmyadmin/shapefile/data/ \
        vendor/phpseclib/phpseclib/phpseclib/File/ \
        vendor/phpseclib/phpseclib/phpseclib/Math/ \
        vendor/phpseclib/phpseclib/phpseclib/Net/ \
        vendor/phpseclib/phpseclib/phpseclib/System/ \
        vendor/symfony/cache/Tests/ \
        vendor/symfony/expression-language/Tests/ \
        vendor/symfony/expression-language/Resources/ \
        vendor/tecnickcom/tcpdf/examples/ \
        vendor/tecnickcom/tcpdf/tools/ \
        vendor/tecnickcom/tcpdf/fonts/ae_fonts_*/ \
        vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.33/ \
        vendor/tecnickcom/tcpdf/fonts/freefont-*/ \
        vendor/tecnickcom/tcpdf/include/sRGB.icc \
        vendor/twig/extensions/doc \
        vendor/twig/extensions/test \
        vendor/twig/twig/doc \
        vendor/twig/twig/test \
        vendor/google/recaptcha/examples/ \
        vendor/google/recaptcha/tests/
    find vendor/phpseclib/phpseclib/phpseclib/Crypt/ -maxdepth 1 -type f -not -name AES.php -not -name Base.php -not -name Random.php -not -name Rijndael.php -print0 | xargs -0 rm
    find vendor/tecnickcom/tcpdf/fonts/ -maxdepth 1 -type f -not -name 'dejavusans.*' -not -name 'dejavusansb.*' -not -name 'helvetica.php' -print0 | xargs -0 rm
    if [ $do_tag -eq 1 ] ; then
        echo "* Commiting composer.lock"
        git add --force composer.lock
        git commit -s -m "Adding composer lock for $version"
    fi
fi

if [ -f package.json ] ; then
    echo "* Running Yarn"
    yarn install --production
fi

# Remove git metadata
rm .git
find . -name .gitignore -print0 | xargs -0 -r rm -f
find . -name .gitattributes -print0 | xargs -0 -r rm -f

if [ $do_test -eq 1 ] ; then
    composer update
    ./vendor/bin/phpunit --configuration phpunit.xml.nocoverage --exclude-group selenium
    test_ret=$?
    if [ $do_ci -eq 1 ] ; then
        cd ../..
        rm -rf $workdir
        git worktree prune
        if [ "$branch" = "ci" ] ; then
            git branch -D ci
        fi
        exit $test_ret
    fi
    if [ $test_ret -ne 0 ] ; then
        exit $test_ret
    fi
    # Remove libs installed for testing
    rm -rf build
    composer update --no-dev
fi


cd ..

# Prepare all kits
for kit in $KITS ; do
    # Copy all files
    name=phpMyAdmin-$version-$kit
    cp -r phpMyAdmin-$version $name

    # Cleanup translations
    cd phpMyAdmin-$version-$kit
    ./scripts/lang-cleanup.sh $kit

    # Remove tests, source code,...
    if [ $kit != source ] ; then
        echo "* Removing source files"
        # Testsuite
        rm -rf test/
        rm phpunit.xml.* build.xml
        # Gettext po files
        rm -rf po/
        # Documentation source code
        mv doc/html htmldoc
        rm -rf doc
        mkdir doc
        mv htmldoc doc/html
        rm doc/html/.buildinfo doc/html/objects.inv
        # Javascript sources
        rm -rf js/vendor/openlayers/src/
        rm -rf node_modules
    fi

    # Remove developer scripts
    rm -rf scripts

    cd ..

    # Remove tar file possibly left from previous run
    rm -f $name.tar

    # Prepare distributions
    for comp in $COMPRESSIONS ; do
        case $comp in
            tbz|tgz|txz)
                if [ ! -f $name.tar ] ; then
                    echo "* Creating $name.tar"
                    tar --owner=root --group=root --numeric-owner --sort=name -cf $name.tar $name
                fi
                if [ $comp = txz ] ; then
                    echo "* Creating $name.tar.xz"
                    xz -9k $name.tar
                fi
                if [ $comp = tgz ] ; then
                    echo "* Creating $name.tar.gz"
                    gzip -9c $name.tar > $name.tar.gz
                fi
                ;;
            zip-7z)
                echo "* Creating $name.zip"
                7za a -bd -tzip $name.zip $name > /dev/null
                ;;
            *)
                echo "WARNING: ignoring compression '$comp', not known!"
                ;;
        esac
    done


    # Cleanup
    rm -f $name.tar
    # Remove directory with current dist set
    rm -rf $name
done

# Cleanup
rm -rf phpMyAdmin-${version}
git worktree prune

# Signing of files with default GPG key
echo "* Signing files"
for file in phpMyAdmin-$version-*.gz phpMyAdmin-$version-*.zip phpMyAdmin-$version-*.xz ; do
    if [ $do_sign -eq 1 ] ; then
        gpg --detach-sign --armor $file
    fi
    sha1sum $file > $file.sha1
    sha256sum $file > $file.sha256
done

if [ $do_daily -eq 1 ] ; then
    cat > phpMyAdmin-${version}.json << EOT
{
    "date": "`date --iso-8601=seconds`",
    "commit": "$git_head"
}
EOT
    exit 0
fi


echo ""
echo ""
echo ""
echo "Files:"
echo "------"

ls -la *.gz *.zip *.xz

cd ..

# Tag as release
if [ $do_tag -eq 1 ] ; then
    echo
    echo "Additional tasks:"
    tagname=RELEASE_`echo $version | tr . _ | tr '[:lower:]' '[:upper:]' | tr -d -`
    echo "* Tagging release as $tagname"
    git tag -s -a -m "Released $version" $tagname $branch
    echo "   Dont forget to push tags using: git push --tags"
    echo "* Cleanup of $branch"
    # Remove composer.lock, but we need to create fresh worktree for that
    git worktree add --force $workdir $branch
    cd $workdir
    git rm --force composer.lock
    git commit -s -m "Removing composer.lock"
    cd ../..
    rm -rf $workdir
    git worktree prune
fi

# Mark as stable release
if [ $do_stable -eq 1 ] ; then
    mark_as_release $branch STABLE
fi

cat <<END


Todo now:
---------

 1. Push the new tag upstream, with a command like git push origin --tags

 2. prepare a release/phpMyAdmin-$version-notes.html explaining in short the goal of
    this release and paste into it the ChangeLog for this release, followed
    by the notes of all previous incremental versions (i.e. 4.4.9 through 4.4.0)

 3. upload the files to our file server, use scripts/upload-release, eg.:

        ./scripts/upload-release $version release

 4. add a news item to our website; a good idea is to include a link to the release notes such as https://www.phpmyadmin.net/files/4.4.10/

 5. send a short mail (with list of major changes) to
        developers@phpmyadmin.net
        news@phpmyadmin.net

    Don't forget to update the Description section in the announcement,
    based on documentation.

 6. increment rc count or version in the repository :
        - in $CONFIG_LIB Config::__constructor() the line
              " \$this->set( 'PMA_VERSION', '2.7.1-dev' ); "
        - in Documentation.html (if it exists) the 2 lines
              " <title>phpMyAdmin 2.2.2-rc1 - Documentation</title> "
              " <h1>phpMyAdmin 2.2.2-rc1 Documentation</h1> "
        - in doc/conf.py (if it exists) the line
              " version = '2.7.1-dev' "

 7. on https://github.com/phpmyadmin/phpmyadmin/milestones close the milestone corresponding to the released version (if this is a stable release) and open a new one for the next minor release

 8. for a major release, update demo/php/versions.ini in the scripts repository so that the demo server shows current versions

 9. in case of a new major release ('y' in x.y.0), update the pmaweb/settings.py in website repository to include the new major releases

10. update the Dockerfile in the docker repository to reflect the new version and create a new annotated tag (such as with git tag -a 4.7.9-1 -m "Version 4.7.9-1")

END
