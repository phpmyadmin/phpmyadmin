<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console\Bookmark;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function is_string;

final class AddController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $db = $request->getParsedBodyParam('db');
        $label = $request->getParsedBodyParam('label');
        $bookmarkQuery = $request->getParsedBodyParam('bookmark_query');
        $shared = $request->getParsedBodyParam('shared');

        if (! is_string($label) || ! is_string($db) || ! is_string($bookmarkQuery) || ! is_string($shared)) {
            $this->response->addJSON('message', __('Incomplete params'));

            return;
        }

        $bookmarkFields = [
            'bkm_database' => $db,
            'bkm_user' => $GLOBALS['cfg']['Server']['user'],
            'bkm_sql_query' => $bookmarkQuery,
            'bkm_label' => $label,
        ];
        $bookmark = Bookmark::createBookmark($this->dbi, $bookmarkFields, $shared === 'true');
        if ($bookmark === false || ! $bookmark->save()) {
            $this->response->addJSON('message', __('Failed'));

            return;
        }

        $this->response->addJSON('message', __('Succeeded'));
        $this->response->addJSON('data', $bookmarkFields);
        $this->response->addJSON('isShared', $shared === 'true');
    }
}
