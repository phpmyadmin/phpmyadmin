import $ from 'jquery';
import { CommonParams } from './common.ts';
import { escapeHtml } from './functions/escape.ts';

const GitInfo = {
    /**
     * Version string to integer conversion.
     * @param {string} str
     * @return {number | false}
     */
    parseVersionString: str => {
        if (typeof (str) !== 'string') {
            return false;
        }

        let add = 0;
        // Parse possible alpha/beta/rc/
        const state = str.split('-');
        if (state.length >= 2) {
            if (state[1].startsWith('rc')) {
                add = -20 - parseInt(state[1].substring(2), 10);
            } else if (state[1].startsWith('beta')) {
                add = -40 - parseInt(state[1].substring(4), 10);
            } else if (state[1].startsWith('alpha')) {
                add = -60 - parseInt(state[1].substring(5), 10);
            } else if (state[1].startsWith('dev')) {
                /* We don't handle dev, it's git snapshot */
                add = 0;
            }
        }

        // Parse version
        const x = str.split('.');
        // Use 0 for non existing parts
        const maj = parseInt(x[0], 10) || 0;
        const min = parseInt(x[1], 10) || 0;
        const pat = parseInt(x[2], 10) || 0;
        const hotfix = parseInt(x[3], 10) || 0;

        return maj * 100000000 + min * 1000000 + pat * 10000 + hotfix * 100 + add;
    },

    /**
     * Indicates current available version on main page.
     * @param {object} data
     */
    currentVersion: data => {
        if (data && data.version && data.date) {
            const current = GitInfo.parseVersionString($('span.version').text());
            const latest = GitInfo.parseVersionString(data.version);
            if (current === false || latest === false) {
                return;
            }

            const url = 'index.php?route=/url&url=https://www.phpmyadmin.net/files/' + escapeHtml(encodeURIComponent(data.version)) + '/';
            let versionInformationMessage = document.createElement('span');
            versionInformationMessage.className = 'latest';
            const versionInformationMessageLink = document.createElement('a');
            versionInformationMessageLink.href = url;
            versionInformationMessageLink.className = 'disableAjax';
            versionInformationMessageLink.target = '_blank';
            versionInformationMessageLink.rel = 'noopener noreferrer';
            const versionInformationMessageLinkText = document.createTextNode(data.version);
            versionInformationMessageLink.appendChild(versionInformationMessageLinkText);
            const prefixMessage = document.createTextNode(window.Messages.strLatestAvailable + ' ');
            versionInformationMessage.appendChild(prefixMessage);
            versionInformationMessage.appendChild(versionInformationMessageLink);
            if (latest > current) {
                const message = window.sprintf(
                    window.Messages.strNewerVersion,
                    escapeHtml(data.version),
                    escapeHtml(data.date)
                );
                let htmlClass = 'alert alert-primary';
                if (Math.floor(latest / 10000) === Math.floor(current / 10000)) {
                    /* Security update */
                    htmlClass = 'alert alert-danger';
                }

                $('#newer_version_notice').remove();
                const mainContainerDiv = document.createElement('div');
                mainContainerDiv.id = 'newer_version_notice';
                mainContainerDiv.className = htmlClass;
                const mainContainerDivLink = document.createElement('a');
                mainContainerDivLink.href = url;
                mainContainerDivLink.className = 'disableAjax';
                mainContainerDivLink.target = '_blank';
                mainContainerDivLink.rel = 'noopener noreferrer';
                const mainContainerDivLinkText = document.createTextNode(message);
                mainContainerDivLink.appendChild(mainContainerDivLinkText);
                mainContainerDiv.appendChild(mainContainerDivLink);
                $('#maincontainer').append($(mainContainerDiv));
            }

            let upToDateMessage: Text | null = null;
            if (latest === current) {
                upToDateMessage = document.createTextNode(' (' + window.Messages.strUpToDate + ')');
            }

            /* Remove extra whitespace */
            const versionInfo = $('#li_pma_version').contents().get(2);
            if (typeof versionInfo !== 'undefined') {
                versionInfo.textContent = versionInfo.textContent.trim();
            }

            const $liPmaVersion = $('#li_pma_version');
            $liPmaVersion.find('span.latest').remove();
            if (upToDateMessage !== null) {
                $liPmaVersion.append($(upToDateMessage));
            } else {
                $liPmaVersion.append($(versionInformationMessage));
            }
        }
    },

    /**
     * Loads Git revision data from ajax for index.php
     */
    displayGitRevision: () => {
        $('#is_git_revision').remove();
        $('#li_pma_version_git').remove();
        $.get(
            'index.php?route=/git-revision',
            {
                'server': CommonParams.get('server'),
                'ajax_request': true,
                'no_debug': true
            },
            data => {
                if (typeof data !== 'undefined' && data.success === true) {
                    $(data.message).insertAfter('#li_pma_version');
                }
            }
        );
    },

    /**
     * Load version information asynchronously.
     */
    loadVersion: () => {
        if ($('li.jsversioncheck').length === 0) {
            return;
        }

        $.ajax({
            dataType: 'json',
            url: 'index.php?route=/version-check',
            method: 'POST',
            data: {
                'server': CommonParams.get('server')
            },
            success: GitInfo.currentVersion
        });
    }
};

export function showGitVersion () {
    GitInfo.loadVersion();
    if ($('#is_git_revision').length === 0) {
        return;
    }

    setTimeout(GitInfo.displayGitRevision, 10);
}
