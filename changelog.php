<?php
// Simple script to set correct charset for changelog
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

header('Content-type: text/plain; charset=utf-8');
readfile('ChangeLog');
?>
