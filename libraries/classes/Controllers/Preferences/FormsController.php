<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\ExportForm;
use PhpMyAdmin\Config\Forms\User\FeaturesForm;
use PhpMyAdmin\Config\Forms\User\ImportForm;
use PhpMyAdmin\Config\Forms\User\MainForm;
use PhpMyAdmin\Config\Forms\User\NaviForm;
use PhpMyAdmin\Config\Forms\User\SqlForm;
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
use function define;
use function ltrim;

/**
 * User preferences page.
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
        global $cf, $url_params, $error, $tabHash, $hash;
        global $server, $PMA_Config;

        $cf = new ConfigFile($PMA_Config->base_settings);
        $this->userPreferences->pageInit($cf);

        // handle form processing
        $formParam = $_GET['form'] ?? null;

        switch ($formParam) {
            case 'Features':
                $formDisplay = new FeaturesForm($cf, 1);
                break;
            case 'Sql':
                $formDisplay = new SqlForm($cf, 1);
                break;
            case 'Navi':
                $formDisplay = new NaviForm($cf, 1);
                break;
            case 'Main':
                $formDisplay = new MainForm($cf, 1);
                break;
            case 'Export':
                $formDisplay = new ExportForm($cf, 1);
                break;
            case 'Import':
                $formDisplay = new ImportForm($cf, 1);
                break;
            default:
                Core::fatalError(__('Incorrect form specified!'));
                return;
        }

        if (isset($_POST['revert'])) {
            // revert erroneous fields to their default values
            $formDisplay->fixErrors();
            // redirect
            $url_params = ['form' => $formParam];
            Core::sendHeaderLocation(
                './index.php?route=/preferences/forms'
                . Url::getCommonRaw($url_params, '&')
            );
            return;
        }

        $error = null;
        if ($formDisplay->process(false) && ! $formDisplay->hasErrors()) {
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
                    ['form' => $formParam],
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

        $this->response->addHTML(UserPreferencesHeader::getContent($this->template, $this->relation));

        if ($formDisplay->hasErrors()) {
            $formErrors = $formDisplay->displayErrors();
        }

        $this->response->addHTML($this->template->render('preferences/forms/main', [
            'error' => $error ? $error->getDisplay() : '',
            'has_errors' => $formDisplay->hasErrors(),
            'errors' => $formErrors ?? null,
            'form' => $formDisplay->getDisplay(
                true,
                true,
                true,
                Url::getFromRoute('/preferences/forms', ['form' => $formParam]),
                ['server' => $server]
            ),
        ]));

        if ($this->response->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            define('PMA_DISABLE_NAVI_SETTINGS', true);
        }
    }
}
