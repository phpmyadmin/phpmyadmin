<?php

/**
 * This example configuration shows how to configure phpMyAdmin for
 * many hosts that all have identical configuration otherwise. To add
 * a new host, just drop it into $hosts below. Contributed by
 * Matthew Hawkins.
 */

$i=0;
$hosts = array (
    "foo.example.com",
    "bar.example.com",
    "baz.example.com",
    "quux.example.com",
);

foreach ($hosts as $host) {
    $i++;
    $cfg['Servers'][$i]['host']     = $host;
    $cfg['Servers'][$i]['port']     = '';
    $cfg['Servers'][$i]['socket']   = '';
    $cfg['Servers'][$i]['connect_type']     = 'tcp';
    $cfg['Servers'][$i]['extension']        = 'mysql';
    $cfg['Servers'][$i]['compress'] = FALSE;
    $cfg['Servers'][$i]['controluser']      = 'pma';
    $cfg['Servers'][$i]['controlpass']      = 'pmapass';
    $cfg['Servers'][$i]['auth_type']        = 'cookie';
    $cfg['Servers'][$i]['user']     = '';
    $cfg['Servers'][$i]['password'] = '';
    $cfg['Servers'][$i]['only_db']  = '';
    $cfg['Servers'][$i]['verbose']  = '';
    $cfg['Servers'][$i]['pmadb']    = 'phpmyadmin';
    $cfg['Servers'][$i]['bookmarktable']    = 'pma_bookmark';
    $cfg['Servers'][$i]['relation'] = 'pma_relation';
    $cfg['Servers'][$i]['table_info']       = 'pma_table_info';
    $cfg['Servers'][$i]['table_coords']     = 'pma_table_coords';
    $cfg['Servers'][$i]['pdf_pages']        = 'pma_pdf_pages';
    $cfg['Servers'][$i]['column_info']      = 'pma_column_info';
    $cfg['Servers'][$i]['history']  = 'pma_history';
    $cfg['Servers'][$i]['designer_coords'] = 'pma_designer_coords';
}

