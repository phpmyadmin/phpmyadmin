<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Advisor;
use PhpMyAdmin\DatabaseInterface;
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
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     * @param Data              $data     Data object
     * @param Advisor           $advisor  Advisor instance
     */
    public function __construct($response, $dbi, Template $template, $data, Advisor $advisor)
    {
        parent::__construct($response, $dbi, $template, $data);
        $this->advisor = $advisor;
    }

    public function index(): void
    {
        $data = [];
        if ($this->data->dataLoaded) {
            $data = $this->advisor->run();
        }

        $this->render('server/status/advisor/index', [
            'data' => $data,
        ]);
    }
}
