/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Resizer object
 * Careful: this object UI logics highly related with functions under PMA_console
 * Resizing min-height is 32, if small than it, console will collapse
 * @namespace ConsoleResizer
 */
export default class ConsoleResizer {
    constructor (instance) {
        this._posY = 0;
        this._height = 0;
        this._resultHeight = 0;
        this.pmaConsole = null;
        this.initialize = this.initialize.bind(this);
        this._mousedown = this._mousedown.bind(this);
        this._mousemove = this._mousemove.bind(this);
        this._mouseup = this._mouseup.bind(this);
        this.setPmaConsole(instance);
    }
    setPmaConsole (instance) {
        this.pmaConsole = instance;
        this.initialize();
    }
    /**
     * Mousedown event handler for bind to resizer
     *
     * @return void
     */
    _mousedown (event) {
        if (this.pmaConsole.config.Mode !== 'show') {
            return;
        }
        this._posY = event.pageY;
        this._height = this.pmaConsole.$consoleContent.height();
        $(document).mousemove(this._mousemove);
        $(document).mouseup(this._mouseup);
        // Disable text selection while resizing
        $(document).on('selectstart', function () {
            return false;
        });
    }
    /**
     * Mousemove event handler for bind to resizer
     *
     * @return void
     */
    _mousemove (event) {
        if (event.pageY < 35) {
            event.pageY = 35;
        }
        this._resultHeight = this._height + (this._posY - event.pageY);
        // Content min-height is 32, if adjusting height small than it we'll move it out of the page
        if (this._resultHeight <= 32) {
            this.pmaConsole.$consoleAllContents.height(32);
            this.pmaConsole.$consoleContent.css('margin-bottom', this._resultHeight - 32);
        } else {
            // Logic below makes viewable area always at bottom when adjusting height and content already at bottom
            if (this.pmaConsole.$consoleContent.scrollTop() + this.pmaConsole.$consoleContent.innerHeight() + 16
                >= this.pmaConsole.$consoleContent.prop('scrollHeight')) {
                this.pmaConsole.$consoleAllContents.height(this._resultHeight);
                this.pmaConsole.scrollBottom();
            } else {
                this.pmaConsole.$consoleAllContents.height(this._resultHeight);
            }
        }
    }
    /**
     * Mouseup event handler for bind to resizer
     *
     * @return void
     */
    _mouseup () {
        this.pmaConsole.setConfig('Height', this._resultHeight);
        this.pmaConsole.show();
        $(document).off('mousemove');
        $(document).off('mouseup');
        $(document).off('selectstart');
    }
    /**
     * Used for console resizer initialize
     *
     * @return void
     */
    initialize () {
        $('#pma_console').find('.toolbar').off('mousedown');
        $('#pma_console').find('.toolbar').mousedown(this._mousedown);
    }
}
