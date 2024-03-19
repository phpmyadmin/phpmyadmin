import { setConfigValue } from '../functions/config.ts';

/**
 * @link https://docs.phpmyadmin.net/en/latest/config.html#console-settings
 */
export const Config = {
    /**
     * @type {boolean}
     */
    StartHistory: false,
    /**
     * @type {boolean}
     */
    AlwaysExpand: false,
    /**
     * @type {boolean}
     */
    CurrentQuery: true,
    /**
     * @type {boolean}
     */
    EnterExecutes: false,
    /**
     * @type {boolean}
     */
    DarkTheme: false,
    /**
     * @type {'info'|'show'|'collapse'}
     */
    Mode: 'info',
    /**
     * @type {number}
     */
    Height: 92,
    /**
     * @type {boolean}
     */
    GroupQueries: false,
    /**
     * @type {'exec'|'time'|'count'}
     */
    OrderBy: 'exec',
    /**
     * @type {'asc'|'desc'}
     */
    Order: 'asc',

    init: function (dataset: DOMStringMap): void {
        this.StartHistory = dataset.startHistory === 'true';
        this.AlwaysExpand = dataset.alwaysExpand === 'true';
        this.CurrentQuery = dataset.currentQuery !== undefined ? dataset.currentQuery === 'true' : true;
        this.EnterExecutes = dataset.enterExecutes === 'true';
        this.DarkTheme = dataset.darkTheme === 'true';
        this.Mode = dataset.mode === 'show' || dataset.mode === 'collapse' ? dataset.mode : 'info';
        const height = Number(dataset.height);
        this.Height = height > 0 ? height : 92;
        this.GroupQueries = dataset.groupQueries === 'true';
        this.OrderBy = dataset.orderBy === 'time' || dataset.orderBy === 'count' ? dataset.orderBy : 'exec';
        this.Order = dataset.order === 'desc' ? 'desc' : 'asc';
    },

    /**
     * @param {'StartHistory'|'AlwaysExpand'|'CurrentQuery'|'EnterExecutes'|'DarkTheme'|'Mode'|'Height'|'GroupQueries'|'OrderBy'|'Order'} key
     * @param {boolean|string|number} value
     */
    set: function (key, value): void {
        this[key] = value;
        setConfigValue('Console/' + key, value);
    },

    /**
     * Used for update console config
     */
    update: function (): void {
        this.set('AlwaysExpand', !! (document.getElementById('consoleOptionsAlwaysExpandCheckbox') as HTMLInputElement).checked);
        this.set('StartHistory', !! (document.getElementById('consoleOptionsStartHistoryCheckbox') as HTMLInputElement).checked);
        this.set('CurrentQuery', !! (document.getElementById('consoleOptionsCurrentQueryCheckbox') as HTMLInputElement).checked);
        this.set('EnterExecutes', !! (document.getElementById('consoleOptionsEnterExecutesCheckbox') as HTMLInputElement).checked);
        this.set('DarkTheme', !! (document.getElementById('consoleOptionsDarkThemeCheckbox') as HTMLInputElement).checked);
        /* Setting the dark theme of the console*/
        const consoleContent = document.getElementById('pma_console').querySelector('.content');
        if (this.DarkTheme) {
            consoleContent.classList.add('console_dark_theme');
        } else {
            consoleContent.classList.remove('console_dark_theme');
        }
    }
};
