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
            do_test=1
            if [ -z "$version" ] ; then
                version=`echo $1 | tr -d -c '0-9a-z.+-'`
                if [ "x$version" != "x$1" ] ; then
                    echo "Invalid version: $1"
                    exit 1
                fi
            elif [ -z "$branch" ] ; then
                branch=`echo $1 | tr -d -c '/0-9A-Za-z_-'`
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

cleanup_composer_vendors() {
    echo "* Cleanup of composer packages"
    rm -rf \
        vendor/phpmyadmin/sql-parser/tests/ \
        vendor/phpmyadmin/sql-parser/tools/ \
        vendor/phpmyadmin/sql-parser/locale/sqlparser.pot \
        vendor/phpmyadmin/sql-parser/locale/*/LC_MESSAGES/sqlparser.po \
        vendor/phpmyadmin/sql-parser/bin/ \
        vendor/phpmyadmin/sql-parser/phpunit.xml.dist \
        vendor/phpmyadmin/motranslator/phpunit.xml.dist \
        vendor/phpmyadmin/motranslator/tests/ \
        vendor/phpmyadmin/shapefile/phpunit.xml \
        vendor/phpmyadmin/shapefile/tests/ \
        vendor/phpmyadmin/shapefile/examples/ \
        vendor/phpmyadmin/shapefile/data/ \
        vendor/phpmyadmin/twig-i18n-extension/README.rst \
        vendor/phpmyadmin/twig-i18n-extension/phpunit.xml.dist \
        vendor/phpmyadmin/twig-i18n-extension/test/ \
        vendor/phpseclib/phpseclib/phpseclib/File/ \
        vendor/phpseclib/phpseclib/phpseclib/Math/ \
        vendor/phpseclib/phpseclib/phpseclib/Net/ \
        vendor/phpseclib/phpseclib/phpseclib/System/ \
        vendor/phpseclib/phpseclib/appveyor.yml \
        vendor/symfony/cache/Tests/ \
        vendor/symfony/expression-language/Tests/ \
        vendor/symfony/expression-language/Resources/ \
        vendor/tecnickcom/tcpdf/examples/ \
        vendor/tecnickcom/tcpdf/tools/ \
        vendor/tecnickcom/tcpdf/fonts/ae_fonts_*/ \
        vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.34/ \
        vendor/tecnickcom/tcpdf/fonts/freefont-*/ \
        vendor/tecnickcom/tcpdf/include/sRGB.icc \
        vendor/tecnickcom/tcpdf/.git \
        vendor/tecnickcom/tcpdf/.github/ \
        vendor/bacon/bacon-qr-code/phpunit.xml.dist \
        vendor/bacon/bacon-qr-code/test/ \
        vendor/dasprid/enum/phpunit.xml.dist \
        vendor/dasprid/enum/test/ \
        vendor/williamdes/mariadb-mysql-kbs/phpunit.xml \
        vendor/williamdes/mariadb-mysql-kbs/test/ \
        vendor/williamdes/mariadb-mysql-kbs/schemas/ \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-raw.json \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-raw.md \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-slim.json \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-ultraslim.php \
        vendor/nikic/fast-route/.travis.yml \
        vendor/nikic/fast-route/.hhconfig \
        vendor/nikic/fast-route/FastRoute.hhi \
        vendor/nikic/fast-route/phpunit.xml \
        vendor/nikic/fast-route/psalm.xml \
        vendor/nikic/fast-route/test/ \
        vendor/twig/twig/doc/ \
        vendor/twig/twig/test/ \
        vendor/twig/twig/.github/ \
        vendor/twig/twig/README.rst \
        vendor/twig/twig/.travis.yml \
        vendor/twig/twig/.editorconfig \
        vendor/twig/twig/.php_cs.dist \
        vendor/twig/twig/drupal_test.sh \
        vendor/paragonie/constant_time_encoding/tests/ \
        vendor/paragonie/constant_time_encoding/psalm.xml \
        vendor/paragonie/constant_time_encoding/phpunit.xml.dist \
        vendor/paragonie/constant_time_encoding/.travis.yml \
        vendor/pragmarx/google2fa/phpstan.neon \
        vendor/pragmarx/google2fa-qrcode/.scrutinizer.yml \
        vendor/pragmarx/google2fa-qrcode/.travis.yml \
        vendor/pragmarx/google2fa-qrcode/phpunit.xml \
        vendor/pragmarx/google2fa-qrcode/tests \
        vendor/google/recaptcha/.travis.yml \
        vendor/google/recaptcha/phpunit.xml.dist \
        vendor/google/recaptcha/.github/ \
        vendor/google/recaptcha/examples/ \
        vendor/google/recaptcha/tests/
    rm -rf \
        vendor/google/recaptcha/CONTRIBUTING.md \
        vendor/phpmyadmin/motranslator/CODE_OF_CONDUCT.md \
        vendor/phpmyadmin/motranslator/CONTRIBUTING.md \
        vendor/phpmyadmin/motranslator/PERFORMANCE.md \
        vendor/phpmyadmin/shapefile/CONTRIBUTING.md \
        vendor/phpmyadmin/sql-parser/CODE_OF_CONDUCT.md \
        vendor/phpmyadmin/sql-parser/CONTRIBUTING.md
    find vendor/phpseclib/phpseclib/phpseclib/Crypt/ -maxdepth 1 -type f \
        -not -name AES.php \
        -not -name Base.php \
        -not -name Random.php \
        -not -name Rijndael.php \
        -print0 | xargs -0 rm
    find vendor/tecnickcom/tcpdf/fonts/ -maxdepth 1 -type f \
        -not -name 'dejavusans.*' \
        -not -name 'dejavusansb.*' \
        -not -name 'helvetica.php' \
        -print0 | xargs -0 rm
}

backup_vendor_folder() {
    TEMP_FOLDER="$(mktemp -d /tmp/phpMyAdmin.XXXXXXXXX)"
    cp -rp ./vendor "${TEMP_FOLDER}"
}

restore_vendor_folder() {
    if [ ! -d ${TEMP_FOLDER} ]; then
        echo 'No backup to restore'
        exit 1;
    fi
    rm -rf ./vendor
    mv "${TEMP_FOLDER}/vendor" ./vendor
    rmdir "${TEMP_FOLDER}"
}

get_composer_package_version() {
    awk '/require-dev/ {printline = 1; print; next } printline' composer.json | grep "$1" | awk -F [\"] '{print $4}'
}

create_phpunit_sandbox() {
    PHPUNIT_VERSION="$(get_composer_package_version 'phpunit/phpunit')"
    TEMP_PHPUNIT_FOLDER="$(mktemp -d /tmp/phpMyAdmin-phpunit.XXXXXXXXX)"
    cd "${TEMP_PHPUNIT_FOLDER}"
    composer require "phpunit/phpunit:${PHPUNIT_VERSION}"
    cd -
}

delete_phpunit_sandbox() {
    if [ ! -d ${TEMP_PHPUNIT_FOLDER} ]; then
        echo 'No phpunit sandbox to delete'
        exit 1;
    fi
    rm -rf "${TEMP_PHPUNIT_FOLDER}"
}

# Ensure we have tracking branch
ensure_local_branch $branch

VERSION_FILE=libraries/classes/Version.php

# Keep in sync with update-po script
fetchReleaseFromFile() {
    php -r "define('VERSION_SUFFIX', ''); require_once('libraries/classes/Version.php'); echo \PhpMyAdmin\Version::VERSION;"
}

echo "The actual configured release is: $(fetchReleaseFromFile)"

if [ $do_ci -eq 0 -a -$do_daily -eq 0 ] ; then
    cat <<END

Please ensure you have incremented rc count or version in the repository :
     - run ./scripts/console set-version $version
     - in $VERSION_FILE Version class:
        - check that VERSION, MAJOR, MINOR and PATCH are correct.
     - in doc/conf.py the line
          " version = '$version' "
     - in README the "Version" line
     - in package.json the line
          " "version": "$version", "
     - set release date in ChangeLog

Continue (y/n)?
END
    read do_release

    if [ "$do_release" != 'y' ]; then
        exit 100
    fi
fi

echo "The actual configured release is now: $(fetchReleaseFromFile)"

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
    git_head_short=`git log -n 1 --format=%h`
    today_date=`date +'%Y%m%d' -u`
fi

if [ $do_daily -eq 1 ] ; then
    echo '* setting the version suffix for the snapshot'
    sed -i "s/'VERSION_SUFFIX', '.*'/'VERSION_SUFFIX', '+$today_date.$git_head_short'/" libraries/vendor_config.php
    php -l libraries/vendor_config.php
fi

# Check release version
if [ $do_ci -eq 0 -a -$do_daily -eq 0 ] ; then
    if ! grep -q "VERSION = '$version'" $VERSION_FILE ; then
        echo "There seems to be wrong version in $VERSION_FILE!"
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
    if ! grep -q "\"version\": \"$version\"," package.json ; then
        echo "There seems to be wrong version in package.json"
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
rm -rf .github CODE_OF_CONDUCT.md DCO

# Testsuite setup
rm -f .travis.yml .scrutinizer.yml .jshintrc .weblate codecov.yml

# Remove Doctum config file
rm -f test/doctum-config.php

# Remove readme for github
rm -f README.rst

if [ -f ./scripts/console ]; then
    # Update the vendors to have the dev vendors
    composer update --no-interaction
    # Warm up the routing cache for 5.1+ releases
    ./scripts/console cache:warmup --routing
fi

case "$branch" in
    QA_4*) PHP_REQ=$(sed -n '/"php"/ s/.*">=\([0-9]\.[0-9]\).*/\1/p' composer.json) ;;
    *) PHP_REQ=$(sed -n '/"php"/ s/.*"\^\([0-9]\.[0-9]\.[0-9]\).*/\1/p' composer.json) ;;
esac

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
composer update --no-interaction --no-dev --optimize-autoloader

# Parse the required versions from composer.json
PACKAGES_VERSIONS=''
case "$branch" in
    QA_4*) PACKAGE_LIST="tecnickcom/tcpdf pragmarx/google2fa bacon/bacon-qr-code samyoul/u2f-php-server" ;;
    *) PACKAGE_LIST="tecnickcom/tcpdf pragmarx/google2fa-qrcode samyoul/u2f-php-server" ;;
esac

for PACKAGES in $PACKAGE_LIST
do
    PKG_VERSION="$(get_composer_package_version "$PACKAGES")"
    PACKAGES_VERSIONS="$PACKAGES_VERSIONS $PACKAGES:$PKG_VERSION"
done

echo "Installing composer packages '$PACKAGES_VERSIONS'"

composer require --no-interaction --optimize-autoloader --update-no-dev $PACKAGES_VERSIONS

mv composer.json.backup composer.json
cleanup_composer_vendors
if [ $do_tag -eq 1 ] ; then
    echo "* Commiting composer.lock"
    git add --force composer.lock
    git commit -s -m "Adding composer lock for $version"
fi

if [ -f package.json ] ; then
    echo "* Running Yarn"
    yarn install --production
fi

# Remove Bootstrap theme
rm -rf themes/bootstrap

# Remove git metadata
rm .git
find . -name .gitignore -print0 | xargs -0 -r rm -f
find . -name .gitattributes -print0 | xargs -0 -r rm -f

if [ $do_test -eq 1 ] ; then
    # Move the folder out and install dev vendors
    create_phpunit_sandbox
    # Backup the files because the new autoloader will change the composer vendor
    backup_vendor_folder
    # Generate an autoload for test class files
    composer dump-autoload
    "${TEMP_PHPUNIT_FOLDER}/vendor/bin/phpunit" --no-coverage --exclude-group selenium
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
    # Remove PHPUnit cache file
    rm -f .phpunit.result.cache
    # Remove libs installed for testing
    rm -rf build
    delete_phpunit_sandbox
    restore_vendor_folder
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
        rm -f .editorconfig .browserslistrc .eslintignore .jshintrc .eslintrc.json .stylelintrc.json psalm.xml psalm-baseline.xml phpstan.neon.dist phpstan-baseline.neon phpcs.xml.dist jest.config.js
        # Gettext po files
        rm -rf po/
        # Documentation source code
        mv doc/html htmldoc
        rm -rf doc
        mkdir doc
        mv htmldoc doc/html
        rm doc/html/.buildinfo doc/html/objects.inv
        rm -rf node_modules
        # Remove bin files for non source version
        # https://github.com/phpmyadmin/phpmyadmin/issues/16033
        rm -rf vendor/bin
    fi

    # Remove developer scripts
    rm -rf scripts

    # Remove possible tmp folder
    rm -rf tmp

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

 2. Push the new STABLE branch upstream

 3. prepare a release/phpMyAdmin-$version-notes.html explaining in short the goal of
    this release and paste into it the ChangeLog for this release, followed
    by the notes of all previous incremental versions (i.e. 4.4.9 through 4.4.0)

 4. upload the files to our file server, use scripts/upload-release, eg.:

        ./scripts/upload-release $version release

 5. add a news item to our website; a good idea is to include a link to the release notes such as https://www.phpmyadmin.net/files/4.4.10/

 6. send a short mail (with list of major changes) to
        developers@phpmyadmin.net
        news@phpmyadmin.net

    Don't forget to update the Description section in the announcement,
    based on documentation.

 7. increment rc count or version in the repository :
        - run ./scripts/console set-version $version
        - in $VERSION_FILE Version class:
            - check that VERSION, MAJOR, MINOR and PATCH are correct.
        - in README the "Version" line
              " Version 2.7.1-dev "
        - in package.json the line
            " "version": " 2.7.1-dev", "
        - in doc/conf.py (if it exists) the line
              " version = '2.7.1-dev' "

 8. on https://github.com/phpmyadmin/phpmyadmin/milestones close the milestone corresponding to the released version (if this is a stable release) and open a new one for the next minor release

 9. for a major release, update demo/php/versions.ini in the scripts repository so that the demo server shows current versions

10. in case of a new major release ('y' in x.y.0), update the pmaweb/settings.py in website repository to include the new major releases

11. update the Dockerfile in the docker repository to reflect the new version and create a new annotated tag (such as with git tag -s -a 4.7.9-1 -m "Version 4.7.9-1"). Remember to push the tag with git push origin {tagName}

END
