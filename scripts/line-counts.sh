#! /usr/bin/bash
php_code="<?php\ndefine('LINE_COUNTS', true);\n\n\$LINE_COUNT = array();"
for file in `find js -name '*.js'` ; do
  lc=`wc -l $file | sed 's/\([0-9]*\).*/\1/'`
  file=${file:3}
  entry="\$LINE_COUNT[\"$file\"] = $lc;"
  php_code="$php_code\n$entry"
done
echo -e $php_code > js/line_counts.php
