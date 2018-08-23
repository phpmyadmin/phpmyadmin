/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Used in or for console
 *
 * @package phpMyAdmin-Console
 */

/**
 * Module import
 */
import ConsoleBookmarks from './classes/Console/PMA_consoleBookmarks';
import ConsoleDebug from './classes/Console/PMA_consoleDebug';
import ConsoleInput from './classes/Console/PMA_consoleInput';
import ConsoleMessages from './classes/Console/PMA_consoleMessages';
import ConsoleResizer from './classes/Console/PMA_ConsoleResizer';

/**
 * Console object
 */
var Console = {
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
     * @var object, instance of PMA Console Resizer
     */
    pmaConsoleResizer: null,
    /**
     * @var object, instance of PMA Console Input
     */
    pmaConsoleInput: null,
    /**
     * @var object, instance of PMA Console Messages
     */
    pmaConsoleMessages: null,
    /**
     * @var object, instance of PMA Console Bookmaks
     */
    pmaConsoleBookmarks: null,
    /**
     * @var object, instance of PMA Console Debug
     */
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

        Console.config = configGet('Console', false);

        Console.isEnabled = true;

        // Vars init
        Console.$consoleToolbar = $('#pma_console').find('>.toolbar');
        Console.$consoleContent = $('#pma_console').find('>.content');
        Console.$consoleAllContents = $('#pma_console').find('.content');
        Console.$consoleTemplates = $('#pma_console').find('>.templates');

        // Generate a from for post
        Console.$requestForm = $('<form method="post" action="import.php">' +
            '<input name="is_js_confirmed" value="0">' +
            '<textarea name="sql_query"></textarea>' +
            '<input name="console_message_id" value="0">' +
            '<input name="server" value="">' +
            '<input name="db" value="">' +
            '<input name="table" value="">' +
            '<input name="token" value="">' +
            '</form>'
        );
        Console.$requestForm.children('[name=token]').val(PMA_commonParams.get('token'));
        Console.$requestForm.on('submit', AJAX.requestHandler);

        // Event binds shouldn't run again
        if (Console.isInitialized === false) {
            // Load config first
            if (Console.config.AlwaysExpand === true) {
                $('#pma_console_options input[name=always_expand]').prop('checked', true);
            }
            if (Console.config.StartHistory === true) {
                $('#pma_console_options').find('input[name=start_history]').prop('checked', true);
            }
            if (Console.config.CurrentQuery === true) {
                $('#pma_console_options').find('input[name=current_query]').prop('checked', true);
            }
            if (Console.config.EnterExecutes === true) {
                $('#pma_console_options').find('input[name=enter_executes]').prop('checked', true);
            }
            if (Console.config.DarkTheme === true) {
                $('#pma_console_options').find('input[name=dark_theme]').prop('checked', true);
                $('#pma_console').find('>.content').addClass('console_dark_theme');
            }

            // Instances of helper classes
            Console.pmaConsoleResizer = new ConsoleResizer(Console);
            Console.pmaConsoleInput = new ConsoleInput(Console);
            Console.pmaConsoleMessages = new ConsoleMessages(Console);
            Console.pmaConsoleBookmarks = new ConsoleBookmarks(Console);
            Console.pmaConsoleDebug = new ConsoleDebug(Console);

            Console.$consoleToolbar.children('.console_switch').click(Console.toggle);

            $('#pma_console').find('.toolbar').children().mousedown(function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
            });

            $('#pma_console').find('.button.clear').click(function () {
                Console.pmaConsoleMessages.clear();
            });

            $('#pma_console').find('.button.history').click(function () {
                Console.pmaConsoleMessages.showHistory();
            });

            $('#pma_console').find('.button.options').click(function () {
                Console.showCard('#pma_console_options');
            });

            $('#pma_console').find('.button.debug').click(function () {
                Console.showCard('#debug_console');
            });

            Console.$consoleContent.click(function (event) {
                if (event.target === this) {
                    Console.pmaConsoleInput.focus();
                }
            });

            $('#pma_console').find('.mid_layer').click(function () {
                Console.hideCard($(this).parent().children('.card'));
            });
            $('#debug_console').find('.switch_button').click(function () {
                Console.hideCard($(this).closest('.card'));
            });
            $('#pma_bookmarks').find('.switch_button').click(function () {
                Console.hideCard($(this).closest('.card'));
            });
            $('#pma_console_options').find('.switch_button').click(function () {
                Console.hideCard($(this).closest('.card'));
            });

            $('#pma_console_options').find('input[type=checkbox]').change(function () {
                Console.updateConfig();
            });

            $('#pma_console_options').find('.button.default').click(function () {
                $('#pma_console_options input[name=always_expand]').prop('checked', false);
                $('#pma_console_options').find('input[name=start_history]').prop('checked', false);
                $('#pma_console_options').find('input[name=current_query]').prop('checked', true);
                $('#pma_console_options').find('input[name=enter_executes]').prop('checked', false);
                $('#pma_console_options').find('input[name=dark_theme]').prop('checked', false);
                Console.updateConfig();
            });

            $('#pma_console_options').find('input[name=enter_executes]').change(function () {
                Console.pmaConsoleMessages.showInstructions(Console.config.EnterExecutes);
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
                    Console.ajaxCallback(data);
                } catch (e) {
                    console.trace();
                    console.log('Failed to parse JSON: ' + e.message);
                }
            });

            Console.isInitialized = true;
        }

        // Change console mode from cookie
        switch (Console.config.Mode) {
        case 'collapse':
            Console.collapse();
            break;
            /* jshint -W086 */// no break needed in default section
        case 'info':
            /* jshint +W086 */
            Console.info();
            break;
        case 'show':
            Console.show(true);
            Console.scrollBottom();
            break;
        default:
            Console.setConfig('Mode', 'info');
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
        Console.$requestForm.children('textarea').val(queryString);
        Console.$requestForm.children('[name=server]').attr('value', PMA_commonParams.get('server'));
        if (options && options.db) {
            Console.$requestForm.children('[name=db]').val(options.db);
            if (options.table) {
                Console.$requestForm.children('[name=table]').val(options.table);
            } else {
                Console.$requestForm.children('[name=table]').val('');
            }
        } else {
            Console.$requestForm.children('[name=db]').val(
                (PMA_commonParams.get('db').length > 0 ? PMA_commonParams.get('db') : ''));
        }
        Console.$requestForm.find('[name=profiling]').remove();
        if (options && options.profiling === true) {
            Console.$requestForm.append('<input name="profiling" value="on">');
        }
        if (! confirmQuery(Console.$requestForm[0], Console.$requestForm.children('textarea')[0].value)) {
            return;
        }
        Console.$requestForm.children('[name=console_message_id]')
            .val(Console.pmaConsoleMessages.appendQuery({ sql_query: queryString }).message_id);
        Console.$requestForm.trigger('submit');
        Console.pmaConsoleInput.clear();
        PMA_reloadNavigation();
    },

    ajaxCallback: function (data) {
        if (data && data.console_message_id) {
            Console.pmaConsoleMessages.updateQuery(data.console_message_id, data.success,
                (data._reloadQuerywindow ? data._reloadQuerywindow : false));
        } else if (data && data._reloadQuerywindow) {
            if (data._reloadQuerywindow.sql_query.length > 0) {
                Console.pmaConsoleMessages.appendQuery(data._reloadQuerywindow, 'successed')
                    .$message.addClass(Console.config.CurrentQuery ? '' : 'hide');
            }
        }
    },

    /**
     * Change console to collapse mode
     *
     * @return void
     */
    collapse: function () {
        Console.setConfig('Mode', 'collapse');
        var pmaConsoleHeight = Math.max(92, Console.config.Height);

        Console.$consoleToolbar.addClass('collapsed');
        Console.$consoleAllContents.height(pmaConsoleHeight);
        Console.$consoleContent.stop();
        Console.$consoleContent.animate({ 'margin-bottom': -1 * Console.$consoleContent.outerHeight() + 'px' },
            'fast', 'easeOutQuart', function () {
                Console.$consoleContent.css({ display:'none' });
                $(window).trigger('resize');
            });
        Console.hideCard();
    },

    /**
     * Show console
     *
     * @param bool inputFocus If true, focus the input line after show()
     * @return void
     */
    show: function (inputFocus) {
        Console.setConfig('Mode', 'show');

        var pmaConsoleHeight = Math.max(92, Console.config.Height);
        pmaConsoleHeight = Math.min(Console.config.Height, (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight) - 25);
        Console.$consoleContent.css({ display:'block' });
        if (Console.$consoleToolbar.hasClass('collapsed')) {
            Console.$consoleToolbar.removeClass('collapsed');
        }
        Console.$consoleAllContents.height(pmaConsoleHeight);
        Console.$consoleContent.stop();
        Console.$consoleContent.animate({ 'margin-bottom': 0 },
            'fast', 'easeOutQuart', function () {
                $(window).trigger('resize');
                if (inputFocus) {
                    Console.pmaConsoleInput.focus();
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
        Console.collapse();
    },

    /**
     * Toggle console mode between collapse/show
     * Used for toggle buttons and shortcuts
     *
     * @return void
     */
    toggle: function () {
        switch (Console.config.Mode) {
        case 'collapse':
        case 'info':
            Console.show(true);
            break;
        case 'show':
            Console.collapse();
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
        Console.$consoleContent.scrollTop(Console.$consoleContent.prop('scrollHeight'));
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
        Console.pmaConsoleInput.blur();
        if ($card.parents('.card').length > 0) {
            Console.showCard($card.parents('.card'));
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
        Console.setConfig('AlwaysExpand', $('#pma_console_options input[name=always_expand]').prop('checked'));
        Console.setConfig('StartHistory', $('#pma_console_options').find('input[name=start_history]').prop('checked'));
        Console.setConfig('CurrentQuery', $('#pma_console_options').find('input[name=current_query]').prop('checked'));
        Console.setConfig('EnterExecutes', $('#pma_console_options').find('input[name=enter_executes]').prop('checked'));
        Console.setConfig('DarkTheme', $('#pma_console_options').find('input[name=dark_theme]').prop('checked'));
        /* Setting the dark theme of the console*/
        if (Console.config.DarkTheme) {
            $('#pma_console').find('>.content').addClass('console_dark_theme');
        } else {
            $('#pma_console').find('>.content').removeClass('console_dark_theme');
        }
    },

    setConfig: function (key, value) {
        Console.config[key] = value;
        configSet('Console/' + key, value);
    },

    isSelect: function (queryString) {
        var reg_exp = /^SELECT\s+/i;
        return reg_exp.test(queryString);
    }
};

export default Console;
