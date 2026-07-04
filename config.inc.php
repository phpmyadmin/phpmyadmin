<?php

declare(strict_types=1);

$cfg['blowfish_secret'] = 'keploy-integration-tests-32byte!'; // 32 chars

$i = 0;
$i++;
$cfg['Servers'][$i]['auth_type'] = 'config';
$cfg['Servers'][$i]['user'] = 'root';
$cfg['Servers'][$i]['password'] = 'keploy123';
$cfg['Servers'][$i]['host'] = 'mysql';
$cfg['Servers'][$i]['port'] = '3306';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['Servers'][$i]['ssl'] = false;

$cfg['TempDir'] = '/tmp/pma';
