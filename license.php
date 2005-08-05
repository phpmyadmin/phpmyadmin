<?php
// Simple script to set correct charset for the license
// vim: expandtab sw=4 ts=4 sts=4:
//
// Note: please do not fold this script into a general script
// that would read any file using a GET parameter, it would open a hole

header('Content-type: text/plain; charset=iso-8859-1');
readfile('LICENSE');
?>
