/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import CommonParams from '../../variables/common_params';
import { PMA_Messages as messages } from '../../variables/export_variables';

/**
 * @class ConsoleBookmarks
 * Console bookmarks card, and bookmarks items management object
 */
export default class ConsoleBookmarks {
    /**
     * @constructor
     * @param {Object} instance    Instance of the PMA Console
     * @return {void}
     */
    constructor (instance) {
        this._bookmarks = [];
        this.pmaConsole = null;
        this.setPmaConsole = this.setPmaConsole.bind(this);
        this.addBookmark = this.addBookmark.bind(this);
        this.refresh = this.refresh.bind(this);
        this.initialize = this.initialize.bind(this);
        this.setPmaConsole(instance);
    }

    /**
     * @param {Object} instance    Instance of PMA Console
     * @return {void}
     */
    setPmaConsole (instance) {
        this.pmaConsole = instance;
        this.initialize();
    }

    /**
     * @param {string} queryString    Query string to be bookmarked
     * @param {string} targetDb       Target database for the query string
     * @param {string} label          Label for the query
     * @param {bool} isShared         Is the query shared
     * @return {void}
     */
    addBookmark (queryString, targetDb, label, isShared) {
        $('#pma_bookmarks').find('.add [name=shared]').prop('checked', false);
        $('#pma_bookmarks').find('.add [name=label]').val('');
        $('#pma_bookmarks').find('.add [name=targetdb]').val('');
        $('#pma_bookmarks').find('.add [name=id_bookmark]').val('');
        this.pmaConsole.pmaConsoleInput.setText('', 'bookmark');

        switch (arguments.length) {
        case 4:
            $('#pma_bookmarks').find('.add [name=shared]').prop('checked', isShared);
            break;
        case 3:
            $('#pma_bookmarks').find('.add [name=label]').val(label);
            break;
        case 2:
            $('#pma_bookmarks').find('.add [name=targetdb]').val(targetDb);
            break;
        case 1:
            this.pmaConsole.pmaConsoleInput.setText(queryString, 'bookmark');
            break;
        default:
            break;
        }
    }

    /**
     * Method to refresh the bookmak list
     * @return {void}
     */
    refresh () {
        $.get('import.php',
            { ajax_request: true,
                server: CommonParams.get('server'),
                console_bookmark_refresh: 'refresh' },
            function (data) {
                if (data.console_message_bookmark) {
                    $('#pma_bookmarks').find('.content.bookmark').html(data.console_message_bookmark);
                    this.pmaConsole.pmaConsoleMessages._msgEventBinds($('#pma_bookmarks').find('.message:not(.binded)'));
                }
            }.bind(this));
    }

    /**
     * Used for console bookmarks initialize
     * message events are already binded by PMA_consoleMsg._msgEventBinds
     *
     * @return void
     */
    initialize () {
        var self = this;
        if ($('#pma_bookmarks').length === 0) {
            return;
        }
        $('#pma_console').find('.button.bookmarks').click(function () {
            self.pmaConsole.showCard('#pma_bookmarks');
        });
        $('#pma_bookmarks').find('.button.add').click(function () {
            self.pmaConsole.showCard('#pma_bookmarks .card.add');
        });
        $('#pma_bookmarks').find('.card.add [name=submit]').click(function () {
            if ($('#pma_bookmarks').find('.card.add [name=label]').val().length === 0
                || self.pmaConsole.pmaConsoleInput.getText('bookmark').length === 0) {
                alert(messages.strFormEmpty);
                return;
            }
            $(this).prop('disabled', true);
            $.post('import.php',
                {
                    ajax_request: true,
                    console_bookmark_add: 'true',
                    label: $('#pma_bookmarks').find('.card.add [name=label]').val(),
                    server: CommonParams.get('server'),
                    db: $('#pma_bookmarks').find('.card.add [name=targetdb]').val(),
                    bookmark_query: self.pmaConsole.pmaConsoleInput.getText('bookmark'),
                    shared: $('#pma_bookmarks').find('.card.add [name=shared]').prop('checked') },
                function () {
                    self.refresh();
                    $('#pma_bookmarks').find('.card.add [name=submit]').prop('disabled', false);
                    self.pmaConsole.hideCard($('#pma_bookmarks').find('.card.add'));
                });
        });
        $('#pma_console').find('.button.refresh').click(function () {
            self.refresh();
        });
    }
}
