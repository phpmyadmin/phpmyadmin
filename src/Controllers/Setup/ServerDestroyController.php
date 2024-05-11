<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Url;

use function is_numeric;

final class ServerDestroyController implements InvocableController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly ResponseRenderer $responseRenderer,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach ($this->responseRenderer->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $configFile = SetupHelper::createConfigFile();

        $id = $this->getIdParam($request->getQueryParam('id'));
        $hasServer = $id >= 1 && $configFile->get('Servers/' . $id) !== null;
        if ($hasServer) {
            $configFile->removeServer($id);
        }

        return $response->withStatus(StatusCodeInterface::STATUS_FOUND)->withHeader(
            'Location',
            '../setup/index.php' . Url::getCommonRaw(['route' => '/setup']),
        );
    }

    /** @psalm-return int<0, max> */
    private function getIdParam(mixed $idParam): int
    {
        if (! is_numeric($idParam)) {
            return 0;
        }

        $id = (int) $idParam;

        return $id >= 1 ? $id : 0;
    }
}
