#!/bin/sh

for f in bd* ; do
    orig=b_${f##bd_}
    if [ -f $f ] ; then
        convert $orig -colorspace Gray $f
    fi
done
convert eye.png -colorspace Gray eye_grey.png
