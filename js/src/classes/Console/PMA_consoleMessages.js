/**
 * Console messages, and message items management object
 */
export default class PMA_consoleMessages {
    constructor (instance) {
        this.pmaConsole = null;
        this.clear = this.clear.bind(this);
        this.showHistory = this.showHistory.bind(this);
        this.getHistory = this.getHistory.bind(this);
        this.showInstructions = this.showInstructions.bind(this);
        this.append = this.append.bind(this);
        this.appendQuery = this.appendQuery.bind(this);
        this._msgEventBinds = this._msgEventBinds.bind(this);
        this.msgAppend = this.msgAppend.bind(this);
        this.updateQuery = this.updateQuery.bind(this);
        this.initialize = this.initialize.bind(this);
        this.setPmaConsole = this.setPmaConsole.bind(this);
        this.setPmaConsole(instance);
    }
    setPmaConsole (instance) {
        this.pmaConsole = instance;
        this.initialize();
    }
    /**
     * Used for clear the messages
     *
     * @return void
     */
    clear () {
        $('#pma_console').find('.content .console_message_container .message:not(.welcome)').addClass('hide');
        $('#pma_console').find('.content .console_message_container .message.failed').remove();
        $('#pma_console').find('.content .console_message_container .message.expanded').find('.action.collapse').click();
    }
    /**
     * Used for show history messages
     *
     * @return void
     */
    showHistory () {
        $('#pma_console').find('.content .console_message_container .message.hide').removeClass('hide');
    }
    /**
     * Used for getting a perticular history query
     *
     * @param int nthLast get nth query message from latest, i.e 1st is last
     * @return string message
     */
    getHistory (nthLast) {
        var $queries = $('#pma_console').find('.content .console_message_container .query');
        var length = $queries.length;
        var $query = $queries.eq(length - nthLast);
        if (!$query || (length - nthLast) < 0) {
            return false;
        } else {
            return $query.text();
        }
    }
    /**
     * Used to show the correct message depending on which key
     * combination executes the query (Ctrl+Enter or Enter).
     *
     * @param bool enterExecutes Only Enter has to be pressed to execute query.
     * @return void
     */
    showInstructions (enterExecutes) {
        enterExecutes = +enterExecutes || 0; // conversion to int
        var $welcomeMsg = $('#pma_console').find('.content .console_message_container .message.welcome span');
        $welcomeMsg.children('[id^=instructions]').hide();
        $welcomeMsg.children('#instructions-' + enterExecutes).show();
    }
    /**
     * Used for log new message
     *
     * @param string msgString Message to show
     * @param string msgType Message type
     * @return object, {message_id, $message}
     */
    append (msgString, msgType) {
        if (typeof(msgString) !== 'string') {
            return false;
        }
        // Generate an ID for each message, we can find them later
        var msgId = Math.round(Math.random() * (899999999999) + 100000000000);
        var now = new Date();
        var $newMessage =
            $('<div class="message ' +
                (this.pmaConsole.config.AlwaysExpand ? 'expanded' : 'collapsed') +
                '" msgid="' + msgId + '"><div class="action_content"></div></div>');
        switch (msgType) {
        case 'query':
            $newMessage.append('<div class="query highlighted"></div>');
            if (this.pmaConsole.pmaConsoleInput._codemirror) {
                CodeMirror.runMode(msgString,
                    'text/x-sql', $newMessage.children('.query')[0]);
            } else {
                $newMessage.children('.query').text(msgString);
            }
            $newMessage.children('.action_content')
                .append(this.pmaConsole.$consoleTemplates.children('.query_actions').html());
            break;
        default:
        case 'normal':
            $newMessage.append('<div>' + msgString + '</div>');
        }
        this._msgEventBinds($newMessage);
        $newMessage.find('span.text.query_time span')
            .text(now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds())
            .parent().attr('title', now);
        return { message_id: msgId,
            $message: $newMessage.appendTo('#pma_console .content .console_message_container') };
    }
    /**
     * Used for log new query
     *
     * @param string queryData Struct should be
     * {sql_query: "Query string", db: "Target DB", table: "Target Table"}
     * @param string state Message state
     * @return object, {message_id: string message id, $message: JQuery object}
     */
    appendQuery (queryData, state) {
        var targetMessage = this.append(queryData.sql_query, 'query');
        if (! targetMessage) {
            return false;
        }
        if (queryData.db && queryData.table) {
            targetMessage.$message.attr('targetdb', queryData.db);
            targetMessage.$message.attr('targettable', queryData.table);
            targetMessage.$message.find('.text.targetdb span').text(queryData.db);
        }
        if (this.pmaConsole.isSelect(queryData.sql_query)) {
            targetMessage.$message.addClass('select');
        }
        switch (state) {
        case 'failed':
            targetMessage.$message.addClass('failed');
            break;
        case 'successed':
            targetMessage.$message.addClass('successed');
            break;
        default:
        case 'pending':
            targetMessage.$message.addClass('pending');
        }
        return targetMessage;
    }
    _msgEventBinds ($targetMessage) {
        var self = this;
        // Leave unbinded elements, remove binded.
        $targetMessage = $targetMessage.filter(':not(.binded)');
        if ($targetMessage.length === 0) {
            return;
        }
        $targetMessage.addClass('binded');

        $targetMessage.find('.action.expand').click(function () {
            $(this).closest('.message').removeClass('collapsed');
            $(this).closest('.message').addClass('expanded');
        });
        $targetMessage.find('.action.collapse').click(function () {
            $(this).closest('.message').addClass('collapsed');
            $(this).closest('.message').removeClass('expanded');
        });
        $targetMessage.find('.action.edit').click(function () {
            self.pmaConsole.pmaConsoleInput.setText($(this).parent().siblings('.query').text());
            self.pmaConsole.pmaConsoleInput.focus();
        });
        $targetMessage.find('.action.requery').click(function () {
            var query = $(this).parent().siblings('.query').text();
            var $message = $(this).closest('.message');
            if (confirm(PMA_messages.strConsoleRequeryConfirm + '\n' +
                (query.length < 100 ? query : query.slice(0, 100) + '...'))
            ) {
                self.pmaConsole.execute(query, { db: $message.attr('targetdb'), table: $message.attr('targettable') });
            }
        });
        $targetMessage.find('.action.bookmark').click(function () {
            var query = $(this).parent().siblings('.query').text();
            var $message = $(this).closest('.message');
            self.pmaConsole.pmaConsoleBookmarks.addBookmark(query, $message.attr('targetdb'));
            self.pmaConsole.showCard('#pma_bookmarks .card.add');
        });
        $targetMessage.find('.action.edit_bookmark').click(function () {
            var query = $(this).parent().siblings('.query').text();
            var $message = $(this).closest('.message');
            var isShared = $message.find('span.bookmark_label').hasClass('shared');
            var label = $message.find('span.bookmark_label').text();
            self.pmaConsole.pmaConsoleBookmarks.addBookmark(query, $message.attr('targetdb'), label, isShared);
            self.pmaConsole.showCard('#pma_bookmarks .card.add');
        });
        $targetMessage.find('.action.delete_bookmark').click(function () {
            var $message = $(this).closest('.message');
            if (confirm(PMA_messages.strConsoleDeleteBookmarkConfirm + '\n' + $message.find('.bookmark_label').text())) {
                $.post('import.php',
                    {
                        server: PMA_commonParams.get('server'),
                        action_bookmark: 2,
                        ajax_request: true,
                        id_bookmark: $message.attr('bookmarkid') },
                    function () {
                        self.pmaConsole.pmaConsoleBookmarks.refresh();
                    });
            }
        });
        $targetMessage.find('.action.profiling').click(function () {
            var $message = $(this).closest('.message');
            self.pmaConsole.execute($(this).parent().siblings('.query').text(),
                { db: $message.attr('targetdb'),
                    table: $message.attr('targettable'),
                    profiling: true });
        });
        $targetMessage.find('.action.explain').click(function () {
            var $message = $(this).closest('.message');
            self.pmaConsole.execute('EXPLAIN ' + $(this).parent().siblings('.query').text(),
                { db: $message.attr('targetdb'),
                    table: $message.attr('targettable') });
        });
        $targetMessage.find('.action.dbg_show_trace').click(function () {
            var $message = $(this).closest('.message');
            if (!$message.find('.trace').length) {
                self.pmaConsole.pmaConsoleDebug.getQueryDetails(
                    $message.data('queryInfo'),
                    $message.data('totalTime'),
                    $message
                );
                self._msgEventBinds($message.find('.message:not(.binded)'));
            }
            $message.addClass('show_trace');
            $message.removeClass('hide_trace');
        });
        $targetMessage.find('.action.dbg_hide_trace').click(function () {
            var $message = $(this).closest('.message');
            $message.addClass('hide_trace');
            $message.removeClass('show_trace');
        });
        $targetMessage.find('.action.dbg_show_args').click(function () {
            var $message = $(this).closest('.message');
            $message.addClass('show_args expanded');
            $message.removeClass('hide_args collapsed');
        });
        $targetMessage.find('.action.dbg_hide_args').click(function () {
            var $message = $(this).closest('.message');
            $message.addClass('hide_args collapsed');
            $message.removeClass('show_args expanded');
        });
        if (self.pmaConsole.pmaConsoleInput._codemirror) {
            $targetMessage.find('.query:not(.highlighted)').each(function (index, elem) {
                CodeMirror.runMode($(elem).text(),
                    'text/x-sql', elem);
                $(this).addClass('highlighted');
            });
        }
    }
    msgAppend (msgId, msgString, msgType) {
        var $targetMessage = $('#pma_console').find('.content .console_message_container .message[msgid=' + msgId + ']');
        if ($targetMessage.length === 0 || isNaN(parseInt(msgId)) || typeof(msgString) !== 'string') {
            return false;
        }
        $targetMessage.append('<div>' + msgString + '</div>');
    }
    updateQuery (msgId, isSuccessed, queryData) {
        var $targetMessage = $('#pma_console').find('.console_message_container .message[msgid=' + parseInt(msgId) + ']');
        if ($targetMessage.length === 0 || isNaN(parseInt(msgId))) {
            return false;
        }
        $targetMessage.removeClass('pending failed successed');
        if (isSuccessed) {
            $targetMessage.addClass('successed');
            if (queryData) {
                $targetMessage.children('.query').text('');
                $targetMessage.removeClass('select');
                if (this.pmaConsole.isSelect(queryData.sql_query)) {
                    $targetMessage.addClass('select');
                }
                if (this.pmaConsole.pmaConsoleInput._codemirror) {
                    CodeMirror.runMode(queryData.sql_query, 'text/x-sql', $targetMessage.children('.query')[0]);
                } else {
                    $targetMessage.children('.query').text(queryData.sql_query);
                }
                $targetMessage.attr('targetdb', queryData.db);
                $targetMessage.attr('targettable', queryData.table);
                $targetMessage.find('.text.targetdb span').text(queryData.db);
            }
        } else {
            $targetMessage.addClass('failed');
        }
    }
    /**
     * Used for console messages initialize
     *
     * @return void
     */
    initialize () {
        this._msgEventBinds($('#pma_console').find('.message:not(.binded)'));
        if (this.pmaConsole.config.StartHistory) {
            this.showHistory();
        }
        this.showInstructions(this.pmaConsole.config.EnterExecutes);
    }
}
