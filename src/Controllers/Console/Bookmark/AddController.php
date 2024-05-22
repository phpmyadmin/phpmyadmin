<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console\Bookmark;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function is_string;

final class AddController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly BookmarkRepository $bookmarkRepository,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $db = $request->getParsedBodyParam('db');
        $label = $request->getParsedBodyParam('label');
        $bookmarkQuery = $request->getParsedBodyParam('bookmark_query');
        $shared = $request->getParsedBodyParam('shared');

        if (! is_string($label) || ! is_string($db) || ! is_string($bookmarkQuery) || ! is_string($shared)) {
            $this->response->addJSON('message', __('Incomplete params'));

            return $this->response->response();
        }

        $bookmark = $this->bookmarkRepository->createBookmark(
            $bookmarkQuery,
            $label,
            Config::getInstance()->selectedServer['user'],
            $db,
            $shared === 'true',
        );
        if ($bookmark === false || ! $bookmark->save()) {
            $this->response->addJSON('message', __('Failed'));

            return $this->response->response();
        }

        $bookmarkFields = [
            'bkm_database' => $db,
            'bkm_user' => Config::getInstance()->selectedServer['user'],
            'bkm_sql_query' => $bookmarkQuery,
            'bkm_label' => $label,
        ];

        $this->response->addJSON('message', __('Succeeded'));
        $this->response->addJSON('data', $bookmarkFields);
        $this->response->addJSON('isShared', $shared === 'true');

        return $this->response->response();
    }
}
