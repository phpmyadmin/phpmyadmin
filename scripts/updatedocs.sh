#!/bin/sh
#
# $Id$
#
# Script to build plain text documentation from the HTML version
#
SRC=Documentation.html
DST=Documentation.txt
OPTIONS="--dont_wrap_pre --nolist --dump"
CMD=lynx
if [ ! -e "$SRC" ]; then
  if [ -e ../"$SRC" ]; then
    SRC="../$SRC"
  else
    echo 'Unable to locate documentation!'
    return
  fi;
fi;
$CMD $OPTIONS "$SRC" > "$DST"
