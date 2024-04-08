<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Git;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Util;

use function strtotime;

final class GitInfoController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Config $config)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        if (! $request->isAjax()) {
            return null;
        }

        $git = new Git($this->config->get('ShowGitRevision') ?? true);

        if (! $git->isGitRevision()) {
            return null;
        }

        $commit = $git->checkGitRevision();

        if (! $git->hasGitInformation() || $commit === null) {
            $this->response->setRequestStatus(false);

            return null;
        }

        $commit['author']['date'] = Util::localisedDate(strtotime($commit['author']['date']));
        $commit['committer']['date'] = Util::localisedDate(strtotime($commit['committer']['date']));

        $this->response->render('home/git_info', $commit);

        return null;
    }
}
