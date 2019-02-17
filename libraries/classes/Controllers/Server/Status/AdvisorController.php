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
use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Server\Status\Data;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Displays the advisor feature
 *
 * @package PhpMyAdmin\Controllers
 */
class AdvisorController extends Controller
{
    /**
     * @var Advisor
     */
    private $advisor;

    /**
     * @var Data
     */
    private $data;

    /**
     * AdvisorController constructor.
     *
     * @param \PhpMyAdmin\Response          $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     */
    public function __construct($response, $dbi)
    {
        parent::__construct($response, $dbi);
        $this->advisor = new Advisor($this->dbi, new ExpressionLanguage());
        $this->data = new Data();
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
