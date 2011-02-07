#!/bin/sh

for f in bd* ; do
    orig=b_${f##bd_}
    if [ -f $f ] ; then
        convert $orig -modulate 90,90 -colorspace Gray $f
    fi
done
convert eye.png -colorspace Gray eye_grey.png
