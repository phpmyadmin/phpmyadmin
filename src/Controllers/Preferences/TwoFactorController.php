<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\TwoFactor;

use function __;
use function count;
use function define;

final readonly class TwoFactorController implements InvocableController
{
    public function __construct(private ResponseRenderer $response, private Relation $relation, private Config $config)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $relationParameters = $this->relation->getRelationParameters();

        $this->response->render('preferences/header', [
            'route' => $request->getRoute(),
            'is_saved' => $request->hasQueryParam('saved'),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        $twoFactor = new TwoFactor($this->config->selectedServer['user']);

        if ($request->hasBodyParam('2fa_remove')) {
            if (! $twoFactor->check($request, true)) {
                $this->response->render('preferences/two_factor/confirm', ['form' => $twoFactor->render($request)]);

                return $this->response->response();
            }

            $twoFactor->configure($request, '');
            $this->response->addHTML(
                Message::rawNotice(__('Two-factor authentication has been removed.'))->getDisplay(),
            );
        } elseif ($request->hasBodyParam('2fa_configure')) {
            if (! $twoFactor->configure($request, $request->getParsedBodyParamAsString('2fa_configure'))) {
                $this->response->render('preferences/two_factor/configure', [
                    'form' => $twoFactor->setup($request),
                    'configure' => $request->getParsedBodyParam('2fa_configure'),
                ]);

                return $this->response->response();
            }

            $this->response->addHTML(
                Message::rawNotice(__('Two-factor authentication has been configured.'))->getDisplay(),
            );
        }

        $backend = $twoFactor->getBackend();
        $this->response->render('preferences/two_factor/main', [
            'enabled' => $twoFactor->isWritable(),
            'num_backends' => count($twoFactor->getAvailable()),
            'backend_id' => $backend::$id,
            'backend_name' => $backend::getName(),
            'backend_description' => $backend::getDescription(),
            'backends' => $twoFactor->getAllBackends(),
            'missing' => $twoFactor->getMissingDeps(),
        ]);

        if ($request->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            define('PMA_DISABLE_NAVI_SETTINGS', true);
        }

        return $this->response->response();
    }
}
