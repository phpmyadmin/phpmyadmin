<?php
/**
 * Generic AJAX endpoint for getting information about database
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response as ResponseRenderer;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function json_decode;

/**
 * Generic AJAX endpoint for getting information about database
 */
class AjaxController extends AbstractController
{
    /** @var Config */
    private $config;

    /**
     * @param ResponseRenderer  $response Response instance
     * @param DatabaseInterface $dbi      DatabaseInterface instance
     * @param Template          $template Template object
     * @param Config            $config   Config instance
     */
    public function __construct($response, $dbi, Template $template, $config)
    {
        parent::__construct($response, $dbi, $template);
        $this->config = $config;
    }

    public function databases(Request $request, Response $response): Response
    {
        global $dblist;

        $this->response->addJSON(['databases' => $dblist->databases]);

        return $response;
    }

    public function tables(Request $request, Response $response): Response
    {
        if (! isset($_POST['db'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return $response;
        }

        $this->response->addJSON(['tables' => $this->dbi->getTables($_POST['db'])]);

        return $response;
    }

    public function columns(Request $request, Response $response): Response
    {
        if (! isset($_POST['db'], $_POST['table'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return $response;
        }

        $this->response->addJSON([
            'columns' => $this->dbi->getColumnNames(
                $_POST['db'],
                $_POST['table']
            ),
        ]);

        return $response;
    }

    public function getConfig(Request $request, Response $response): Response
    {
        if (! isset($_POST['key'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return $response;
        }

        $this->response->addJSON(['value' => $this->config->get($_POST['key'])]);

        return $response;
    }

    public function setConfig(Request $request, Response $response): Response
    {
        if (! isset($_POST['key'], $_POST['value'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return $response;
        }

        $result = $this->config->setUserValue(
            null,
            $_POST['key'],
            json_decode($_POST['value'])
        );

        if ($result === true) {
            return $response;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);

        return $response;
    }
}
