<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function is_numeric;

final class UpdateNavWidthConfigController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private readonly Config $config)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $value = $request->getParsedBodyParam('value');
        if (! is_numeric($value) || $value < 0) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error(__('Unexpected parameter value.'))]);

            return;
        }

        $result = $this->config->setUserValue(null, 'NavigationWidth', (int) $value);
        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }
}
