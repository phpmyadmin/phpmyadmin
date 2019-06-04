<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\Status\AdvisorController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Advisor;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Displays the advisor feature
 *
 * @package PhpMyAdmin\Controllers
 */
class AdvisorController extends AbstractController
{
    /**
     * @var Advisor
     */
    private $advisor;

    /**
     * AdvisorController constructor.
     *
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

    /**
     * @return string
     */
    public function index(): string
    {
        $data = '';
        if ($this->data->dataLoaded) {
            $data = json_encode($this->advisor->run());
        }

        return $this->template->render('server/status/advisor/index', [
            'data' => $data,
        ]);
    }
}
