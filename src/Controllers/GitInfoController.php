<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use DateTimeImmutable;
use PhpMyAdmin\Config;
use PhpMyAdmin\Git;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;

#[Route('/git-revision', ['GET', 'POST'])]
final class GitInfoController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Config $config)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $git = new Git($this->config->config->ShowGitRevision);

        if (! $git->isGitRevision()) {
            return $this->response->response();
        }

        $commit = $git->checkGitRevision();

        if (! $git->hasGitInformation() || $commit === null) {
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $commit['author']['date'] = Util::localisedDate(new DateTimeImmutable($commit['author']['date']));
        $commit['committer']['date'] = Util::localisedDate(new DateTimeImmutable($commit['committer']['date']));

        $this->response->render('home/git_info', $commit);

        return $this->response->response();
    }
}
