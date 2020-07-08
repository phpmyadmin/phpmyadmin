<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response as ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * The navigation panel
 *
 * Displays server, database and table selection tree.
 */
class NavigationController extends AbstractController
{
    /** @var Navigation */
    private $navigation;

    /** @var Relation */
    private $relation;

    /**
     * @param ResponseRenderer  $response   A Response instance.
     * @param DatabaseInterface $dbi        A DatabaseInterface instance.
     * @param Template          $template   A Template instance.
     * @param Navigation        $navigation A Navigation instance.
     * @param Relation          $relation   A Relation instance.
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        Navigation $navigation,
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template);
        $this->navigation = $navigation;
        $this->relation = $relation;
    }

    public function index(Request $request, Response $response): Response
    {
        if (! $this->response->isAjax()) {
            $this->response->addHTML(
                Message::error(
                    __('Fatal error: The navigation can only be accessed via AJAX')
                )->getDisplay()
            );

            return $response;
        }

        if (isset($_POST['getNaviSettings']) && $_POST['getNaviSettings']) {
            $this->response->addJSON('message', PageSettings::getNaviSettings());

            return $response;
        }

        if (isset($_POST['reload'])) {
            Util::cacheSet('dbs_to_test', false);// Empty database list cache, see #14252
        }

        $cfgRelation = $this->relation->getRelationsParam();
        if ($cfgRelation['navwork']) {
            if (isset($_POST['hideNavItem'])) {
                if (! empty($_POST['itemName'])
                    && ! empty($_POST['itemType'])
                    && ! empty($_POST['dbName'])
                ) {
                    $this->navigation->hideNavigationItem(
                        $_POST['itemName'],
                        $_POST['itemType'],
                        $_POST['dbName'],
                        (! empty($_POST['tableName']) ? $_POST['tableName'] : null)
                    );
                }

                return $response;
            }

            if (isset($_POST['unhideNavItem'])) {
                if (! empty($_POST['itemName'])
                    && ! empty($_POST['itemType'])
                    && ! empty($_POST['dbName'])
                ) {
                    $this->navigation->unhideNavigationItem(
                        $_POST['itemName'],
                        $_POST['itemType'],
                        $_POST['dbName'],
                        (! empty($_POST['tableName']) ? $_POST['tableName'] : null)
                    );
                }

                return $response;
            }

            if (isset($_POST['showUnhideDialog'])) {
                if (! empty($_POST['dbName'])) {
                    $this->response->addJSON(
                        'message',
                        $this->navigation->getItemUnhideDialog($_POST['dbName'])
                    );
                }

                return $response;
            }
        }

        $this->response->addJSON('message', $this->navigation->getDisplay());

        return $response;
    }
}
