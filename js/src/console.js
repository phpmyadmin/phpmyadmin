/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used in or for console
 *
 * @package phpMyAdmin-Console
 */
import PMA_consoleBookmarks from './classes/Console/PMA_consoleBookmarks';
import PMA_consoleDebug from './classes/Console/PMA_consoleDebug';
import PMA_consoleInput from './classes/Console/PMA_consoleInput';
import PMA_consoleMessages from './classes/Console/PMA_consoleMessages';
import PMA_consoleResizer from './classes/Console/PMA_ConsoleResizer';
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
    pmaConsoleResizer: null,
    pmaConsoleInput: null,
    pmaConsoleMessages: null,
    pmaConsoleBookmarks: null,
    pmaConsoleDebug: null,
    /**
     * Used for console initialize, reinit is ok, just some variable assignment
     *
     * @return void
     */
    initialize: function () {
        if ($('#pma_console').length === 0) {
            return;
        }

        PMA_console.config = configGet('Console', false);

        PMA_console.isEnabled = true;

        // Vars init
        PMA_console.$consoleToolbar = $('#pma_console').find('>.toolbar');
        PMA_console.$consoleContent = $('#pma_console').find('>.content');
        PMA_console.$consoleAllContents = $('#pma_console').find('.content');
        PMA_console.$consoleTemplates = $('#pma_console').find('>.templates');

        // Generate a from for post
        PMA_console.$requestForm = $('<form method="post" action="import.php">' +
            '<input name="is_js_confirmed" value="0">' +
            '<textarea name="sql_query"></textarea>' +
            '<input name="console_message_id" value="0">' +
            '<input name="server" value="">' +
            '<input name="db" value="">' +
            '<input name="table" value="">' +
            '<input name="token" value="">' +
            '</form>'
        );
        PMA_console.$requestForm.children('[name=token]').val(PMA_commonParams.get('token'));
        PMA_console.$requestForm.on('submit', AJAX.requestHandler);

        // Event binds shouldn't run again
        if (PMA_console.isInitialized === false) {
            // Load config first
            if (PMA_console.config.AlwaysExpand === true) {
                $('#pma_console_options input[name=always_expand]').prop('checked', true);
            }
            if (PMA_console.config.StartHistory === true) {
                $('#pma_console_options').find('input[name=start_history]').prop('checked', true);
            }
            if (PMA_console.config.CurrentQuery === true) {
                $('#pma_console_options').find('input[name=current_query]').prop('checked', true);
            }
            if (PMA_console.config.EnterExecutes === true) {
                $('#pma_console_options').find('input[name=enter_executes]').prop('checked', true);
            }
            if (PMA_console.config.DarkTheme === true) {
                $('#pma_console_options').find('input[name=dark_theme]').prop('checked', true);
                $('#pma_console').find('>.content').addClass('console_dark_theme');
            }

            PMA_console.pmaConsoleResizer = new PMA_consoleResizer(PMA_console);
            PMA_console.pmaConsoleInput = new PMA_consoleInput(PMA_console);
            PMA_console.pmaConsoleMessages = new PMA_consoleMessages(PMA_console);
            PMA_console.pmaConsoleBookmarks = new PMA_consoleBookmarks(PMA_console);
            PMA_console.pmaConsoleDebug = new PMA_consoleDebug(PMA_console);

            PMA_console.$consoleToolbar.children('.console_switch').click(PMA_console.toggle);

            $('#pma_console').find('.toolbar').children().mousedown(function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
            });

            $('#pma_console').find('.button.clear').click(function () {
                PMA_console.pmaConsoleMessages.clear();
            });

            $('#pma_console').find('.button.history').click(function () {
                PMA_console.pmaConsoleMessages.showHistory();
            });

            $('#pma_console').find('.button.options').click(function () {
                PMA_console.showCard('#pma_console_options');
            });

            $('#pma_console').find('.button.debug').click(function () {
                PMA_console.showCard('#debug_console');
            });

            PMA_console.$consoleContent.click(function (event) {
                if (event.target === this) {
                    PMA_console.pmaConsoleInput.focus();
                }
            });

            $('#pma_console').find('.mid_layer').click(function () {
                PMA_console.hideCard($(this).parent().children('.card'));
            });
            $('#debug_console').find('.switch_button').click(function () {
                PMA_console.hideCard($(this).closest('.card'));
            });
            $('#pma_bookmarks').find('.switch_button').click(function () {
                PMA_console.hideCard($(this).closest('.card'));
            });
            $('#pma_console_options').find('.switch_button').click(function () {
                PMA_console.hideCard($(this).closest('.card'));
            });

            $('#pma_console_options').find('input[type=checkbox]').change(function () {
                PMA_console.updateConfig();
            });

            $('#pma_console_options').find('.button.default').click(function () {
                $('#pma_console_options input[name=always_expand]').prop('checked', false);
                $('#pma_console_options').find('input[name=start_history]').prop('checked', false);
                $('#pma_console_options').find('input[name=current_query]').prop('checked', true);
                $('#pma_console_options').find('input[name=enter_executes]').prop('checked', false);
                $('#pma_console_options').find('input[name=dark_theme]').prop('checked', false);
                PMA_console.updateConfig();
            });

            $('#pma_console_options').find('input[name=enter_executes]').change(function () {
                PMA_console.pmaConsoleMessages.showInstructions(PMA_console.config.EnterExecutes);
            });

            $(document).ajaxComplete(function (event, xhr, ajaxOptions) {
                if (ajaxOptions.dataType && ajaxOptions.dataType.indexOf('json') !== -1) {
                    return;
                }
                if (xhr.status !== 200) {
                    return;
                }
                try {
                    var data = JSON.parse(xhr.responseText);
                    PMA_console.ajaxCallback(data);
                } catch (e) {
                    console.trace();
                    console.log('Failed to parse JSON: ' + e.message);
                }
            });

            PMA_console.isInitialized = true;
        }

        // Change console mode from cookie
        switch (PMA_console.config.Mode) {
        case 'collapse':
            PMA_console.collapse();
            break;
            /* jshint -W086 */// no break needed in default section
        case 'info':
            /* jshint +W086 */
            PMA_console.info();
            break;
        case 'show':
            PMA_console.show(true);
            PMA_console.scrollBottom();
            break;
        default:
            PMA_console.setConfig('Mode', 'info');
        }
    },
    /**
     * Execute query and show results in console
     *
     * @return void
     */
    execute: function (queryString, options) {
        if (typeof(queryString) !== 'string' || ! /[a-z]|[A-Z]/.test(queryString)) {
            return;
        }
        PMA_console.$requestForm.children('textarea').val(queryString);
        PMA_console.$requestForm.children('[name=server]').attr('value', PMA_commonParams.get('server'));
        if (options && options.db) {
            PMA_console.$requestForm.children('[name=db]').val(options.db);
            if (options.table) {
                PMA_console.$requestForm.children('[name=table]').val(options.table);
            } else {
                PMA_console.$requestForm.children('[name=table]').val('');
            }
        } else {
            PMA_console.$requestForm.children('[name=db]').val(
                (PMA_commonParams.get('db').length > 0 ? PMA_commonParams.get('db') : ''));
        }
        PMA_console.$requestForm.find('[name=profiling]').remove();
        if (options && options.profiling === true) {
            PMA_console.$requestForm.append('<input name="profiling" value="on">');
        }
        if (! confirmQuery(PMA_console.$requestForm[0], PMA_console.$requestForm.children('textarea')[0].value)) {
            return;
        }
        PMA_console.$requestForm.children('[name=console_message_id]')
            .val(PMA_console.pmaConsoleMessages.appendQuery({ sql_query: queryString }).message_id);
        PMA_console.$requestForm.trigger('submit');
        PMA_console.pmaConsoleInput.clear();
        PMA_reloadNavigation();
    },
    ajaxCallback: function (data) {
        if (data && data.console_message_id) {
            PMA_console.pmaConsoleMessages.updateQuery(data.console_message_id, data.success,
                (data._reloadQuerywindow ? data._reloadQuerywindow : false));
        } else if (data && data._reloadQuerywindow) {
            if (data._reloadQuerywindow.sql_query.length > 0) {
                PMA_console.pmaConsoleMessages.appendQuery(data._reloadQuerywindow, 'successed')
                    .$message.addClass(PMA_console.config.CurrentQuery ? '' : 'hide');
            }
        }
    },
    /**
     * Change console to collapse mode
     *
     * @return void
     */
    collapse: function () {
        PMA_console.setConfig('Mode', 'collapse');
        var pmaConsoleHeight = Math.max(92, PMA_console.config.Height);

        PMA_console.$consoleToolbar.addClass('collapsed');
        PMA_console.$consoleAllContents.height(pmaConsoleHeight);
        PMA_console.$consoleContent.stop();
        PMA_console.$consoleContent.animate({ 'margin-bottom': -1 * PMA_console.$consoleContent.outerHeight() + 'px' },
            'fast', 'easeOutQuart', function () {
                PMA_console.$consoleContent.css({ display:'none' });
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
    show: function (inputFocus) {
        PMA_console.setConfig('Mode', 'show');

        var pmaConsoleHeight = Math.max(92, PMA_console.config.Height);
        pmaConsoleHeight = Math.min(PMA_console.config.Height, (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight) - 25);
        PMA_console.$consoleContent.css({ display:'block' });
        if (PMA_console.$consoleToolbar.hasClass('collapsed')) {
            PMA_console.$consoleToolbar.removeClass('collapsed');
        }
        PMA_console.$consoleAllContents.height(pmaConsoleHeight);
        PMA_console.$consoleContent.stop();
        PMA_console.$consoleContent.animate({ 'margin-bottom': 0 },
            'fast', 'easeOutQuart', function () {
                $(window).trigger('resize');
                if (inputFocus) {
                    PMA_console.pmaConsoleInput.focus();
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
    info: function () {
        // Under construction
        PMA_console.collapse();
    },
    /**
     * Toggle console mode between collapse/show
     * Used for toggle buttons and shortcuts
     *
     * @return void
     */
    toggle: function () {
        switch (PMA_console.config.Mode) {
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
    scrollBottom: function () {
        PMA_console.$consoleContent.scrollTop(PMA_console.$consoleContent.prop('scrollHeight'));
    },
    /**
     * Show card
     *
     * @param string cardSelector Selector, select string will be "#pma_console " + cardSelector
     * this param also can be JQuery object, if you need.
     *
     * @return void
     */
    showCard: function (cardSelector) {
        var $card = null;
        if (typeof(cardSelector) !== 'string') {
            if (cardSelector.length > 0) {
                $card = cardSelector;
            } else {
                return;
            }
        } else {
            $card = $('#pma_console ' + cardSelector);
        }
        if ($card.length === 0) {
            return;
        }
        $card.parent().children('.mid_layer').show().fadeTo(0, 0.15);
        $card.addClass('show');
        PMA_console.pmaConsoleInput.blur();
        if ($card.parents('.card').length > 0) {
            PMA_console.showCard($card.parents('.card'));
        }
    },
    /**
     * Scroll console to bottom
     *
     * @param object $targetCard Target card JQuery object, if it's empty, function will hide all cards
     * @return void
     */
    hideCard: function ($targetCard) {
        if (! $targetCard) {
            $('#pma_console').find('.mid_layer').fadeOut(140);
            $('#pma_console').find('.card').removeClass('show');
        } else if ($targetCard.length > 0) {
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
    updateConfig: function () {
        PMA_console.setConfig('AlwaysExpand', $('#pma_console_options input[name=always_expand]').prop('checked'));
        PMA_console.setConfig('StartHistory', $('#pma_console_options').find('input[name=start_history]').prop('checked'));
        PMA_console.setConfig('CurrentQuery', $('#pma_console_options').find('input[name=current_query]').prop('checked'));
        PMA_console.setConfig('EnterExecutes', $('#pma_console_options').find('input[name=enter_executes]').prop('checked'));
        PMA_console.setConfig('DarkTheme', $('#pma_console_options').find('input[name=dark_theme]').prop('checked'));
        /* Setting the dark theme of the console*/
        if (PMA_console.config.DarkTheme) {
            $('#pma_console').find('>.content').addClass('console_dark_theme');
        } else {
            $('#pma_console').find('>.content').removeClass('console_dark_theme');
        }
    },
    setConfig: function (key, value) {
        PMA_console.config[key] = value;
        configSet('Console/' + key, value);
    },
    isSelect: function (queryString) {
        var reg_exp = /^SELECT\s+/i;
        return reg_exp.test(queryString);
    }
};

export default PMA_console;
