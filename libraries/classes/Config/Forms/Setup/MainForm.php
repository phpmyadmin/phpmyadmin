<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\Setup;

class MainForm extends \PhpMyAdmin\Config\Forms\User\MainForm
{
    public static function getForms()
    {
        $result = parent::getForms();
        /* Following are not available to user */
        $result['Startup'][] = 'ShowPhpInfo';
        $result['Startup'][] = 'ShowChgPassword';
        return $result;
    }
}
