<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function count;

final class DestroyController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        Transformations $transformations,
        RelationCleanup $relationCleanup
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
    }

    public function __invoke(): void
    {
        global $selected, $errorUrl, $cfg, $dblist, $reload;

        $params = [
            'drop_selected_dbs' => $_POST['drop_selected_dbs'] ?? null,
            'selected_dbs' => $_POST['selected_dbs'] ?? null,
        ];

        if (
            ! isset($params['drop_selected_dbs'])
            || ! $this->response->isAjax()
            || (! $this->dbi->isSuperUser() && ! $cfg['AllowUserDropDatabase'])
        ) {
            $message = Message::error();
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        if (! isset($params['selected_dbs'])) {
            $message = Message::error(__('No databases selected.'));
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        $errorUrl = Url::getFromRoute('/server/databases');
        $selected = $_POST['selected_dbs'];
        $rebuildDatabaseList = false;
        $sqlQuery = '';
        $numberOfDatabases = count($selected);

        for ($i = 0; $i < $numberOfDatabases; $i++) {
            $this->relationCleanup->database($selected[$i]);
            $aQuery = 'DROP DATABASE ' . Util::backquote($selected[$i]);
            $reload = true;
            $rebuildDatabaseList = true;

            $sqlQuery .= $aQuery . ';' . "\n";
            $this->dbi->query($aQuery);
            $this->transformations->clear($selected[$i]);
        }

        if ($rebuildDatabaseList) {
            $dblist->databases->build();
        }

        $message = Message::success(
            _ngettext(
                '%1$d database has been dropped successfully.',
                '%1$d databases have been dropped successfully.',
                $numberOfDatabases
            )
        );
        $message->addParam($numberOfDatabases);
        $json = ['message' => $message];
        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON($json);
    }
}
