/* vim: set expandtab sw=4 ts=4 sts=4: */
import CodeMirror from 'codemirror';
import 'codemirror/mode/sql/sql.js';
import 'codemirror/addon/runmode/runmode.js';
import 'codemirror/addon/hint/show-hint.js';
import 'codemirror/addon/hint/sql-hint.js';
import 'codemirror/addon/lint/lint.js';
import '../../plugins/codemirror/sql-lint';
import { codemirrorAutocompleteOnInputRead } from '../../utils/sql';
import CommonParams from '../../variables/common_params';

/**
 * Console input object
 * @class ConsoleInput
 */
export default class ConsoleInput {
    /**
     * @constructor
     * @param {object} instance    Instance of PMA Console
     */
    constructor (instance) {
        /**
         * @var array, contains Codemirror objects or input jQuery objects
         * @access private
         */
        this._inputs = null;
        /**
         * @var bool, if codemirror enabled
         * @access private
         */
        this._codemirror = false;
        /**
         * @var int, count for history navigation, 0 for current input
         * @access private
         */
        this._historyCount = 0;
        /**
         * @var string, current input when navigating through history
         * @access private
         */
        this._historyPreserveCurrent = null;
        /**
         * @var object
         * @access private
         */
        this.pmaConsole = null;

        /**
         * Bindings for accessing the instance of the class using this
         * insde the methods.
         */
        this.setPmaConsole = this.setPmaConsole.bind(this);
        this.initialize = this.initialize.bind(this);
        this._historyNavigate = this._historyNavigate.bind(this);
        this._keydown = this._keydown.bind(this);
        this.execute = this.execute.bind(this);
        this.clear = this.clear.bind(this);
        this.focus = this.focus.bind(this);
        this.blur = this.blur.bind(this);
        this.setText = this.setText.bind(this);
        this.getText = this.getText.bind(this);

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
     * Used for Console Input initialise
     * @return {void}
     */
    initialize () {
        // _cm object can't be reinitialize
        if (this._inputs !== null) {
            return;
        }
        if (CommonParams.get('CodemirrorEnable') === true) {
            this._codemirror = true;
        }
        this._inputs = [];
        if (this._codemirror) {
            this._inputs.console = CodeMirror($('#pma_console').find('.console_query_input')[0], {
                theme: 'pma',
                mode: 'text/x-sql',
                lineWrapping: true,
                extraKeys: { 'Ctrl-Space': 'autocomplete' },
                hintOptions: { 'completeSingle': false, 'completeOnSingleClick': true },
                gutters: ['CodeMirror-lint-markers'],
                lint: {
                    'getAnnotations': CodeMirror.sqlLint,
                    'async': true,
                }
            });
            this._inputs.console.on('inputRead', codemirrorAutocompleteOnInputRead);
            this._inputs.console.on('keydown', function (instance, event) {
                this._historyNavigate(event);
            }.bind(this));
            if ($('#pma_bookmarks').length !== 0) {
                this._inputs.bookmark = CodeMirror($('#pma_console').find('.bookmark_add_input')[0], {
                    theme: 'pma',
                    mode: 'text/x-sql',
                    lineWrapping: true,
                    extraKeys: { 'Ctrl-Space': 'autocomplete' },
                    hintOptions: { 'completeSingle': false, 'completeOnSingleClick': true },
                    gutters: ['CodeMirror-lint-markers'],
                    lint: {
                        'getAnnotations': CodeMirror.sqlLint,
                        'async': true,
                    }
                });
                this._inputs.bookmark.on('inputRead', codemirrorAutocompleteOnInputRead);
            }
        } else {
            this._inputs.console =
                $('<textarea>').appendTo('#pma_console .console_query_input')
                    .on('keydown', this._historyNavigate);
            if ($('#pma_bookmarks').length !== 0) {
                this._inputs.bookmark =
                    $('<textarea>').appendTo('#pma_console .bookmark_add_input');
            }
        }
        $('#pma_console').find('.console_query_input').keydown(this._keydown);
    }

    /**
     * @param {jQueryEvent} event
     * @return {void}
     */
    _historyNavigate (event) {
        if (event.keyCode === 38 || event.keyCode === 40) {
            var upPermitted = false;
            var downPermitted = false;
            var editor = this._inputs.console;
            var cursorLine;
            var totalLine;
            if (this._codemirror) {
                cursorLine = editor.getCursor().line;
                totalLine = editor.lineCount();
            } else {
                // Get cursor position from textarea
                var text = this.getText();
                cursorLine = text.substr(0, editor.prop('selectionStart')).split('\n').length - 1;
                totalLine = text.split(/\r*\n/).length;
            }
            if (cursorLine === 0) {
                upPermitted = true;
            }
            if (cursorLine === totalLine - 1) {
                downPermitted = true;
            }
            var nextCount;
            var queryString = false;
            if (upPermitted && event.keyCode === 38) {
                // Navigate up in history
                if (this._historyCount === 0) {
                    this._historyPreserveCurrent = this.getText();
                }
                nextCount = this._historyCount + 1;
                queryString = this.pmaConsole.pmaConsoleMessages.getHistory(nextCount);
            } else if (downPermitted && event.keyCode === 40) {
                // Navigate down in history
                if (this._historyCount === 0) {
                    return;
                }
                nextCount = this._historyCount - 1;
                if (nextCount === 0) {
                    queryString = this._historyPreserveCurrent;
                } else {
                    queryString = this.pmaConsole.pmaConsoleMessages.getHistory(nextCount);
                }
            }
            if (queryString !== false) {
                this._historyCount = nextCount;
                this.setText(queryString, 'console');
                if (this._codemirror) {
                    editor.setCursor(editor.lineCount(), 0);
                }
                event.preventDefault();
            }
        }
    }

    /**
     * Mousedown event handler for bind to input
     * Shortcut is Ctrl+Enter key or just ENTER, depending on console's
     * configuration.
     *
     * @return void
     */
    _keydown (event) {
        if (this.pmaConsole.config.EnterExecutes) {
            // Enter, but not in combination with Shift (which writes a new line).
            if (!event.shiftKey && event.keyCode === 13) {
                this.execute();
            }
        } else {
            // Ctrl+Enter
            if (event.ctrlKey && event.keyCode === 13) {
                this.execute();
            }
        }
    }

    /**
     * Used for send text to PMA_console.execute()
     *
     * @return void
     */
    execute () {
        if (this._codemirror) {
            this.pmaConsole.execute(this._inputs.console.getValue());
        } else {
            this.pmaConsole.execute(this._inputs.console.val());
        }
    }

    /**
     * Used for clear the input
     *
     * @param string target, default target is console input
     * @return void
     */
    clear (target) {
        this.setText('', target);
    }

    /**
     * Used for set focus to input
     *
     * @return void
     */
    focus () {
        this._inputs.console.focus();
    }

    /**
     * Used for blur input
     *
     * @return void
     */
    blur () {
        if (this._codemirror) {
            this._inputs.console.getInputField().blur();
        } else {
            this._inputs.console.blur();
        }
    }

    /**
     * Used for set text in input
     *
     * @param string text
     * @param string target
     *
     * @return void
     */
    setText (text, target) {
        if (this._codemirror) {
            switch (target) {
            case 'bookmark':
                this.pmaConsole.execute(this._inputs.bookmark.setValue(text));
                break;
            default:
            case 'console':
                this.pmaConsole.execute(this._inputs.console.setValue(text));
            }
        } else {
            switch (target) {
            case 'bookmark':
                this.pmaConsole.execute(this._inputs.bookmark.val(text));
                break;
            default:
            case 'console':
                this.pmaConsole.execute(this._inputs.console.val(text));
            }
        }
    }

    /**
     * Used for getting the text of input
     *
     * @param {string} target
     *
     * @return {string}
     */
    getText (target) {
        if (this._codemirror) {
            switch (target) {
            case 'bookmark':
                return this._inputs.bookmark.getValue();
            default:
            case 'console':
                return this._inputs.console.getValue();
            }
        } else {
            switch (target) {
            case 'bookmark':
                return this._inputs.bookmark.val();
            default:
            case 'console':
                return this._inputs.console.val();
            }
        }
    }
}
