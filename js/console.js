/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used in or for console
 *
 * @package phpMyAdmin-Console
 */

/**
 * Console object
 */
var PMA_console = {
    /**
     * @var object, jQuery object, selector is '#pma_console>.content'
     * @access private
     */
    $consoleContent: null,
    /**
     * @var object, jQuery object, selector is '#pma_console .content',
     *  used for resizer
     * @access private
     */
    $consoleAllContents: null,
    /**
     * @var object, jQuery object, selector is '#pma_console .toolbar'
     * @access private
     */
    $consoleToolbar: null,
    /**
     * @var object, jQuery object, selector is '#pma_console .template'
     * @access private
     */
    $consoleTemplates: null,
    /**
     * @var object, jQuery object, form for submit
     * @access private
     */
    $requestForm: null,
    /**
     * @var object, contain console config
     * @access private
     */
    config: null,
    /**
     * @var bool, if console element exist, it'll be true
     * @access public
     */
    isEnabled: false,
    /**
     * @var bool, make sure console events bind only once
     * @access private
     */
    isInitialized: false,
    /**
     * Used for console initialize, reinit is ok, just some variable assignment
     *
     * @return void
     */
    initialize: function() {

        if($('#pma_console').length === 0) {
            return;
        }

        PMA_console.isEnabled = true;

        // Cookie var checks and init
        if(! $.cookie('pma_console_height')) {
            $.cookie('pma_console_height', 92);
        }
        if(! $.cookie('pma_console_mode')) {
            $.cookie('pma_console_mode', 'info');
        }

        // Vars init
        PMA_console.$consoleToolbar = $('#pma_console>.toolbar');
        PMA_console.$consoleContent = $('#pma_console>.content');
        PMA_console.$consoleAllContents = $('#pma_console .content');
        PMA_console.$consoleTemplates = $('#pma_console>.templates');

        // Generate a from for post
        PMA_console.$requestForm = $('<form method="post" action="import.php">' +
            '<input name="is_js_confirmed" value="0">' +
            '<textarea name="sql_query"></textarea>' +
            '<input name="console_message_id" value="0">' +
            '<input name="db" value="">' +
            '<input name="table" value="">' +
            '<input name="token" value="' +
            PMA_commonParams.get('token') +
            '">' +
            '</form>'
        );
        PMA_console.$requestForm.bind('submit', AJAX.requestHandler);

        // Event binds shouldn't run again
        if(PMA_console.isInitialized === false) {

            // Load config first
            var tempConfig = JSON.parse($.cookie('pma_console_config'));
            if(tempConfig) {
                if(tempConfig.alwaysExpand === true) {
                    $('#pma_console_options input[name=always_expand]').prop('checked', true);
                }
                if(tempConfig.startHistory === true) {
                    $('#pma_console_options input[name=start_history]').prop('checked', true);
                }
                if(tempConfig.currentQuery === true) {
                    $('#pma_console_options input[name=current_query]').prop('checked', true);
                }
            } else {
                $('#pma_console_options input[name=current_query]').prop('checked', true);
            }

            PMA_console.updateConfig();

            PMA_consoleResizer.initialize();
            PMA_consoleInput.initialize();
            PMA_consoleMessages.initialize();
            PMA_consoleBookmarks.initialize();

            PMA_console.$consoleToolbar.children('.console_switch').click(PMA_console.toggle);
            $(document).keydown(function(event) {
                // Ctrl + Alt + C
                if(event.ctrlKey && event.altKey && event.keyCode === 67) {
                    PMA_console.toggle();
                }
            });

            $('#pma_console .toolbar').children().mousedown(function(event) {
                event.preventDefault();
                event.stopImmediatePropagation();
            });

            $('#pma_console .button.clear').click(function() {
                PMA_consoleMessages.clear();
            });

            $('#pma_console .button.history').click(function() {
                PMA_consoleMessages.showHistory();
            });

            $('#pma_console .button.options').click(function() {
                PMA_console.showCard('#pma_console_options');
            });

            $('#pma_console .mid_layer').click(function() {
                PMA_console.hideCard($(this).parent().children('.card'));
            });
            $('#pma_bookmarks .switch_button').click(function() {
                PMA_console.hideCard($(this).closest('.card'));
            });

            $('#pma_console_options input[type=checkbox]').change(function() {
                PMA_console.updateConfig();
            });

            $('#pma_console_options .button.default').click(function() {
                $('#pma_console_options input[name=always_expand]').prop('checked', false);
                $('#pma_console_options input[name=start_history]').prop('checked', false);
                $('#pma_console_options input[name=current_query]').prop('checked', true);
                PMA_console.updateConfig();
            });

            $(document).ajaxComplete(function (event, xhr) {
                try {
                    var data = $.parseJSON(xhr.responseText);
                    PMA_console.ajaxCallback(data);
                } catch (e) {
                    console.log("Invalid JSON!" + e.message);
                }
            });

            PMA_console.isInitialized = true;
        }

        // Change console mode from cookie
        switch($.cookie('pma_console_mode')) {
            case 'collapse':
                PMA_console.collapse();
                break;
            /* jshint -W086 */// no break needed in default section
            default:
                $.cookie('pma_console_mode', 'info');
            case 'info':
            /* jshint +W086 */
                PMA_console.info();
                break;
            case 'show':
                PMA_console.show(true);
                PMA_console.scrollBottom();
                break;
        }
    },
    /**
     * Execute query and show results in console
     *
     * @return void
     */
    execute: function(queryString, options) {
        if(typeof(queryString) != 'string' || ! /[a-z]|[A-Z]/.test(queryString)){
            return;
        }
        PMA_console.$requestForm.children('textarea').val(queryString);
        if(options && options.db) {
            PMA_console.$requestForm.children('[name=db]').attr('value', options.db);
            if(options.table) {
                PMA_console.$requestForm.children('[name=table]').attr('value', options.table);
            } else {
                PMA_console.$requestForm.children('[name=table]').attr('value', '');
            }
        } else {
            PMA_console.$requestForm.children('[name=db]').attr('value',
                (PMA_commonParams.get('db').length > 0 ? PMA_commonParams.get('db') : ''));
        }
        PMA_console.$requestForm.find('[name=profiling]').remove();
        if(options && options.profiling === true) {
            PMA_console.$requestForm.append('<input name="profiling" value="on">');
        }
        if (! confirmQuery(PMA_console.$requestForm[0], PMA_console.$requestForm.children('textarea')[0])) {
            return;
        }
        PMA_console.$requestForm.children('[name=console_message_id]')
            .attr('value', PMA_consoleMessages.appendQuery({sql_query: queryString}).message_id);
        PMA_console.$requestForm.trigger('submit');
        PMA_consoleInput.clear();
    },
    ajaxCallback: function(data) {
        if(data && data.console_message_id) {
            PMA_consoleMessages.updateQuery(data.console_message_id, data.success,
                (data._reloadQuerywindow ? data._reloadQuerywindow : false));
        } else if( data && data._reloadQuerywindow) {
            if(data._reloadQuerywindow.sql_query.length > 0) {
                PMA_consoleMessages.appendQuery(data._reloadQuerywindow, 'successed')
                    .$message.addClass(PMA_console.config.currentQuery ? '' : 'hide');
            }
        }
    },
    /**
     * Change console to collapse mode
     *
     * @return void
     */
    collapse: function() {
        $.cookie('pma_console_mode', 'collapse');
        var pmaConsoleHeight = $.cookie('pma_console_height');

        if(pmaConsoleHeight < 32) {
            $.cookie('pma_console_height', 92);
        }
        PMA_console.$consoleToolbar.addClass('collapsed');
        PMA_console.$consoleAllContents.height(pmaConsoleHeight);
        PMA_console.$consoleContent.stop();
        PMA_console.$consoleContent.animate({'margin-bottom': -1 * PMA_console.$consoleContent.outerHeight() + 'px'},
            'fast', 'easeOutQuart', function() {
                PMA_console.$consoleContent.css({display:'none'});
                $(window).trigger('resize');
            });
        PMA_console.hideCard();
    },
    /**
     * Show console
     *
     * @param bool inputFocus If true, focus the input line after show()
     * @return void
     */
    show: function(inputFocus) {
        $.cookie('pma_console_mode', 'show');

        var pmaConsoleHeight = $.cookie('pma_console_height');

        if(pmaConsoleHeight < 32) {
            $.cookie('pma_console_height', 32);
            PMA_console.collapse();
            return;
        }
        PMA_console.$consoleContent.css({display:'block'});
        if(PMA_console.$consoleToolbar.hasClass('collapsed')) {
            PMA_console.$consoleToolbar.removeClass('collapsed');
        }
        PMA_console.$consoleAllContents.height(pmaConsoleHeight);
        PMA_console.$consoleContent.stop();
        PMA_console.$consoleContent.animate({'margin-bottom': 0},
            'fast', 'easeOutQuart', function() {
                $(window).trigger('resize');
                if(inputFocus) {
                    PMA_consoleInput.focus();
                }
            });
    },
    /**
     * Change console to SQL information mode
     * this mode shows current SQL query
     * This mode is the default mode
     *
     * @return void
     */
    info: function() {
        // Under construction
        PMA_console.collapse();
    },
    /**
     * Toggle console mode between collapse/show
     * Used for toggle buttons and shortcuts
     *
     * @return void
     */
    toggle: function() {
        switch($.cookie('pma_console_mode')) {
            case 'collapse':
            case 'info':
                PMA_console.show(true);
                break;
            case 'show':
                PMA_console.collapse();
                break;
            default:
                PMA_consoleInitialize();
        }
    },
    /**
     * Scroll console to bottom
     *
     * @return void
     */
    scrollBottom: function() {
        PMA_console.$consoleContent.scrollTop(PMA_console.$consoleContent.prop("scrollHeight"));
    },
    /**
     * Show card
     *
     * @param string cardSelector Selector, select string will be "#pma_console " + cardSelector
     * this param also can be JQuery object, if you need.
     *
     * @return void
     */
    showCard: function(cardSelector) {
        var $card = null;
        if(typeof(cardSelector) !== 'string') {
            if (cardSelector.length > 0) {
                $card = cardSelector;
            } else {
                return;
            }
        } else {
            $card = $("#pma_console " + cardSelector);
        }
        if($card.length === 0) {
            return;
        }
        $card.parent().children('.mid_layer').show().fadeTo(0, 0.15);
        $card.addClass('show');
        PMA_consoleInput.blur();
        if($card.parents('.card').length > 0) {
            PMA_console.showCard($card.parents('.card'));
        }
    },
    /**
     * Scroll console to bottom
     *
     * @param object $targetCard Target card JQuery object, if it's empty, function will hide all cards
     * @return void
     */
    hideCard: function($targetCard) {
        if(! $targetCard) {
            $('#pma_console .mid_layer').fadeOut(140);
            $('#pma_console .card').removeClass('show');
        } else if($targetCard.length > 0) {
            $targetCard.parent().find('.mid_layer').fadeOut(140);
            $targetCard.find('.card').removeClass('show');
            $targetCard.removeClass('show');
        }
    },
    /**
     * Used for update console config
     *
     * @return void
     */
    updateConfig: function() {
        PMA_console.config = {
            alwaysExpand: $('#pma_console_options input[name=always_expand]').prop('checked'),
            startHistory: $('#pma_console_options input[name=start_history]').prop('checked'),
            currentQuery: $('#pma_console_options input[name=current_query]').prop('checked')
        };
        $.cookie('pma_console_config', JSON.stringify(PMA_console.config));
    },
    isSelect: function (queryString) {
        var reg_exp = /^SELECT\s+/i;
        return reg_exp.test(queryString);
    }
};

/**
 * Resizer object
 * Careful: this object UI logics highly related with functions under PMA_console
 * Resizing min-height is 32, if small than it, console will collapse
 */
var PMA_consoleResizer = {
    _posY: 0,
    _height: 0,
    _resultHeight: 0,
    /**
     * Mousedown event handler for bind to resizer
     *
     * @return void
     */
    _mousedown: function(event) {
        if($.cookie('pma_console_mode') !== 'show') {
            return;
        }
        PMA_consoleResizer._posY = event.pageY;
        PMA_consoleResizer._height = PMA_console.$consoleContent.height();
        $(document).mousemove(PMA_consoleResizer._mousemove);
        $(document).mouseup(PMA_consoleResizer._mouseup);
        // Disable text selection while resizing
        $(document).bind('selectstart', function(){ return false; });
    },
    /**
     * Mousemove event handler for bind to resizer
     *
     * @return void
     */
    _mousemove: function(event) {
        PMA_consoleResizer._resultHeight = PMA_consoleResizer._height + (PMA_consoleResizer._posY -event.pageY);
        // Content min-height is 32, if adjusting height small than it we'll move it out of the page
        if(PMA_consoleResizer._resultHeight <= 32) {
            PMA_console.$consoleAllContents.height(32);
            PMA_console.$consoleContent.css('margin-bottom', PMA_consoleResizer._resultHeight - 32);
        }
        else {
            // Logic below makes viewable area always at bottom when adjusting height and content already at bottom
            if(PMA_console.$consoleContent.scrollTop() + PMA_console.$consoleContent.innerHeight() + 16
                >= PMA_console.$consoleContent.prop('scrollHeight')) {
                PMA_console.$consoleAllContents.height(PMA_consoleResizer._resultHeight);
                PMA_console.scrollBottom();
            } else {
                PMA_console.$consoleAllContents.height(PMA_consoleResizer._resultHeight);
            }
        }
    },
    /**
     * Mouseup event handler for bind to resizer
     *
     * @return void
     */
    _mouseup: function() {
        $.cookie('pma_console_height', PMA_consoleResizer._resultHeight);
        PMA_console.show();
        $(document).unbind('mousemove');
        $(document).unbind('mouseup');
        $(document).unbind('selectstart');
    },
    /**
     * Used for console resizer initialize
     *
     * @return void
     */
    initialize: function() {
        $('#pma_console .toolbar').unbind('mousedown');
        $('#pma_console .toolbar').mousedown(PMA_consoleResizer._mousedown);
    }
};


/**
 * Console input object
 */
var PMA_consoleInput = {
    /**
     * @var array, contains Codemirror objects or input jQuery objects
     * @access private
     */
    _inputs: null,
    /**
     * @var bool, if codemirror enabled
     * @access private
     */
    _codemirror: false,
    /**
     * Used for console input initialize
     *
     * @return void
     */
    initialize: function() {
        // _cm object can't be reinitialize
        if(PMA_consoleInput._inputs !== null) {
            return;
        }
        if(typeof CodeMirror !== 'undefined') {
            PMA_consoleInput._codemirror = true;
        }
        PMA_consoleInput._inputs = [];
        if (PMA_consoleInput._codemirror) {
            PMA_consoleInput._inputs.console = CodeMirror($('#pma_console .console_query_input')[0], {
                theme: 'pma',
                mode: 'text/x-sql',
                lineWrapping: true,
                extraKeys: {"Ctrl-Space": "autocomplete"},
                hintOptions: {"completeSingle": false, "completeOnSingleClick": true}
            });
            PMA_consoleInput._inputs.console.on("inputRead", codemirrorAutocompleteOnInputRead);
            if ($('#pma_bookmarks').length !== 0) {
                PMA_consoleInput._inputs.bookmark = CodeMirror($('#pma_console .bookmark_add_input')[0], {
                    theme: 'pma',
                    mode: 'text/x-sql',
                    lineWrapping: true,
                    extraKeys: {"Ctrl-Space": "autocomplete"},
                    hintOptions: {"completeSingle": false, "completeOnSingleClick": true}
                });
                PMA_consoleInput._inputs.bookmark.on("inputRead", codemirrorAutocompleteOnInputRead);
            }
        } else {
            PMA_consoleInput._inputs.console =
                $('<textarea>').appendTo('#pma_console .console_query_input');
            if ($('#pma_bookmarks').length !== 0) {
                PMA_consoleInput._inputs.bookmark =
                    $('<textarea>').appendTo('#pma_console .bookmark_add_input');
            }
        }
        $('#pma_console .console_query_input').keydown(PMA_consoleInput._keydown);
    },
    /**
     * Mousedown event handler for bind to input
     * Shortcut is ESC key
     *
     * @return void
     */
    _keydown: function(event) {
        if(event.ctrlKey && event.keyCode === 13) {
            PMA_consoleInput.execute();
        }
    },
    /**
     * Used for send text to PMA_console.execute()
     *
     * @return void
     */
    execute: function() {
        if (PMA_consoleInput._codemirror) {
            PMA_console.execute(PMA_consoleInput._inputs.console.getValue());
        } else {
            PMA_console.execute(PMA_consoleInput._inputs.console.val());
        }
    },
    /**
     * Used for clear the input
     *
     * @param string target, default target is console input
     * @return void
     */
    clear: function(target) {
        PMA_consoleInput.setText('', target);
    },
    /**
     * Used for set focus to input
     *
     * @return void
     */
    focus: function() {
        PMA_consoleInput._inputs.console.focus();
    },
    /**
     * Used for blur input
     *
     * @return void
     */
    blur: function() {
        if (PMA_consoleInput._codemirror) {
            PMA_consoleInput._inputs.console.getInputField().blur();
        } else {
            PMA_consoleInput._inputs.console.blur();
        }
    },
    /**
     * Used for set text in input
     *
     * @param string text
     * @param string target
     * @return void
     */
    setText: function(text, target) {
        if (PMA_consoleInput._codemirror) {
            switch(target) {
                case 'bookmark':
                    PMA_console.execute(PMA_consoleInput._inputs.bookmark.setValue(text));
                    break;
                default:
                case 'console':
                    PMA_console.execute(PMA_consoleInput._inputs.console.setValue(text));
            }
        } else {
            switch(target) {
                case 'bookmark':
                    PMA_console.execute(PMA_consoleInput._inputs.bookmark.val(text));
                    break;
                default:
                case 'console':
                    PMA_console.execute(PMA_consoleInput._inputs.console.val(text));
            }
        }
    },
    getText: function(target) {
        if (PMA_consoleInput._codemirror) {
            switch(target) {
                case 'bookmark':
                    return PMA_consoleInput._inputs.bookmark.getValue();
                default:
                case 'console':
                    return PMA_consoleInput._inputs.console.getValue();
            }
        } else {
            switch(target) {
                case 'bookmark':
                    return PMA_consoleInput._inputs.bookmark.val();
                default:
                case 'console':
                    return PMA_consoleInput._inputs.console.val();
            }
        }
    }

};


/**
 * Console messages, and message items management object
 */
var PMA_consoleMessages = {
    /**
     * Used for clear the messages
     *
     * @return void
     */
    clear: function() {
        $('#pma_console .content .console_message_container .message:not(.welcome)').addClass('hide');
        $('#pma_console .content .console_message_container .message.failed').remove();
        $('#pma_console .content .console_message_container .message.expanded').find('.action.collapse').click();
    },
    /**
     * Used for show history messages
     *
     * @return void
     */
    showHistory: function() {
        $('#pma_console .content .console_message_container .message.hide').removeClass('hide');
    },
    /**
     * Used for log new message
     *
     * @param string msgString Message to show
     * @param string msgType Message type
     * @return object, {message_id, $message}
     */
    append: function(msgString, msgType) {
        if(typeof(msgString) !== 'string') {
            return false;
        }
        // Generate an ID for each message, we can find them later
        var msgId = Math.round(Math.random()*(899999999999)+100000000000);
        var now = new Date();
        var $newMessage =
            $('<div class="message '
                + (PMA_console.config.alwaysExpand ? 'expanded' : 'collapsed')
                +'" msgid="' + msgId + '"><div class="action_content"></div></div>');
        switch(msgType) {
            case 'query':
                $newMessage.append('<div class="query highlighted"></div>');
                if(PMA_consoleInput._codemirror) {
                    CodeMirror.runMode(msgString,
                        'text/x-sql', $newMessage.children('.query')[0]);
                } else {
                    $newMessage.children('.query').text(msgString);
                }
                $newMessage.children('.action_content')
                    .append(PMA_console.$consoleTemplates.children('.query_actions').html());
                break;
            default:
            case 'normal':
                $newMessage.append('<div>' + msgString + '</div>');
        }
        PMA_consoleMessages._msgEventBinds($newMessage);
        $newMessage.find('span.text.query_time span')
            .text(now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds())
            .parent().attr('title', now);
        return {message_id: msgId,
                $message: $newMessage.appendTo('#pma_console .content .console_message_container')};
    },
    /**
     * Used for log new query
     *
     * @param string queryData Struct should be
     * {sql_query: "Query string", db: "Target DB", table: "Target Table"}
     * @param string state Message state
     * @return object, {message_id: string message id, $message: JQuery object}
     */
    appendQuery: function(queryData, state) {
        var targetMessage = PMA_consoleMessages.append(queryData.sql_query, 'query');
        if(! targetMessage) {
            return false;
        }
        if(queryData.db && queryData.table) {
            targetMessage.$message.attr('targetdb', queryData.db);
            targetMessage.$message.attr('targettable', queryData.table);
            targetMessage.$message.find('.text.targetdb span').text(queryData.db);
        }
        if(PMA_console.isSelect(queryData.sql_query)) {
            targetMessage.$message.addClass('select');
        }
        switch(state) {
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
    },
    _msgEventBinds: function($targetMessage) {
        // Leave unbinded elements, remove binded.
        $targetMessage = $targetMessage.filter(':not(.binded)');
        if($targetMessage.length === 0) {
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
            PMA_consoleInput.setText($(this).parent().siblings('.query').text());
            PMA_consoleInput.focus();
        });
        $targetMessage.find('.action.requery').click(function () {
            var query = $(this).parent().siblings('.query').text();
            var $message = $(this).closest('.message');
            if(confirm(PMA_messages.strConsoleRequeryConfirm + '\n'
                + (query.length<100 ? query : query.slice(0, 100) + '...'))) {
                PMA_console.execute(query, {db: $message.attr('targetdb'), table: $message.attr('targettable')});
            }
        });
        $targetMessage.find('.action.bookmark').click(function () {
            var query = $(this).parent().siblings('.query').text();
            var $message = $(this).closest('.message');
            PMA_consoleBookmarks.addBookmark(query, $message.attr('targetdb'));
            PMA_console.showCard('#pma_bookmarks .card.add');
        });
        $targetMessage.find('.action.edit_bookmark').click(function () {
            var query = $(this).parent().siblings('.query').text();
            var $message = $(this).closest('.message');
            var isShared = $message.find('span.bookmark_label').hasClass('shared');
            var label = $message.find('span.bookmark_label').text();
            PMA_consoleBookmarks.addBookmark(query, $message.attr('targetdb'), label, isShared);
            PMA_console.showCard('#pma_bookmarks .card.add');
        });
        $targetMessage.find('.action.delete_bookmark').click(function () {
            var $message = $(this).closest('.message');
            if(confirm(PMA_messages.strConsoleDeleteBookmarkConfirm + '\n' + $message.find('.bookmark_label').text())) {
                $.post('import.php',
                    {token: PMA_commonParams.get('token'),
                    action_bookmark: 2,
                    ajax_request: true,
                    id_bookmark: $message.attr('bookmarkid')},
                    function () {
                        PMA_consoleBookmarks.refresh();
                    });
            }
        });
        $targetMessage.find('.action.profiling').click(function () {
            var $message = $(this).closest('.message');
            PMA_console.execute($(this).parent().siblings('.query').text(),
                {db: $message.attr('targetdb'),
                table: $message.attr('targettable'),
                profiling: true});
        });
        $targetMessage.find('.action.explain').click(function () {
            var $message = $(this).closest('.message');
            PMA_console.execute('EXPLAIN ' + $(this).parent().siblings('.query').text(),
                {db: $message.attr('targetdb'),
                table: $message.attr('targettable')});
        });
        if(PMA_consoleInput._codemirror) {
            $targetMessage.find('.query:not(.highlighted)').each(function(index, elem) {
                    CodeMirror.runMode($(elem).text(),
                        'text/x-sql', elem);
                    $(this).addClass('highlighted');
                });
        }
    },
    msgAppend: function(msgId, msgString, msgType) {
        var $targetMessage = $('#pma_console .content .console_message_container .message[msgid=' + msgId +']');
        if($targetMessage.length === 0 || isNaN(parseInt(msgId)) || typeof(msgString) !== 'string') {
            return false;
        }
        $targetMessage.append('<div>' + msgString + '</div>');
    },
    updateQuery: function(msgId, isSuccessed, queryData) {
        var $targetMessage = $('#pma_console .console_message_container .message[msgid=' + parseInt(msgId) +']');
        if($targetMessage.length === 0 || isNaN(parseInt(msgId))) {
            return false;
        }
        $targetMessage.removeClass('pending failed successed');
        if(isSuccessed) {
            $targetMessage.addClass('successed');
            if(queryData) {
                $targetMessage.children('.query').text('');
                $targetMessage.removeClass('select');
                if(PMA_console.isSelect(queryData.sql_query)) {
                    $targetMessage.addClass('select');
                }
                if(PMA_consoleInput._codemirror) {
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
    },
    /**
     * Used for console messages initialize
     *
     * @return void
     */
    initialize: function() {
        PMA_consoleMessages._msgEventBinds($('#pma_console .message:not(.binded)'));
        if(PMA_console.config.startHistory) {
            PMA_consoleMessages.showHistory();
        }
    }
};


/**
 * Console bookmarks card, and bookmarks items management object
 */
var PMA_consoleBookmarks = {
    _bookmarks: [],
    addBookmark: function (queryString, targetDb, label, isShared, id) {
        $('#pma_bookmarks .add [name=shared]').prop('checked', false);
        $('#pma_bookmarks .add [name=label]').val('');
        $('#pma_bookmarks .add [name=targetdb]').val('');
        $('#pma_bookmarks .add [name=id_bookmark]').val('');
        PMA_consoleInput.setText('', 'bookmark');

        switch(arguments.length) {
            case 4:
                $('#pma_bookmarks .add [name=shared]').prop('checked', isShared);
            case 3:
                $('#pma_bookmarks .add [name=label]').val(label);
            case 2:
                $('#pma_bookmarks .add [name=targetdb]').val(targetDb);
            case 1:
                PMA_consoleInput.setText(queryString, 'bookmark');
            default:
                break;
        }
    },
    refresh: function () {
        $.get('import.php?console_bookmark_refresh=refresh&token=' + PMA_commonParams.get('token'),
            {'ajax_request': true},
            function(data) {
                if(data.console_message_bookmark) {
                    $('#pma_bookmarks .content.bookmark').html(data.console_message_bookmark);
                    PMA_consoleMessages._msgEventBinds($('#pma_bookmarks .message:not(.binded)'));
                }
            });
    },
    /**
     * Used for console bookmarks initialize
     * message events are already binded by PMA_consoleMsg._msgEventBinds
     *
     * @return void
     */
    initialize: function() {
        if($('#pma_bookmarks').length === 0) {
            return;
        }
        $('#pma_console .button.bookmarks').click(function() {
            PMA_console.showCard('#pma_bookmarks');
        });
        $('#pma_bookmarks .button.add').click(function() {
            PMA_console.showCard('#pma_bookmarks .card.add');
        });
        $('#pma_bookmarks .card.add [name=submit]').click(function () {
            if ($('#pma_bookmarks .card.add [name=label]').val().length === 0
                || PMA_consoleInput.getText('bookmark').length === 0)
            {
                alert(PMA_messages.strFormEmpty);
                return;
            }
            $(this).prop('disabled', true);
            $.post('import.php',
                {token: PMA_commonParams.get('token'),
                ajax_request: true,
                console_bookmark_add: 'true',
                label: $('#pma_bookmarks .card.add [name=label]').val(),
                db: $('#pma_bookmarks .card.add [name=targetdb]').val(),
                bookmark_query: PMA_consoleInput.getText('bookmark'),
                shared: $('#pma_bookmarks .card.add [name=shared]').prop('checked')},
                function () {
                    PMA_consoleBookmarks.refresh();
                    $('#pma_bookmarks .card.add [name=submit]').prop('disabled', false);
                    PMA_console.hideCard($('#pma_bookmarks .card.add'));
                });
        });
        $('#pma_console .button.refresh').click(function() {
            PMA_consoleBookmarks.refresh();
        });
    }
};

/**
 * Executed on page load
 */
$(function () {
    PMA_console.initialize();
});
