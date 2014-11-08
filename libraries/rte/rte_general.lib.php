<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * General functions.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check result
 *
 * @param resource|bool $result          Query result
 * @param string        $error           Error to add
 * @param string        $createStatement Query
 * @param array         $errors          Errors
 *
 * @return array
 */
function checkResult($result, $error, $createStatement, $errors)
{
    if ($result) {
        return $errors;
    }

    // OMG, this is really bad! We dropped the query,
    // failed to create a new one
    // and now even the backup query does not execute!
    // This should not happen, but we better handle
    // this just in case.
    $errors[] = $error . '<br />'
        . __('The backed up query was:')
        . "\"" . htmlspecialchars($createStatement) . "\"" . '<br />'
        . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);

    return $errors;
}
