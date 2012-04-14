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
*/
function PMA_printGitRevision()
{
    // if using a remote commit fast-forwarded, link to Github
    $commit_hash = substr($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITHASH'), 0, 7);
    $commit_hash = '<strong title="' 
        . htmlspecialchars($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_MESSAGE')) . '">'
        . $commit_hash . '</strong>';
    if ($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_ISREMOTECOMMIT')) {
        $commit_hash =
            '<a href="'
            . PMA_linkURL('https://github.com/phpmyadmin/phpmyadmin/commit/'
                . $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITHASH'))
            . '" target="_blank">' . $commit_hash . '</a>';
    }

    $branch = $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_BRANCH');
    if ($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_ISREMOTEBRANCH')) {
        $branch =
            '<a href="'
            . PMA_linkURL('https://github.com/phpmyadmin/phpmyadmin/tree/'
                . $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_BRANCH'))
            . '" target="_blank">' . $branch . '</a>';
    }

    PMA_printListItem(__('Git revision') . ': '
        . sprintf(
            __('%s from %s branch'), $commit_hash, $branch) . ',<br /> '
        . sprintf(
            __('committed on %s by %s'), 
            PMA_localisedDate(strtotime($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITTER')['date'])),
            '<a href="' . PMA_linkURL('mailto:' . $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITTER')['email']) . '">' 
                . htmlspecialchars($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITTER')['name']) . '</a>')
        . ($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_AUTHOR') != $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_COMMITTER')
            ? ', <br />' 
            . sprintf(
                __('authored on %s by %s'), 
                PMA_localisedDate(strtotime($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_AUTHOR')['date'])),
                '<a href="' . PMA_linkURL('mailto:' . $GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_AUTHOR')['email']) . '">' 
                    . htmlspecialchars($GLOBALS['PMA_Config']->get('PMA_VERSION_GIT_AUTHOR')['name']) . '</a>')
            : ''),
        'li_pma_version_git', null, null, null);
}
