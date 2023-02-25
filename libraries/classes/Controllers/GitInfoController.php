<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Git;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function strtotime;

final class GitInfoController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Config $config)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $this->response->isAjax()) {
            return;
        }

        $git = new Git($this->config->get('ShowGitRevision') ?? true);

        if (! $git->isGitRevision()) {
            return;
        }

        $commit = $git->checkGitRevision();

        if (! $git->hasGitInformation() || $commit === null) {
            $this->response->setRequestStatus(false);

            return;
        }

        $commit['author']['date'] = Util::localisedDate(strtotime($commit['author']['date']));
        $commit['committer']['date'] = Util::localisedDate(strtotime($commit['committer']['date']));

        $this->render('home/git_info', $commit);
    }
}
