<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\TableController
 *
 * @package PMA
 */

namespace PMA\Controllers;

if (!defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/controllers/Controller.class.php';

/**
 * Handles table related logic
 *
 * @package PhpMyAdmin
 */
abstract class TableController extends Controller
{
    function __construct()
    {
        parent::__construct();
    }
}