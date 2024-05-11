<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Setup\SetupHelper;

use function is_numeric;

final class ServerDestroyController
{
    public function __invoke(ServerRequest $request): void
    {
        $configFile = SetupHelper::createConfigFile();

        $id = $this->getIdParam($request->getQueryParam('id'));
        $hasServer = $id >= 1 && $configFile->get('Servers/' . $id) !== null;
        if (! $hasServer) {
            return;
        }

        $configFile->removeServer($id);
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
