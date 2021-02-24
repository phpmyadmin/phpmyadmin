<?php

declare(strict_types=1);

$i = 0;
$cfg['Servers'] = [];
$i++;
$cfg['Servers'][$i]['verbose'] = 'Local';
$cfg['Servers'][$i]['host'] = '127.0.0.1';
$cfg['Servers'][$i]['auth_type'] = 'cookie';
$cfg['UploadDir'] = './test/test_data/';
