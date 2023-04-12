<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;

use function __;
use function count;
use function define;

class TwoFactorController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Relation $relation)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $relationParameters = $this->relation->getRelationParameters();

        echo $this->template->render('preferences/header', [
            'route' => $request->getRoute(),
            'is_saved' => $request->hasQueryParam('saved'),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        $twoFactor = new TwoFactor($GLOBALS['cfg']['Server']['user']);

        if ($request->hasBodyParam('2fa_remove')) {
            if (! $twoFactor->check(true)) {
                echo $this->template->render('preferences/two_factor/confirm', ['form' => $twoFactor->render()]);

                return;
            }

            $twoFactor->configure('');
            echo Message::rawNotice(__('Two-factor authentication has been removed.'))->getDisplay();
        } elseif ($request->hasBodyParam('2fa_configure')) {
            if (! $twoFactor->configure($request->getParsedBodyParam('2fa_configure'))) {
                echo $this->template->render('preferences/two_factor/configure', [
                    'form' => $twoFactor->setup(),
                    'configure' => $request->getParsedBodyParam('2fa_configure'),
                ]);

                return;
            }

            echo Message::rawNotice(__('Two-factor authentication has been configured.'))->getDisplay();
        }

        $backend = $twoFactor->getBackend();
        echo $this->template->render('preferences/two_factor/main', [
            'enabled' => $twoFactor->isWritable(),
            'num_backends' => count($twoFactor->getAvailable()),
            'backend_id' => $backend::$id,
            'backend_name' => $backend::getName(),
            'backend_description' => $backend::getDescription(),
            'backends' => $twoFactor->getAllBackends(),
            'missing' => $twoFactor->getMissingDeps(),
        ]);

        if ($this->response->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            define('PMA_DISABLE_NAVI_SETTINGS', true);
        }
    }
}
