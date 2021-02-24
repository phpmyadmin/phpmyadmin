<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Advisor;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

/**
 * Displays the advisor feature
 */
class AdvisorController extends AbstractController
{
    /** @var Advisor */
    private $advisor;

    /**
     * @param Response $response
     * @param Data     $data
     */
    public function __construct($response, Template $template, $data, Advisor $advisor)
    {
        parent::__construct($response, $template, $data);
        $this->advisor = $advisor;
    }

    public function index(): void
    {
        $data = [];
        if ($this->data->dataLoaded) {
            $data = $this->advisor->run();
        }

        $this->render('server/status/advisor/index', ['data' => $data]);
    }
}
