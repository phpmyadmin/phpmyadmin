<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * query by example the whole database
 *
 * @package PhpMyAdmin
 */

$session_name = 'phpMyAdmin';
@session_name($session_name);
session_start();

require_once 'vendor/autoload.php';

use RobThree\Auth\Providers\Qr\IQRCodeProvider;
use PHPQRCode;

class PMAQRProvider implements IQRCodeProvider {
  public function getMimeType() {
    return 'image/png';                             // This provider only returns PNG's
  }

  public function getQRCodeImage($qrtext, $size) {
    ob_start();                                     // 'Catch' QRCode's output
    PHPQRCode\QRcode::png($qrtext, null, PHPQRCode\Constants::QR_ECLEVEL_L, 3, 4);
                                                    // We ignore $size and set it to 3
                                                    // since phpqrcode doesn't support
                                                    // a size in pixels...
    $result = ob_get_contents();                    // 'Catch' QRCode's output
    ob_end_clean();                                 // Cleanup
    return $result;                                 // Return image
  }
}

$qrprovider = new PMAQRProvider();

$tfa = new RobThree\Auth\TwoFactorAuth('phpMyAdmin', 6, 30, 'sha1', $qrprovider);
if (isset($_REQUEST['verification'])) {
    if ($tfa->verifyCode($_SESSION['secret'], $_REQUEST['verification'], 2)) {
        if (isset($_REQUEST['settingup']) && $_REQUEST['settingup'] == 'true') {
            require_once 'libraries/common.inc.php';
            $response = PhpMyAdmin\Response::getInstance();
            $response->disable();

            $cfg_2fa_table = $GLOBALS['cfg']['Server']['2fa_secrets'];
            $create_table_query = 'CREATE TABLE IF NOT EXISTS ' . $cfg_2fa_table . ' (pma_user VARCHAR(64), secret VARCHAR(20))
                COMMENT="2 factor authentication secrets"
                DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;';
            if (!$GLOBALS['dbi']->selectDb('phpmyadmin', $GLOBALS['controllink'])) {
                echo htmlspecialchars($GLOBALS['dbi']->getError($GLOBALS['controllink']));
                exit;
            }
            if (!$GLOBALS['dbi']->tryQuery($create_table_query, $GLOBALS['controllink'])) {
                echo htmlspecialchars($GLOBALS['dbi']->getError($GLOBALS['controllink']));
                exit;
            }

            $user = $GLOBALS['dbi']->escapeString($GLOBALS['PHP_AUTH_USER'], $GLOBALS['controllink']);
            $add_secret_query = 'INSERT INTO ' . $cfg_2fa_table . ' VALUES (\'' . $user . '\' ,\'' . $_SESSION['secret'] . '\');';
            if (!$GLOBALS['dbi']->tryQuery($add_secret_query, $GLOBALS['controllink'])) {
                echo htmlspecialchars($GLOBALS['dbi']->getError($GLOBALS['controllink']));
                exit;
            }
        }
        $_SESSION['2FAEnabled'] = true;
        $_SESSION['2FAVerified'] = true;
        echo 'true';
    } else {
        echo htmlspecialchars('Sorry. The key does not match. Please ensure that server time and your app\'s time are in sync.');
    }
    exit;
}

if (isset($_REQUEST['delete_key'])) {
    require_once 'libraries/common.inc.php';
    $response = PhpMyAdmin\Response::getInstance();
    if (!$GLOBALS['dbi']->selectDb('phpmyadmin', $GLOBALS['controllink'])) {
        $response->disable();
        echo htmlspecialchars($GLOBALS['dbi']->getError($GLOBALS['controllink']));
        exit;
    }
    $cfg_2fa_table = $GLOBALS['cfg']['Server']['2fa_secrets'];
    $user = $GLOBALS['dbi']->escapeString($GLOBALS['PHP_AUTH_USER'], $GLOBALS['controllink']);
    $delete_key_query = 'DELETE FROM `' . $cfg_2fa_table . '` WHERE
        `pma_user` = \'' . $user . '\';';
    if (!($GLOBALS['dbi']->query($delete_key_query, $GLOBALS['controllink']))) {
        $response->disable();
        echo htmlspecialchars($GLOBALS['dbi']->getError($GLOBALS['controllink']));
        exit;
    }
    $html_output = PhpMyAdmin\Message::success(__('Deleted keys successfully'));
    $_SESSION['2FAEnabled'] = false;
    $_SESSION['2FAVerified'] = false;
    $response->addHTML($html_output);
}

use PhpMyAdmin\Response;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;

require_once 'libraries/common.inc.php';

$response = Response::getInstance();

if (empty($GLOBALS['cfg']['Server']['2fa_secrets'])) {
    $html_output = Message::notice(__('Sorry! You have not configured storage settings.'));
    $response->addHTML($html_output);
    exit;
}

$controllink = $GLOBALS['dbi']->connect(
    DatabaseInterface::CONNECT_CONTROL
);
if (!$GLOBALS['dbi']->selectDb('phpmyadmin', $controllink)) {
    $conn_error = $GLOBALS['dbi']->getError($controllink);
    return false;
}

$cfg_2fa_table = $GLOBALS['cfg']['Server']['2fa_secrets'];
$user = $GLOBALS['dbi']->escapeString($GLOBALS['PHP_AUTH_USER'], $GLOBALS['controllink']);
$check_2FA_db_query = 'SELECT `pma_user` FROM `' . $cfg_2fa_table . '` WHERE `pma_user` = \'' . $user . '\';';
$result = null;
if (!($result = $GLOBALS['dbi']->query($check_2FA_db_query, $controllink))) {
    $conn_error = $GLOBALS['dbi']->getError($controllink);
    return false;
}
$num_rows = $GLOBALS['dbi']->numRows($result);
assert ($num_rows <= 1);
if ($num_rows == 1) {
    $html_output = '<fieldset>';
    $html_output .= '<legend>' . __('2 factor authentication setup') . '</legend>';
    $html_output .= '<h3>' . __('You have already successfully setup 2 factor authentication.') . '</h3>';
    $html_output .= '<form action="setup2FA.php">';
    $html_output .= '<input type="submit" id="delete_key" value="Delete key" name="delete_key">';
    $html_output .= '</form>';
    $html_output .= '</fieldset>';
    $response->addHTML($html_output);
    exit;
}

$qrprovider = new PMAQRProvider();
$tfa = new RobThree\Auth\TwoFactorAuth('phpMyAdmin', 6, 30, 'sha1', $qrprovider);

$secret = $tfa->createSecret();

$_SESSION['secret'] = $secret;

$html_output = '<fieldset>';
$html_output .= '<legend>' . __('2 factor authentication setup') . '</legend>';
$html_output .= '<ol> <li>';
$html_output .= '<p>' . __('Please scan the following QR code with your app') . '</p>';
$html_output .= '<fieldset style="display:inline-block"> <img src="' . $tfa->getQRCodeImageAsDataUri("QRcode", $secret) . '"> </fieldset>';
$html_output .= '</li> <li>';
$html_output .= '<p>' . __('Enter the code generated in text box below and press submit.') . '</p>';
$html_output .= '<input type="text" id="shared_key">';
$html_output .= '<input type="button" id="submit" value="Submit">';
$html_output .= '</li> </ol>';
$html_output .= '</fieldset>';

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('setup2FA.js');

$response->addHTML($html_output);
