<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Validator;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function __;
use function explode;
use function file_exists;
use function implode;
use function is_array;
use function json_decode;
use function json_encode;
use function sprintf;

use const CONFIG_FILE;

final class ValidateController implements InvocableController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly Template $template,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (@file_exists(CONFIG_FILE) && ! $this->config->config->debug->demo) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

            return $response->write($this->template->render('error/generic', [
                'lang' => Current::$lang,
                'error_message' => __('Configuration already exists, setup is disabled!'),
            ]));
        }

        $response = $this->responseFactory->createResponse();
        foreach (Core::headerJSON() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $id = $request->getParsedBodyParamAsString('id', '');
        $vids = explode(',', $id);

        $valuesParam = $request->getParsedBodyParamAsString('values', '');
        $values = json_decode($valuesParam, true);
        if (! is_array($values)) {
            return $response->write((string) json_encode(['success' => false, 'message' => __('Wrong data')]));
        }

        $configFile = SetupHelper::createConfigFile();
        $validator = new Validator($configFile);
        $result = $validator->validate($vids, $values, true);
        if ($result === false) {
            $result = sprintf(
                __('Wrong data or no validation for %s'),
                implode(',', $vids),
            );
        }

        return $response->write($result !== true ? (string) json_encode($result) : '');
    }
}
