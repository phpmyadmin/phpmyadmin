<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Various checks and message functions used on index page.
 *
 * @package PhpMyAdmin-Setup
 */

use PMA\libraries\VersionInformation;
use PMA\libraries\Sanitize;

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Initializes message list
 *
 * @return void
 */
function PMA_messagesBegin()
{
    if (! isset($_SESSION['messages']) || !is_array($_SESSION['messages'])) {
        $_SESSION['messages'] = array('error' => array(), 'notice' => array());
    } else {
        // reset message states
        foreach ($_SESSION['messages'] as &$messages) {
            foreach ($messages as &$msg) {
                $msg['fresh'] = false;
                $msg['active'] = false;
            }
        }
    }
}

/**
 * Adds a new message to message list
 *
 * @param string $type    one of: notice, error
 * @param string $msgId   unique message identifier
 * @param string $title   language string id (in $str array)
 * @param string $message message text
 *
 * @return void
 */
function PMA_messagesSet($type, $msgId, $title, $message)
{
    $fresh = ! isset($_SESSION['messages'][$type][$msgId]);
    $_SESSION['messages'][$type][$msgId] = array(
        'fresh' => $fresh,
        'active' => true,
        'title' => $title,
        'message' => $message);
}

/**
 * Cleans up message list
 *
 * @return void
 */
function PMA_messagesEnd()
{
    foreach ($_SESSION['messages'] as &$messages) {
        $remove_ids = array();
        foreach ($messages as $id => &$msg) {
            if ($msg['active'] == false) {
                $remove_ids[] = $id;
            }
        }
        foreach ($remove_ids as $id) {
            unset($messages[$id]);
        }
    }
}

/**
 * Prints message list, must be called after PMA_messagesEnd()
 *
 * @return void
 */
function PMA_messagesShowHtml()
{
    foreach ($_SESSION['messages'] as $type => $messages) {
        foreach ($messages as $id => $msg) {
            if (! $msg['fresh'] && $type != 'error') {
                $extra = ' hiddenmessage';
            } else {
                $extra = '';
            }
            echo '<div class="' , $type, $extra , '" id="' , $id , '">'
                , '<h4>' , $msg['title'] , '</h4>'
                , $msg['message'] , '</div>';
        }
    }
}

/**
 * Checks for newest phpMyAdmin version and sets result as a new notice
 *
 * @return void
 */
function PMA_versionCheck()
{
    // version check messages should always be visible so let's make
    // a unique message id each time we run it
    $message_id = uniqid('version_check');

    // Fetch data
    $versionInformation = new VersionInformation();
    $version_data = $versionInformation->getLatestVersion();

    if (empty($version_data)) {
        PMA_messagesSet(
            'error',
            $message_id,
            __('Version check'),
            __(
                'Reading of version failed. '
                . 'Maybe you\'re offline or the upgrade server does not respond.'
            )
        );
        return;
    }

    $releases = $version_data->releases;
    $latestCompatible = $versionInformation->getLatestCompatibleVersion($releases);
    if ($latestCompatible != null) {
        $version = $latestCompatible['version'];
        $date = $latestCompatible['date'];
    } else {
        return;
    }

    $version_upstream = $versionInformation->versionToInt($version);
    if ($version_upstream === false) {
        PMA_messagesSet(
            'error',
            $message_id,
            __('Version check'),
            __('Got invalid version string from server')
        );
        return;
    }

    $version_local = $versionInformation->versionToInt(
        $GLOBALS['PMA_Config']->get('PMA_VERSION')
    );
    if ($version_local === false) {
        PMA_messagesSet(
            'error',
            $message_id,
            __('Version check'),
            __('Unparsable version string')
        );
        return;
    }

    if ($version_upstream > $version_local) {
        $version = htmlspecialchars($version);
        $date = htmlspecialchars($date);
        PMA_messagesSet(
            'notice',
            $message_id,
            __('Version check'),
            sprintf(__('A newer version of phpMyAdmin is available and you should consider upgrading. The newest version is %s, released on %s.'), $version, $date)
        );
    } else {
        if ($version_local % 100 == 0) {
            PMA_messagesSet(
                'notice',
                $message_id,
                __('Version check'),
                Sanitize::sanitize(sprintf(__('You are using Git version, run [kbd]git pull[/kbd] :-)[br]The latest stable version is %s, released on %s.'), $version, $date))
            );
        } else {
            PMA_messagesSet(
                'notice',
                $message_id,
                __('Version check'),
                __('No newer stable version is available')
            );
        }
    }
}
