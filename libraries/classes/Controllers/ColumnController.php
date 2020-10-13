<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

final class ColumnController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function all(): void
    {
        if (! isset($_POST['db'], $_POST['table'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON(['columns' => $this->dbi->getColumnNames($_POST['db'], $_POST['table'])]);
    }
}
