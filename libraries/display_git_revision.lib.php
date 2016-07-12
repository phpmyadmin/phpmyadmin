<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for password change
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
* Prints details about the current Git commit revision
*
* @return void
*/
function PMA_printGitRevision()
{
    if (! $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT')) {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
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
            . PMA_linkURL(
                'https://github.com/phpmyadmin/phpmyadmin/commit/'
                . $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITHASH')
            )
            . '" rel="noopener noreferrer" target="_blank">' . $commit_hash . '</a>';
    }

    $branch = $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_BRANCH');
    if ($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_ISREMOTEBRANCH')) {
        $branch = '<a href="'
            . PMA_linkURL(
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
    PMA_printListItem(
        __('Git revision:') . ' '
        . $branch . ',<br /> '
        . sprintf(
            __('committed on %1$s by %2$s'),
            PMA_Util::localisedDate(strtotime($committer['date'])),
            '<a href="' . PMA_linkURL('mailto:' . htmlspecialchars($committer['email'])) . '">'
            . htmlspecialchars($committer['name']) . '</a>'
        )
        . ($author != $committer
            ? ', <br />'
            . sprintf(
                __('authored on %1$s by %2$s'),
                PMA_Util::localisedDate(strtotime($author['date'])),
                '<a href="' . PMA_linkURL('mailto:' . htmlspecialchars($author['email'])) . '">'
                . htmlspecialchars($author['name']) . '</a>'
            )
            : ''),
        'li_pma_version_git', null, null, null
    );
}
