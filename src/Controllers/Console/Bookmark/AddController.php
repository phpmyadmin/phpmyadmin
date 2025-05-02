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

final readonly class AddController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private BookmarkRepository $bookmarkRepository,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $db = $request->getParsedBodyParamAsString('db');
        $label = $request->getParsedBodyParamAsString('label');
        $bookmarkQuery = $request->getParsedBodyParamAsString('bookmark_query');
        $shared = $request->getParsedBodyParamAsString('shared');

        $bookmark = $this->bookmarkRepository->createBookmark(
            $bookmarkQuery,
            $label,
            $this->config->selectedServer['user'],
            $db,
            $shared === 'true',
        );
        if ($bookmark === false || ! $bookmark->save()) {
            $this->response->addJSON('message', __('Failed'));

            return $this->response->response();
        }

        $bookmarkFields = [
            'bkm_database' => $db,
            'bkm_user' => $this->config->selectedServer['user'],
            'bkm_sql_query' => $bookmarkQuery,
            'bkm_label' => $label,
        ];

        $this->response->addJSON('message', __('Succeeded'));
        $this->response->addJSON('data', $bookmarkFields);
        $this->response->addJSON('isShared', $shared === 'true');

        return $this->response->response();
    }
}
