<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use function json_decode;

final class ConfigController extends AbstractController
{
    /** @var Config */
    private $config;

    /**
     * @param Response $response
     */
    public function __construct($response, Template $template, Config $config)
    {
        parent::__construct($response, $template);
        $this->config = $config;
    }

    public function get(): void
    {
        if (! isset($_POST['key'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON(['value' => $this->config->get($_POST['key'])]);
    }

    public function set(): void
    {
        if (! isset($_POST['key'], $_POST['value'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $result = $this->config->setUserValue(null, $_POST['key'], json_decode($_POST['value']));

        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }
}
