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
     * AdvisorController constructor.
     *
     * @param \PhpMyAdmin\Response           $response Response object
     * @param \PhpMyAdmin\DatabaseInterface  $dbi      DatabaseInterface object
     * @param \PhpMyAdmin\Server\Status\Data $data     Data object
     */
    public function __construct($response, $dbi, $data)
    {
        parent::__construct($response, $dbi, $data);
        $this->advisor = new Advisor($this->dbi, new ExpressionLanguage());
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
