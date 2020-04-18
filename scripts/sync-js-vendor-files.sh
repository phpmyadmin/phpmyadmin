#!/bin/sh
#
# vim: expandtab sw=4 ts=4 sts=4:
#

# This script will sync JS node_modules files with existing files in vendor folder.
# Warning: this will not add any more files, it uses existing files in vendor folder.

ROOT_DIR="$(realpath $(dirname $0)/../)"
echo "Using root dir: $ROOT_DIR"

cd ${ROOT_DIR}

# Remove each '-not -path' when a new package can be used from npm
echo 'Delete vendor files we can replace from source dists'
# jquery.sortableTable.js is an internal lib
find ./js/vendor/ \
    -not -path './js/vendor/openlayers/*' \
    -not -path './js/vendor/sprintf.js' \
    -not -path './js/vendor/jqplot/jquery.jqplot.js' \
    -not -path './js/vendor/jqplot/plugins/jqplot.*.js' \
    -not -path './js/vendor/jquery/jquery-ui.min.js' \
    -not -path './js/vendor/jquery/jquery.sortableTable.js' \
    -not -path './js/vendor/jquery/jquery.svg.js' \
    -not -path './js/vendor/jquery/jquery.fullscreen.js' \
    -not -path './js/vendor/jquery/jquery-ui-timepicker-addon.js' \
    -not -path './js/vendor/jquery/jquery.debounce-1.0.6.js' \
    -not -path './js/vendor/jquery/jquery.ba-hashchange-1.3.js' \
    -type f -delete -print

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
echo 'Updating jquery.event.drag'
cp ./node_modules/jquery.event.drag/jquery.event.drag.js ./js/vendor/jquery/jquery.event.drag-2.2.js
# https://github.com/devongovett/jquery.event.drag/commit/2db3b7865f31eee6a8145532554f8b02210180bf#diff-ab8497cedd384270de86ee2e9f06530e
echo 'Patching jquery.event.drag to be jquery init compatible'
echo '--- js/vendor/jquery/jquery.event.drag-2.2.js 2020-04-18 16:43:43.822208181 +0200
+++ js/vendor/jquery/jquery.event.drag-2.2.js	2020-04-18 16:44:29.342750892 +0200
@@ -7,7 +7,7 @@
 // Updated: 2012-05-21
 // REQUIRES: jquery 1.7.x

-module.exports = function( $ ){
+;(function( $ ){
   // add the jquery instance method
   $.fn.drag = function( str, arg, opts ){
   	// figure out the event type
@@ -397,4 +397,4 @@

   // share the same special event configuration with related events...
   $special.draginit = $special.dragstart = $special.dragend = drag;
-};
+})( jQuery );
' | patch --strip=0
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
#see: https://github.com/cowboy/jquery-hashchange/blob/master/jquery.ba-hashchange.js
echo 'Updating jquery-uitablefilter'
cp ./node_modules/jquery-uitablefilter/jquery.uitablefilter.js js/vendor/jquery/jquery.uitablefilter.js
echo 'Updating jquery-tablesorter'
cp ./node_modules/tablesorter/dist/js/jquery.tablesorter.js ./js/vendor/jquery/jquery.tablesorter.js
#echo 'Updating jquery-fullscreen-plugin'
#see: https://github.com/kayahr/jquery-fullscreen-plugin/blob/master/jquery.fullscreen.js
#echo 'Updating jquery-debounce'
#see: https://github.com/dfilatov/jquery-plugins/blob/master/src/jquery.debounce/jquery.debounce.js
#echo 'Updating jquery-Timepicker-Addon'
#see: https://github.com/trentrichardson/jQuery-Timepicker-Addon/blob/master/dist/jquery-ui-timepicker-addon.js
echo 'Update jqplot'
#see: https://github.com/jqPlot/jqPlot/blob/master/src/jquery.jqplot.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.pieRenderer.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.barRenderer.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.pointLabels.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.enhancedLegendRenderer.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.dateAxisRenderer.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.categoryAxisRenderer.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.canvasTextRenderer.js
#see: https://github.com/jqPlot/jqPlot/blob/master/src/plugins/jqplot.canvasAxisLabelRenderer.js

cp ./node_modules/jqplot/jqplot.cursor.js ./js/vendor/jqplot/plugins/jqplot.cursor.js
cp ./node_modules/jqplot/jqplot.highlighter.js ./js/vendor/jqplot/plugins/jqplot.highlighter.js

echo 'Done.'
