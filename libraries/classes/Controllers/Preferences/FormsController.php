<?php
/**
 * @package PhpMyAdmin\Controllers\Preferences
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\UserPreferencesHeader;

/**
 * User preferences page.
 *
 * @package PhpMyAdmin\Controllers\Preferences
 */
class FormsController extends AbstractController
{
    /** @var UserPreferences */
    private $userPreferences;

    /** @var Relation */
    private $relation;

    /**
     * @param Response          $response        A Response instance.
     * @param DatabaseInterface $dbi             A DatabaseInterface instance.
     * @param Template          $template        A Template instance.
     * @param UserPreferences   $userPreferences A UserPreferences instance.
     * @param Relation          $relation        A Relation instance.
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        UserPreferences $userPreferences,
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template);
        $this->userPreferences = $userPreferences;
        $this->relation = $relation;
    }

    public function index(): void
    {
        global $cf, $form_param, $form_class, $form_display, $url_params, $error, $tabHash, $hash;
        global $server, $PMA_Config;

        $cf = new ConfigFile($PMA_Config->base_settings);
        $this->userPreferences->pageInit($cf);

        // handle form processing
        $form_param = $_GET['form'] ?? null;
        $form_class = UserFormList::get($form_param);
        if ($form_class === null) {
            Core::fatalError(__('Incorrect form specified!'));
        }

        /** @var BaseForm $form_display */
        $form_display = new $form_class($cf, 1);

        if (isset($_POST['revert'])) {
            // revert erroneous fields to their default values
            $form_display->fixErrors();
            // redirect
            $url_params = ['form' => $form_param];
            Core::sendHeaderLocation(
                './index.php?route=/preferences/forms'
                . Url::getCommonRaw($url_params, '&')
            );
            return;
        }

        $error = null;
        if ($form_display->process(false) && ! $form_display->hasErrors()) {
            // Load 2FA settings
            $twoFactor = new TwoFactor($GLOBALS['cfg']['Server']['user']);
            // save settings
            $result = $this->userPreferences->save($cf->getConfigArray());
            // save back the 2FA setting only
            $twoFactor->save();
            if ($result === true) {
                // reload config
                $PMA_Config->loadUserPreferences();
                $tabHash = $_POST['tab_hash'] ?? null;
                $hash = ltrim($tabHash, '#');
                $this->userPreferences->redirect(
                    'index.php?route=/preferences/forms',
                    ['form' => $form_param],
                    $hash
                );
                return;
            } else {
                $error = $result;
            }
        }

        // display forms
        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('config.js');

        echo UserPreferencesHeader::getContent($this->template, $this->relation);

        if ($form_display->hasErrors()) {
            $formErrors = $form_display->displayErrors();
        }

        echo $this->template->render('preferences/forms/main', [
            'error' => $error ? $error->getDisplay() : '',
            'has_errors' => $form_display->hasErrors(),
            'errors' => $formErrors ?? null,
            'form' => $form_display->getDisplay(
                true,
                true,
                true,
                Url::getFromRoute('/preferences/forms', ['form' => $form_param]),
                ['server' => $server]
            ),
        ]);

        if ($this->response->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            define('PMA_DISABLE_NAVI_SETTINGS', true);
        }
    }
}
