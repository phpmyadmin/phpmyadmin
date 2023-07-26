<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Header;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function file_exists;
use function in_array;

use const CONFIG_FILE;

final class MainController
{
    public function __construct(private readonly ResponseFactory $responseFactory)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (@file_exists(CONFIG_FILE) && ! $GLOBALS['cfg']['DBG']['demo']) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);
            $response->getBody()->write((new Template())->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => $GLOBALS['text_dir'] ?? 'ltr',
                'error_message' => __('Configuration already exists, setup is disabled!'),
            ]));

            return $response;
        }

        /** @var mixed $pageParam */
        $pageParam = $request->getQueryParam('page');
        $page = in_array($pageParam, ['form', 'config', 'servers'], true) ? $pageParam : 'index';

        $response = $this->responseFactory->createResponse();
        $header = new Header();
        foreach ($header->getHttpHeaders() as $name => $value) {
            // Sent security-related headers
            $response = $response->withHeader($name, $value);
        }

        if ($page === 'form') {
            $response->getBody()->write((new FormController($GLOBALS['ConfigFile'], new Template()))([
                'formset' => $request->getQueryParam('formset'),
            ]));

            return $response;
        }

        if ($page === 'config') {
            $response->getBody()->write((new ConfigController($GLOBALS['ConfigFile'], new Template()))([
                'formset' => $request->getQueryParam('formset'),
                'eol' => $request->getQueryParam('eol'),
            ]));

            return $response;
        }

        if ($page === 'servers') {
            $controller = new ServersController($GLOBALS['ConfigFile'], new Template());
            /** @var mixed $mode */
            $mode = $request->getQueryParam('mode');
            if ($mode === 'remove' && $request->isPost()) {
                $controller->destroy(['id' => $request->getQueryParam('id')]);
                $response = $response->withStatus(StatusCodeInterface::STATUS_FOUND);

                return $response->withHeader(
                    'Location',
                    '../setup/index.php' . Url::getCommonRaw(['route' => '/setup']),
                );
            }

            $response->getBody()->write($controller->index([
                'formset' => $request->getQueryParam('formset'),
                'mode' => $mode,
                'id' => $request->getQueryParam('id'),
            ]));

            return $response;
        }

        $response->getBody()->write((new HomeController($GLOBALS['ConfigFile'], new Template()))([
            'formset' => $request->getQueryParam('formset'),
            'version_check' => $request->getQueryParam('version_check'),
        ]));

        return $response;
    }
}
