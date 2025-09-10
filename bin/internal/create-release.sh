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
# The version series this script is allowed to handle
VERSION_SERIES="6.0"

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
do_revision=0

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
        --revision-info)
            do_revision=1
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
            ;;
        --no-sign)
            do_sign=0
            ;;
        --kits)
            KITS="$2"
            # Skip one position, the value
            shift
            ;;
        --compressions)
            COMPRESSIONS="$2"
            # Skip one position, the value
            shift
            ;;
        --help)
            echo "Usages:"
            echo "  create-release.sh <version> <from_branch> [--tag] [--stable] [--test] [--ci] [--daily] [--revision-info] [--compressions] [--kits] [--no-sign]"
            echo ""
            echo "If --tag is specified, release tag is automatically created (use this for all releases including pre-releases)"
            echo "If --stable is specified, the STABLE branch is updated with this release"
            echo "If --test is specified, the testsuite is executed before creating the release"
            echo "If --ci is specified, the testsuite is executed and no actual release is created"
            echo "If --no-sign is specified, the ouput files will not be signed"
            echo "If --daily is specified, the ouput files will have snapshot information"
            echo "If --revision-info is specified, the output files will contain git revision info"
            echo "If --compressions is specified, it changes the compressions available. Space separated values. Valid values: $COMPRESSIONS"
            echo "If --kits is specified, it changes the kits to be built. Space separated values. Valid values: $KITS"
            echo ""
            echo "Examples:"
            echo "  create-release.sh 5.2.2-dev QA_5_2"
            echo "  create-release.sh 5.2.2 QA_5_2 --tag --stable"
            exit 65
            ;;
        *)
            do_test=1
            if [ -z "$version" ] ; then
                version=$(echo "$1" | tr -d -c '0-9a-z.+-')
                if [ "x$version" != "x$1" ] ; then
                    echo "Invalid version: $1"
                    exit 1
                fi
            elif [ -z "$branch" ] ; then
                branch=$(echo "$1" | tr -d -c '/0-9A-Za-z_-')
                if [ "x$branch" != "x$1" ] ; then
                    echo "Invalid branch: $1"
                    exit 1
                fi
            else
                echo "Unknown parameter: $1!"
                echo "Use --help to check the syntax."
                exit 1
            fi
    esac
    shift
done

if [ -z "$version" ] && [ $do_ci -eq 0 ]; then
    echo "Version must be specified!"
    exit 1
fi

if [ -z "$branch" ]; then
    echo "Branch must be specified!"
    exit 1
fi

kit_prefix="phpMyAdmin-$version"

# Checks whether remote branch has local tracking branch
ensure_local_branch() {
    if ! git branch | grep -q '^..'"$1"'$' ; then
        git branch --track "$1" origin/"$1"
    fi
}

# Marks current head of given branch as head of other branch
# Used for STABLE tracking
mark_as_release() {
    branch=$1
    rel_branch=$2
    echo "* Marking release as $rel_branch"
    ensure_local_branch "$rel_branch"
    git checkout "$rel_branch"
    git merge -s recursive -X theirs "$branch"
    git checkout master
}

cleanup_composer_vendors() {
    echo "* Cleanup of composer packages"
    rm -rf \
        vendor/phpmyadmin/sql-parser/tests/ \
        vendor/phpmyadmin/sql-parser/tools/ \
        vendor/phpmyadmin/sql-parser/src/Tools/ \
        vendor/phpmyadmin/sql-parser/locale/sqlparser.pot \
        vendor/phpmyadmin/sql-parser/locale/*/LC_MESSAGES/sqlparser.po \
        vendor/phpmyadmin/sql-parser/bin/ \
        vendor/phpmyadmin/sql-parser/phpunit.xml.dist \
        vendor/phpmyadmin/motranslator/phpunit.xml.dist \
        vendor/phpmyadmin/motranslator/tests/ \
        vendor/phpmyadmin/shapefile/codecov.yml \
        vendor/phpmyadmin/shapefile/phpunit.xml.dist \
        vendor/phpmyadmin/shapefile/tests/ \
        vendor/phpmyadmin/shapefile/examples/ \
        vendor/phpmyadmin/shapefile/data/ \
        vendor/phpmyadmin/shapefile/phpstan-baseline.neon \
        vendor/phpmyadmin/shapefile/phpstan.neon.dist \
        vendor/phpmyadmin/twig-i18n-extension/README.rst \
        vendor/phpmyadmin/twig-i18n-extension/phpunit.xml.dist \
        vendor/phpmyadmin/twig-i18n-extension/test/ \
        vendor/symfony/cache/Tests/ \
        vendor/symfony/service-contracts/Test/ \
        vendor/symfony/expression-language/Tests/ \
        vendor/symfony/expression-language/Resources/ \
        vendor/symfony/dependency-injection/Loader/schema/dic/services/services-1.0.xsd \
        vendor/tecnickcom/tcpdf/examples/ \
        vendor/tecnickcom/tcpdf/tools/ \
        vendor/tecnickcom/tcpdf/fonts/ae_fonts_*/ \
        vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.*/ \
        vendor/tecnickcom/tcpdf/fonts/freefont-*/ \
        vendor/tecnickcom/tcpdf/include/sRGB.icc \
        vendor/tecnickcom/tcpdf/.git \
        vendor/tecnickcom/tcpdf/.github/ \
        vendor/bacon/bacon-qr-code/phpunit.xml.dist \
        vendor/bacon/bacon-qr-code/test/ \
        vendor/dasprid/enum/.github/ \
        vendor/dasprid/enum/phpunit.xml.dist \
        vendor/dasprid/enum/test/ \
        vendor/williamdes/mariadb-mysql-kbs/phpunit.xml \
        vendor/williamdes/mariadb-mysql-kbs/test/ \
        vendor/williamdes/mariadb-mysql-kbs/schemas/ \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-raw.json \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-raw.md \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-slim.json \
        vendor/williamdes/mariadb-mysql-kbs/dist/merged-ultraslim.php \
        vendor/code-lts/u2f-php-server/phpunit.xml \
        vendor/code-lts/u2f-php-server/test/ \
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
        vendor/twig/twig/.php-cs-fixer.dist.php \
        vendor/webmozart/assert/.editorconfig \
        vendor/webmozart/assert/.github/ \
        vendor/webmozart/assert/.php_cs \
        vendor/webmozart/assert/psalm.xml \
        vendor/twig/twig/src/Test/ \
        vendor/psr/http-message/docs/ \
        vendor/psr/log/Psr/Log/Test/ \
        vendor/psr/http-factory/.pullapprove.yml \
        vendor/slim/psr7/MAINTAINERS.md \
        vendor/paragonie/constant_time_encoding/tests/ \
        vendor/paragonie/constant_time_encoding/psalm.xml \
        vendor/paragonie/constant_time_encoding/phpunit.xml.dist \
        vendor/paragonie/constant_time_encoding/.travis.yml \
        vendor/pragmarx/google2fa/.github/ \
        vendor/pragmarx/google2fa/phpstan.neon \
        vendor/pragmarx/google2fa-qrcode/.scrutinizer.yml \
        vendor/pragmarx/google2fa-qrcode/.travis.yml \
        vendor/pragmarx/google2fa-qrcode/phpunit.xml \
        vendor/pragmarx/google2fa-qrcode/tests \
        vendor/google/recaptcha/src/autoload.php \
        vendor/google/recaptcha/app.yaml \
        vendor/google/recaptcha/.travis.yml \
        vendor/google/recaptcha/phpunit.xml.dist \
        vendor/google/recaptcha/.github/ \
        vendor/google/recaptcha/examples/ \
        vendor/google/recaptcha/tests/
    rm -rf \
        vendor/google/recaptcha/ARCHITECTURE.md \
        vendor/google/recaptcha/CONTRIBUTING.md \
        vendor/phpmyadmin/motranslator/CODE_OF_CONDUCT.md \
        vendor/phpmyadmin/motranslator/CONTRIBUTING.md \
        vendor/phpmyadmin/motranslator/PERFORMANCE.md \
        vendor/phpmyadmin/shapefile/CONTRIBUTING.md \
        vendor/phpmyadmin/shapefile/CODE_OF_CONDUCT.md \
        vendor/phpmyadmin/sql-parser/CODE_OF_CONDUCT.md \
        vendor/phpmyadmin/sql-parser/CONTRIBUTING.md \
        vendor/beberlei/assert/.github/ \
        vendor/brick/math/SECURITY.md \
        vendor/brick/math/psalm-baseline.xml \
        vendor/brick/math/psalm.xml \
        vendor/ramsey/collection/conventional-commits.json \
        vendor/ramsey/collection/SECURITY.md \
        vendor/spomky-labs/base64url/.github/ \
        vendor/spomky-labs/cbor-php/.php_cs.dist \
        vendor/spomky-labs/cbor-php/CODE_OF_CONDUCT.md \
        vendor/spomky-labs/cbor-php/infection.json.dist \
        vendor/spomky-labs/cbor-php/phpstan.neon \
        vendor/thecodingmachine/safe/generated/Exceptions/.gitkeep \
        vendor/thecodingmachine/safe/rector-migrate-0.7.php \
        vendor/phpmyadmin/motranslator/psalm-baseline.xml \
        vendor/phpmyadmin/motranslator/psalm.xml \
        vendor/slim/psr7/phpunit.xml.dist \
        vendor/slim/psr7/tests/ \
        vendor/psr/event-dispatcher/.editorconfig \
        vendor/spomky-labs/cbor-php/SECURITY.md \
        vendor/spomky-labs/pki-framework/SECURITY.md \
        vendor/web-auth/cose-lib/SECURITY.md \
        vendor/laminas/laminas-httphandlerrunner/.laminas-ci.json \
        vendor/twig/twig/phpstan-baseline.neon \
        vendor/twig/twig/phpstan.neon.dist
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
    if [ ! -d "${TEMP_FOLDER}" ]; then
        echo 'No backup to restore'
        exit 1;
    fi
    rm -r ./vendor
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
    composer require --no-interaction --dev "phpunit/phpunit:${PHPUNIT_VERSION}"
    cd -
}

delete_phpunit_sandbox() {
    if [ ! -d "${TEMP_PHPUNIT_FOLDER}" ]; then
        echo 'No phpunit sandbox to delete'
        exit 1;
    fi
    rm -r "${TEMP_PHPUNIT_FOLDER}"
}

security_checkup() {
    if [ ! -f vendor/tecnickcom/tcpdf/tcpdf.php ]; then
        echo 'TCPDF should be installed, detection failed !'
        exit 1;
    fi
    if [ ! -f vendor/web-auth/webauthn-lib/src/PublicKeyCredential.php ]; then
        echo 'Webauthn-lib should be installed, detection failed !'
        exit 1;
    fi
    if [ ! -f vendor/code-lts/u2f-php-server/src/U2FServer.php ]; then
        echo 'U2F-server should be installed, detection failed !'
        exit 1;
    fi
    if [ ! -f vendor/pragmarx/google2fa-qrcode/src/Google2FA.php ]; then
        echo 'Google 2FA should be installed, detection failed !'
        exit 1;
    fi
}

autoload_checkup() {
    php <<'CODE'
<?php

$classMapFiles = require __DIR__ . '/vendor/composer/autoload_classmap.php';

$foundFiles = 0;
$notFoundFiles = 0;

foreach ($classMapFiles as $classMapFile) {
        if (! file_exists($classMapFile)) {
            echo 'Does not exist: ' . $classMapFile . PHP_EOL;
            $notFoundFiles++;
            continue;
        }
        $foundFiles++;
}

echo '[autoload class map checkup] Found files: ' . $foundFiles . PHP_EOL;
echo '[autoload class map checkup] NOT found files: ' . $notFoundFiles . PHP_EOL;
$minFilesToHave = 1100;// An arbitrary value based on how many files the autoload has on average

if ($foundFiles < $minFilesToHave) {
    echo '[autoload class map checkup] The project expects at least ' . $minFilesToHave . ' in the autoload class map' . PHP_EOL;
    exit(1);
}

if ($notFoundFiles > 0) {
    echo '[autoload class map checkup] There is some missing files documented in the class map' . PHP_EOL;
    exit(1);
}
echo '[autoload class map checkup] The autoload class map seems okay' . PHP_EOL;
CODE

}

# Ensure we have tracking branch
ensure_local_branch "$branch"

VERSION_FILE=src/Version.php

# Keep in sync with update-po script
fetchReleaseFromFile() {
    SUFFIX="${1:-}"
    php -r "define('VERSION_SUFFIX', '$SUFFIX'); require_once('$VERSION_FILE'); echo \PhpMyAdmin\Version::VERSION;"
}

fetchVersionSeriesFromFile() {
    php -r "define('VERSION_SUFFIX', ''); require_once('$VERSION_FILE'); echo \PhpMyAdmin\Version::SERIES;"
}

VERSION_FROM_FILE="$(fetchReleaseFromFile)"
VERSION_SERIES_FROM_FILE="$(fetchVersionSeriesFromFile)"

if [ $do_ci -eq 1 ]; then
    VERSION_FROM_FILE="$(fetchReleaseFromFile '+ci')"
    version="${VERSION_FROM_FILE}"
fi

if [ "${VERSION_SERIES_FROM_FILE}" != "${VERSION_SERIES}" ]; then
    echo "This script can not handle ${VERSION_SERIES_FROM_FILE} version series."
    echo "Only ${VERSION_SERIES} version series are allowed, please use your target branch directly or another branch."
    echo "By changing branches you will have a release script that was designed for your version series."
    exit 1;
fi

echo "The actual configured release is: $VERSION_FROM_FILE"
echo "The actual configured release series is: $VERSION_SERIES_FROM_FILE"

if [ $do_ci -eq 0 ] && [ $do_daily -eq 0 ] ; then
    cat <<END

Please ensure you have incremented rc count or version in the repository :
     - run ./bin/console set-version $version
     - in $VERSION_FILE Version class:
        - check that VERSION, MAJOR, MINOR and PATCH are correct.
     - in docs/conf.py the line
          " version = '$version' "
     - in README the "Version" line
     - in package.json the line
          " "version": "$version", "
     - set release date in ChangeLog

Continue (y/n)?
END
    read -r do_release

    if [ "$do_release" != 'y' ]; then
        exit 100
    fi
    echo "The actual configured release is now: $(fetchReleaseFromFile)"
fi

# Create working copy
mkdir -p release
git worktree prune
workdir_name=phpMyAdmin-$version
workdir=release/$workdir_name
if [ -d "$workdir" ] ; then
    echo "Working directory '$workdir' already exists, please move it out of the way"
    exit 1
fi

# Add worktree with chosen branch
git worktree add --force "$workdir" "$branch"
cd "$workdir"
if [ $do_pull -eq 1 ] ; then
    git pull -q
fi
if [ $do_daily -eq 1 ] ; then
    git_head=$(git log -n 1 --format=%H)
    git_head_short=$(git log -n 1 --format=%h)
    today_date=$(date +'%Y%m%d' -u)
fi

if [ $do_daily -eq 1 ] ; then
    echo '* setting the version suffix for the snapshot'
    sed -i "s/'versionSuffix' => '.*'/'versionSuffix' => '+$today_date.$git_head_short'/" app/vendor_config.php
    php -l app/vendor_config.php

    # Fetch it back and refresh $version
    VERSION_FROM_FILE="$(fetchReleaseFromFile "+$today_date.$git_head_short")"
    version="${VERSION_FROM_FILE}"
    echo "The actual configured release is: $VERSION_FROM_FILE"
fi

# Check release version
if [ $do_ci -eq 0 ] && [ -$do_daily -eq 0 ] ; then
    if ! grep -q "VERSION = '$version'" $VERSION_FILE ; then
        echo "There seems to be wrong version in $VERSION_FILE!"
        exit 2
    fi
    if ! grep -q "version = '$version'" docs/conf.py ; then
        echo "There seems to be wrong version in docs/conf.py"
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

# Save the build date
if [ $do_daily -eq 1 ] ; then
    LC_ALL=C date -u > RELEASE-DATE-"$VERSION_SERIES_FROM_FILE"+snapshot
else
    LC_ALL=C date -u > RELEASE-DATE-"$version"
fi

# Building documentation
echo "* Running sphinx-build (version: $(sphinx-build --version))"
echo "* Generating documentation"
LC_ALL=C make -C docs html
find docs -name '*.pyc' -print0 | xargs -0 -r rm -f

# Check for gettext support
if [ -d resources/po ] ; then
    echo "* Generating mo files"
    ./bin/generate-mo
    if [ -f ./bin/remove-incomplete-mo ] ; then
        echo "* Removing incomplete translations"
        ./bin/remove-incomplete-mo
    fi
fi

if [ -f ./bin/line-counts.sh ] ; then
    echo "* Generating line counts"
    ./bin/line-counts.sh
fi

echo "* Removing unneeded files"

# Remove developer information
rm -r .github CODE_OF_CONDUCT.md DCO

# Testsuite setup
rm .scrutinizer.yml .weblate codecov.yml

# Remove Doctum config file
rm tests/doctum-config.php

# Remove readme for github
rm README.rst

if [ -f ./bin/console ]; then
    # Update the vendors to have the dev vendors
    composer install --no-interaction
    # Warm up the routing cache for 5.1+ releases
    ./bin/console cache:warmup --routing
    if [ $do_revision -eq 1 ] ; then
        ./bin/console write-revision-info
    fi
fi

echo "* Writing the version to composer.json (version: $version)"
composer config version "$version"

# Okay, there is no way to tell composer to install
# suggested package. Let's require it and then revert
# composer.json to original state.
cp composer.json composer.json.backup
COMPOSER_VERSION="$(composer --version)"
echo "* Running composer (version: $COMPOSER_VERSION)"
composer install --no-interaction --no-dev

# Parse the required versions from composer.json
PACKAGE_LIST='tecnickcom/tcpdf pragmarx/google2fa-qrcode bacon/bacon-qr-code code-lts/u2f-php-server web-auth/webauthn-lib'

set --

for PACKAGES in $PACKAGE_LIST
do
    PKG_VERSION="$(get_composer_package_version "$PACKAGES")"
    set -- "$@" "$PACKAGES:$PKG_VERSION"
done

echo "* Installing composer packages '$*'"

# Allows word splitting
# shellcheck disable=SC2086
composer require --no-interaction --update-no-dev "$@"

echo "* Running a security checkup"
security_checkup

echo "* Cleaning up vendor folders"
mv composer.json.backup composer.json
cleanup_composer_vendors

echo "* Re-generating the autoload class map"
# https://getcomposer.org/doc/articles/autoloader-optimization.md#what-does-it-do-
# We removed some files, we also need that composer removes them from autoload class maps
# If the class is in the class map (as explained in the link above) then it is assumed to exist as a file
composer dump-autoload --no-interaction --optimize --dev

echo "* Running an autoload checkup"
autoload_checkup

echo "* Running a security checkup"
security_checkup

if [ -f package.json ] ; then
    echo "* Running Yarn"
    yarn install --production
fi

# Remove git metadata
rm .git
find . -name .gitignore -print0 | xargs -0 -r rm
find . -name .gitattributes -print0 | xargs -0 -r rm

if [ $do_test -eq 1 ] ; then
    # Move the folder out and install dev vendors
    create_phpunit_sandbox
    # Backup the files because the new autoloader will change the composer vendor
    backup_vendor_folder
    # Generate an autoload for test class files (and include dev namespaces)
    composer dump-autoload --dev || php -r "echo 'Requires: composer >= v2.1.2' . PHP_EOL; exit(1);"
    "${TEMP_PHPUNIT_FOLDER}/vendor/bin/phpunit" --no-coverage --testsuite unit
    test_ret=$?
    if [ $do_ci -eq 1 ] ; then
        cd ../..
        rm -r "$workdir"
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
    # Generate an normal autoload (this is just a security, because normally the vendor folder will be restored)
    composer dump-autoload
    # Remove libs installed for testing
    rm -r build
    delete_phpunit_sandbox
    restore_vendor_folder
fi

security_checkup

cd ..

SIGN_FILES=""

# Prepare all kits
for kit in $KITS ; do
    echo "* Building kit: $kit"
    # Copy all files
    name=$kit_prefix-$kit
    cp -r "$workdir_name" "$name"

    # Cleanup translations
    cd "$name"
    ./bin/internal/lang-cleanup.sh "$kit"

    # Remove tests, source code,...
    if [ "$kit" != source ] ; then
        echo "* Removing source files"
        # Testsuite
        rm -r tests/
        # Template test files
        rm -r resources/templates/test/
        rm phpunit.xml.*
        rm .editorconfig .browserslistrc .eslintignore .jshintrc .eslintrc.json .stylelintrc.json psalm.xml psalm-baseline.xml phpstan.neon.dist phpstan-baseline.neon phpcs.xml.dist jest.config.cjs infection.json5.dist .phpstorm.meta.php
        # Gettext po files (if they were not removed by ./bin/internal/lang-cleanup.sh)
        rm -rf resources/po
        # Documentation source code
        mv docs/html htmldoc
        rm -r docs
        mkdir public/docs
        mv htmldoc public/docs/html
        rm public/docs/html/.buildinfo public/docs/html/objects.inv
        rm -r node_modules
        # Remove bin files for non source version
        # https://github.com/phpmyadmin/phpmyadmin/issues/16033
        rm -r vendor/bin

        # Remove developer scripts
        rm -r bin
    fi

    # Remove possible tmp folder
    rm -rf tmp

    cd ..

    # Remove tar file possibly left from previous run
    rm -f "$name".tar

    # Prepare distributions
    for comp in $COMPRESSIONS ; do
        case $comp in
            tbz|tgz|txz)
                if [ ! -f "$name".tar ] ; then
                    echo "* Creating $name.tar"
                    tar --owner=root --group=root --numeric-owner --sort=name -cf "$name".tar "$name"
                fi
                if [ "$comp" = txz ] ; then
                    echo "* Creating $name.tar.xz"
                    xz -9k "$name".tar
                    SIGN_FILES="$SIGN_FILES $name.tar.xz"
                fi
                if [ "$comp" = tgz ] ; then
                    echo "* Creating $name.tar.gz"
                    gzip -9c "$name".tar > "$name".tar.gz
                    SIGN_FILES="$SIGN_FILES $name.tar.gz"
                fi
                ;;
            zip-7z)
                echo "* Creating $name.zip"
                7za a -bd -tzip "$name".zip "$name" > /dev/null
                SIGN_FILES="$SIGN_FILES $name.zip"
                ;;
            *)
                echo "WARNING: ignoring compression '$comp', not known!"
                ;;
        esac
    done


    # Cleanup
    rm -f "$name".tar
    # Remove directory with current dist set
    rm -r "$name"
done

# Cleanup
rm -r "$workdir_name"
git worktree prune

# Signing of files with default GPG key
if [ $do_sign -eq 1 ] ; then
    echo "* Signing and making .sha{1,256} files"
else
    echo "* Making .sha{1,256} files"
fi

for file in $SIGN_FILES; do
    if [ $do_sign -eq 1 ] ; then
        gpg --detach-sign --armor "$file"
    fi
    sha1sum "$file" > "$file".sha1
    sha256sum "$file" > "$file".sha256
done

if [ $do_daily -eq 1 ] ; then
    cat > "$kit_prefix".json << EOT
{
    "date": "$(date --iso-8601=seconds)",
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

ls -la $SIGN_FILES

cd ..

# Tag as release
if [ $do_tag -eq 1 ] ; then
    echo
    echo "Additional tasks:"
    tagname=RELEASE_$(echo "$version" | tr . _ | tr '[:lower:]' '[:upper:]' | tr -d -)
    echo "* Tagging release as $tagname"
    git tag -s -a -m "Released $version" "$tagname" "$branch"
    echo "   Dont forget to push tags using: git push --tags"
fi

# Mark as stable release
if [ $do_stable -eq 1 ] ; then
    mark_as_release "$branch" STABLE
fi

cat <<END


Todo now:
---------

 1. Push the new tag upstream, with a command like git push origin --tags

 2. Push the new STABLE branch upstream

 3. prepare a release/phpMyAdmin-$version-notes.html explaining in short the goal of
    this release and paste into it the ChangeLog for this release, followed
    by the notes of all previous incremental versions (i.e. 4.4.9 through 4.4.0)

 4. upload the files to our file server, use bin/internal/upload-release, eg.:

        ./bin/internal/upload-release $version release

 5. add a news item to our website; a good idea is to include a link to the release notes such as https://www.phpmyadmin.net/files/4.4.10/

 6. send a short mail (with list of major changes) to
        developers@phpmyadmin.net
        news@phpmyadmin.net

    Don't forget to update the Description section in the announcement,
    based on documentation.

 7. increment rc count or version in the repository :
        - run ./bin/console set-version $version
        - in $VERSION_FILE Version class:
            - check that VERSION, MAJOR, MINOR and PATCH are correct.
        - in README the "Version" line
              " Version 2.7.1-dev "
        - in package.json the line
            " "version": " 2.7.1-dev", "
        - in docs/conf.py (if it exists) the line
              " version = '2.7.1-dev' "

 8. on https://github.com/phpmyadmin/phpmyadmin/milestones close the milestone corresponding to the released version (if this is a stable release) and open a new one for the next minor release

 9. for a major release, update demo/php/versions.ini in the scripts repository so that the demo server shows current versions

10. in case of a new major release ('y' in x.y.0), update the pmaweb/settings.py in website repository to include the new major releases

11. update the Dockerfile in the docker repository to reflect the new version and create a new annotated tag (such as with git tag -s -a 4.7.9-1 -m "Version 4.7.9-1"). Remember to push the tag with git push origin {tagName}

END
