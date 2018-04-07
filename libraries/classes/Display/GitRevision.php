<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays git revision
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Display;

use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Display\GitRevision class
 *
 * @package PhpMyAdmin
 */
class GitRevision
{
    /**
    * Prints details about the current Git commit revision
    *
    * @return void
    */
    public static function display()
    {
        if (! $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT')) {
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            return;
        }

        // load revision data from repo
        $GLOBALS['PMA_Config']->checkGitRevision();

        // if using a remote commit fast-forwarded, link to GitHub
        $commit_hash = substr(
            $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITHASH'),
            0,
            7
        );
        $commit_hash = '<strong title="'
            . htmlspecialchars($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_MESSAGE'))
            . '">' . $commit_hash . '</strong>';
        if ($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_ISREMOTECOMMIT')) {
            $commit_hash = '<a href="'
                . Core::linkURL(
                    'https://github.com/phpmyadmin/phpmyadmin/commit/'
                    . $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITHASH')
                )
                . '" rel="noopener noreferrer" target="_blank">' . $commit_hash . '</a>';
        }

        $branch = $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_BRANCH');
        if ($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_ISREMOTEBRANCH')) {
            $branch = '<a href="'
                . Core::linkURL(
                    'https://github.com/phpmyadmin/phpmyadmin/tree/'
                    . $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_BRANCH')
                )
                . '" rel="noopener noreferrer" target="_blank">' . $branch . '</a>';
        }
        if ($branch !== false) {
            $branch = sprintf(__('%1$s from %2$s branch'), $commit_hash, $branch);
        } else {
            $branch = $commit_hash . ' (' . __('no branch') . ')';
        }

        $committer = $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITTER');
        $author = $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_AUTHOR');
        Core::printListItem(
            __('Git revision:') . ' '
            . $branch . ',<br /> '
            . sprintf(
                __('committed on %1$s by %2$s'),
                Util::localisedDate(strtotime($committer['date'])),
                '<a href="' . Core::linkURL(
                    'mailto:' . htmlspecialchars($committer['email'])
                ) . '">'
                . htmlspecialchars($committer['name']) . '</a>'
            )
            . ($author != $committer
                ? ', <br />'
                . sprintf(
                    __('authored on %1$s by %2$s'),
                    Util::localisedDate(strtotime($author['date'])),
                    '<a href="' . Core::linkURL(
                        'mailto:' . htmlspecialchars($author['email'])
                    ) . '">'
                    . htmlspecialchars($author['name']) . '</a>'
                )
                : ''),
            'li_pma_version_git', null, null, null
        );
    }
}
