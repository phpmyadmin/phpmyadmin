<?php

declare(strict_types=1);

if (PHP_VERSION_ID >= 80102) {
    return;
}

die('<p>PHP 8.1.2+ is required.</p><p>Currently installed version is: ' . PHP_VERSION . '</p>');
