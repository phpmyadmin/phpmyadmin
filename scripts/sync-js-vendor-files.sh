#!/bin/sh
#
# vim: expandtab sw=4 ts=4 sts=4:
#

# This script will sync JS node_modules files with existing files in vendor folder.
# Warning: this will not add any more files, it uses existing files in vendor folder.

ROOT_DIR="$(realpath $(dirname $0)/../)"
echo "Using root dir: $ROOT_DIR"

cd ${ROOT_DIR}

# Uncomment when all the modules are in the package.json file
#echo 'Delete all files'
#find ./js/vendor/ -not -path './openlayers/*' -type f -delete

echo 'Updating codemirror'
cp ./node_modules/codemirror/addon/hint/sql-hint.js ./js/vendor/codemirror/addon/hint/sql-hint.js
cp ./node_modules/codemirror/addon/hint/show-hint.css ./js/vendor/codemirror/addon/hint/show-hint.css
cp ./node_modules/codemirror/addon/hint/show-hint.js ./js/vendor/codemirror/addon/hint/show-hint.js
cp ./node_modules/codemirror/addon/runmode/runmode.js ./js/vendor/codemirror/addon/runmode/runmode.js
cp ./node_modules/codemirror/addon/lint/lint.css ./js/vendor/codemirror/addon/lint/lint.css
cp ./node_modules/codemirror/addon/lint/lint.js ./js/vendor/codemirror/addon/lint/lint.js
cp ./node_modules/codemirror/lib/codemirror.js ./js/vendor/codemirror/lib/codemirror.js
cp ./node_modules/codemirror/lib/codemirror.css ./js/vendor/codemirror/lib/codemirror.css
cp ./node_modules/codemirror/mode/sql/sql.js ./js/vendor/codemirror/mode/sql/sql.js
cp ./node_modules/codemirror/mode/javascript/javascript.js ./js/vendor/codemirror/mode/javascript/javascript.js
cp ./node_modules/codemirror/mode/xml/xml.js ./js/vendor/codemirror/mode/xml/xml.js
cp ./node_modules/codemirror/LICENSE ./js/vendor/codemirror/LICENSE
echo 'Updating jquery'
cp ./node_modules/jquery/dist/jquery.min.js ./js/vendor/jquery/jquery.min.js
cp ./node_modules/jquery/dist/jquery.min.map ./js/vendor/jquery/jquery.min.map
cp ./node_modules/jquery/LICENSE.txt ./js/vendor/jquery/MIT-LICENSE.txt
echo 'Updating jquery-migrate'
cp ./node_modules/jquery-migrate/dist/jquery-migrate.js ./js/vendor/jquery/jquery-migrate.js
echo 'Updating jquery-mousewheel'
cp ./node_modules/jquery-mousewheel/jquery.mousewheel.js ./js/vendor/jquery/jquery.mousewheel.js
# echo 'Updating jquery-ui'
# Impossible to do, they do not distribute dist files in the package...
#echo 'Updating jquery.event.drag'
#cp ./node_modules/jquery.event.drag/jquery.event.drag.js ./js/vendor/jquery/jquery.event.drag-2.2.js
echo 'Updating jquery-validation'
cp ./node_modules/jquery-validation/dist/jquery.validate.js ./js/vendor/jquery/jquery.validate.js
cp ./node_modules/jquery-validation/dist/additional-methods.js ./js/vendor/jquery/additional-methods.js
echo 'Updating js-cookie'
cp ./node_modules/js-cookie/src/js.cookie.js ./js/vendor/js.cookie.js
echo 'Updating bootstrap'
cp ./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js ./js/vendor/bootstrap/bootstrap.bundle.min.js
cp ./node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map ./js/vendor/bootstrap/bootstrap.bundle.min.js.map
echo 'Updating zxcvbn'
cp ./node_modules/zxcvbn/dist/zxcvbn.js ./js/vendor/zxcvbn.js
cp ./node_modules/zxcvbn/dist/zxcvbn.js.map ./js/vendor/zxcvbn.js.map
echo 'Updating tracekit'
cp ./node_modules/tracekit/tracekit.js ./js/vendor/tracekit.js
echo 'Updating u2f-api-polyfill'
cp ./node_modules/u2f-api-polyfill/u2f-api-polyfill.js ./js/vendor/u2f-api-polyfill.js
echo 'Updating blueimp-md5'
cp ./node_modules/blueimp-md5/js/md5.js ./js/vendor/jquery/jquery.md5.js
#echo 'Updating jquery.svg.js'
#see: https://github.com/kbwood/svg/blob/master/jquery.svg.js
#echo 'Updating jquery-hashchange'
#see: https://raw.githubusercontent.com/cowboy/jquery-hashchange/master/jquery.ba-hashchange.js
#echo 'Updating jquery-uitablefilter'
#see: https://github.com/natinusala/jquery-uitablefilter/blob/master/jquery.uitablefilter.js
echo 'Updating jquery-tablesorter'
cp ./node_modules/tablesorter/dist/js/jquery.tablesorter.js ./js/vendor/jquery/jquery.tablesorter.js
echo 'Done.'
